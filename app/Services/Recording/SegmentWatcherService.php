<?php

namespace App\Services\Recording;

use App\Models\Camera;
use App\Models\RecordingSegment;
use App\Models\RecordingSetting;
use App\Models\StorageBackend;
use App\Services\Storage\StorageBackendManager;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * SegmentWatcherService
 *
 * Monitors the local recording directory for new completed segments.
 * This is the core of the continuous recording system:
 *
 * 1. MediaMTX writes segments to /recordings/{path}/ (1-min segments)
 * 2. This service detects when a segment is "complete" (new segment started = old one finished)
 * 3. Indexes complete segments in recording_segments table
 * 4. Triggers upload to storage backend (MinIO, S3, etc.)
 * 5. Optionally cleans up local files after upload
 *
 * Run via: php artisan recording:watch (continuous) or scheduled every 30s
 */
class SegmentWatcherService
{
    public function __construct(
        private StorageBackendManager $storageManager,
    ) {}

    /**
     * Scan all camera recording directories for new segments.
     */
    public function scan(): array
    {
        $basePath = config('streaming.recording.base_path', '/recordings');

        if (! File::isDirectory($basePath)) {
            return ['scanned' => 0, 'new_segments' => 0, 'uploaded' => 0];
        }

        $cameras = Camera::whereNotNull('slug')
            ->where('continuous_recording', true)
            ->with('tenant')
            ->get()
            ->keyBy(function (Camera $camera) {
                // Build the MediaMTX path used for recording directory
                $shortId = substr($camera->id, 0, 8);

                return "cam_{$shortId}_{$camera->slug}";
            });

        $totalNew = 0;
        $totalUploaded = 0;
        $dirs = File::directories($basePath);

        foreach ($dirs as $dir) {
            $dirName = basename($dir);
            $camera = $cameras->get($dirName);

            if (! $camera) {
                // Try to find camera by matching directory pattern
                $camera = $this->findCameraByDir($dirName, $cameras);
            }

            if (! $camera) {
                continue;
            }

            $result = $this->scanCameraDirectory($camera, $dir);
            $totalNew += $result['new_segments'];
            $totalUploaded += $result['uploaded'];
        }

        return [
            'scanned' => count($dirs),
            'new_segments' => $totalNew,
            'uploaded' => $totalUploaded,
        ];
    }

    /**
     * Scan a single camera's recording directory.
     */
    public function scanCameraDirectory(Camera $camera, string $directory): array
    {
        $files = File::files($directory);
        $videoFiles = collect($files)
            ->filter(fn ($f) => str_ends_with(strtolower($f->getFilename()), '.ts')
                || str_ends_with(strtolower($f->getFilename()), '.mp4'))
            ->sortBy(fn ($f) => $f->getMTime());

        if ($videoFiles->isEmpty()) {
            return ['new_segments' => 0, 'uploaded' => 0];
        }

        $newSegments = 0;
        $uploaded = 0;
        $filesArray = $videoFiles->values()->all();
        $fileCount = count($filesArray);

        foreach ($filesArray as $index => $file) {
            $filename = $file->getFilename();

            // Check if segment already indexed
            $existing = RecordingSegment::where('camera_id', $camera->id)
                ->where('filename', $filename)
                ->first();

            if ($existing) {
                // If already indexed as 'writing' and there's a newer file, mark as ready
                if ($existing->status === RecordingSegment::STATUS_WRITING && $index < $fileCount - 1) {
                    $realDuration = null;
                    $realEndsAt = null;
                    $nextFile = $filesArray[$index + 1] ?? null;
                    if ($nextFile && $existing->starts_at) {
                        $nextStartsAt = $this->parseTimestampFromFilename($nextFile->getFilename());
                        if ($nextStartsAt) {
                            $gap = (int) $existing->starts_at->diffInSeconds($nextStartsAt);
                            $segDuration = $this->getSegmentDuration($camera);
                            // Only use next file's start as ends_at if the gap is reasonable
                            // (within 2x segment duration). Otherwise use probe or default.
                            if ($gap <= $segDuration * 2) {
                                $realDuration = $gap;
                                $realEndsAt = $nextStartsAt;
                            } else {
                                $realDuration = $this->probeVideoDuration($file->getPathname()) ?? $segDuration;
                            }
                        }
                    }
                    $existing->markReady($file->getSize(), $realDuration, $realEndsAt);
                    $newSegments++;
                }

                // Try upload if ready but not yet uploaded
                if ($existing->status === RecordingSegment::STATUS_READY && ! $existing->remote_path) {
                    if ($this->uploadSegment($existing, $file->getPathname())) {
                        $uploaded++;
                    }
                }

                continue;
            }

            // New segment detected
            $isLastFile = ($index === $fileCount - 1);
            $startsAt = $this->parseTimestampFromFilename($filename);

            $segmentDuration = $this->getSegmentDuration($camera);
            $endsAt = $isLastFile ? null : ($startsAt?->copy()->addSeconds($segmentDuration) ?? now());

            $segment = RecordingSegment::create([
                'camera_id' => $camera->id,
                'tenant_id' => $camera->tenant_id,
                'filename' => $filename,
                'local_path' => $file->getPathname(),
                'file_size_bytes' => $file->getSize(),
                'starts_at' => $startsAt ?? now(),
                'ends_at' => $endsAt,
                'duration_seconds' => ($startsAt && $endsAt) ? (int) $startsAt->diffInSeconds($endsAt) : 0,
                'status' => $isLastFile ? RecordingSegment::STATUS_WRITING : RecordingSegment::STATUS_READY,
                'codec' => $camera->codec ?? 'h264',
                'resolution' => $camera->resolution,
                'fps' => $camera->fps,
            ]);

            if (! $isLastFile) {
                // Segment is complete, try to calculate real duration
                $nextFile = $filesArray[$index + 1] ?? null;
                if ($nextFile) {
                    $nextStartsAt = $this->parseTimestampFromFilename($nextFile->getFilename());
                    if ($startsAt && $nextStartsAt) {
                        $realDuration = (int) $startsAt->diffInSeconds($nextStartsAt);
                        $segment->update([
                            'ends_at' => $nextStartsAt,
                            'duration_seconds' => $realDuration,
                        ]);
                    }
                }

                $newSegments++;

                // Dispatch thumbnail generation for this newly ready segment
                \App\Jobs\GenerateSegmentThumbnailJob::dispatch($segment->id);

                // Upload to storage backend
                if ($this->uploadSegment($segment, $file->getPathname())) {
                    $uploaded++;
                }
            }
        }

        if ($newSegments > 0 || $uploaded > 0) {
            Log::info("Segment scan: camera {$camera->name}", [
                'camera_id' => $camera->id,
                'new_segments' => $newSegments,
                'uploaded' => $uploaded,
                'total_files' => $fileCount,
            ]);
        }

        return ['new_segments' => $newSegments, 'uploaded' => $uploaded];
    }

    /**
     * Upload a segment to the configured storage backend.
     */
    private function uploadSegment(RecordingSegment $segment, string $localPath): bool
    {
        $backend = $this->resolveBackend($segment);
        if (! $backend) {
            Log::debug('Nenhum storage backend remoto encontrado para upload.', [
                'segment_id' => $segment->id,
                'tenant_id' => $segment->tenant_id,
            ]);

            return false;
        }

        try {
            $segment->update(['status' => RecordingSegment::STATUS_UPLOADING]);

            $driver = $this->storageManager->getDriver($backend);
            $remotePath = $this->buildRemotePath($segment);

            $success = $driver->storeFile($remotePath, $localPath);

            if ($success) {
                $segment->markUploaded($remotePath, $backend->id);

                // Cleanup local if configured
                $settings = RecordingSetting::forTenant($segment->tenant_id);
                if ($settings->cleanup_local_after_upload) {
                    $this->cleanupLocal($segment);
                }

                return true;
            }

            $segment->update(['status' => RecordingSegment::STATUS_READY]);
            Log::warning('Falha upload segmento.', [
                'segment_id' => $segment->id,
                'backend' => $backend->name,
            ]);

            return false;
        } catch (\Throwable $e) {
            $segment->update(['status' => RecordingSegment::STATUS_READY]);
            Log::error('Erro upload segmento.', [
                'segment_id' => $segment->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Clean up local file after successful upload.
     * MediaMTX creates files as root:root, so we try multiple methods.
     */
    private function cleanupLocal(RecordingSegment $segment): void
    {
        if (! $segment->local_path || ! File::exists($segment->local_path)) {
            if ($segment->local_path) {
                $segment->update(['local_path' => null]);
            }

            return;
        }

        $deleted = false;

        // Method 1: Try PHP's unlink
        try {
            @File::delete($segment->local_path);
            $deleted = ! File::exists($segment->local_path);
        } catch (\Throwable) {
            // Permission denied — try fallback
        }

        // Method 2: Shell rm -f (works if container runs as root or has sudo)
        if (! $deleted) {
            try {
                @exec('rm -f ' . escapeshellarg($segment->local_path) . ' 2>/dev/null');
                $deleted = ! File::exists($segment->local_path);
            } catch (\Throwable) {
                // Still failed
            }
        }

        if ($deleted) {
            $segment->update(['local_path' => null]);
            Log::debug("Cleanup: local file deleted for segment {$segment->id}");
        } else {
            Log::warning("Cleanup: could not delete local file for segment {$segment->id}: {$segment->local_path}");
        }
    }

    /**
     * Resolve storage backend for a segment upload.
     *
     * Skips LOCAL backends since segments are already stored locally.
     * Priority: 1) Tenant's default remote backend  2) Global default remote backend  3) Highest-priority active remote backend
     */
    private function resolveBackend(RecordingSegment $segment): ?StorageBackend
    {
        // 1. Tenant default backend (non-local, for upload)
        if ($segment->tenant_id) {
            $backend = StorageBackend::forTenant($segment->tenant_id)
                ->active()
                ->where('is_default', true)
                ->where('driver', '!=', StorageBackend::DRIVER_LOCAL)
                ->first();

            if ($backend) {
                return $backend;
            }
        }

        // 2. Global default backend (non-local)
        $backend = StorageBackend::whereNull('tenant_id')
            ->active()
            ->where('is_default', true)
            ->where('driver', '!=', StorageBackend::DRIVER_LOCAL)
            ->first();

        if ($backend) {
            return $backend;
        }

        // 3. Fallback: highest-priority active non-local backend for this tenant
        if ($segment->tenant_id) {
            return StorageBackend::forTenant($segment->tenant_id)
                ->active()
                ->where('driver', '!=', StorageBackend::DRIVER_LOCAL)
                ->orderBy('priority')
                ->first();
        }

        return null;
    }

    /**
     * Build remote storage path for a segment.
     */
    private function buildRemotePath(RecordingSegment $segment): string
    {
        $tenantId = $segment->tenant_id ?? 'global';
        $camera = $segment->camera;
        $cameraSlug = $camera?->slug ?? $camera?->id ?? 'unknown';
        $date = $segment->starts_at->format('Y/m/d');

        return "recordings/tenant_{$tenantId}/{$cameraSlug}/{$date}/{$segment->filename}";
    }

    /**
     * Parse timestamp from filename format: YYYY-MM-DD_HH-MM-SS-ffffff.ts (or .mp4 legacy)
     * FFmpeg/MediaMTX uses UTC timestamps in filenames.
     */
    private function parseTimestampFromFilename(string $filename): ?Carbon
    {
        // Format: 2026-02-10_13-27-37-371731.ts (or .mp4)
        if (preg_match('/^(\d{4}-\d{2}-\d{2})_(\d{2})-(\d{2})-(\d{2})/', $filename, $m)) {
            $dateStr = "{$m[1]} {$m[2]}:{$m[3]}:{$m[4]}";

            try {
                // Parse as UTC (MediaMTX format) and convert to app timezone
                return Carbon::createFromFormat('Y-m-d H:i:s', $dateStr, 'UTC')
                    ->setTimezone(config('app.timezone'));
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }

    /**
     * Get segment duration for a camera's tenant.
     */
    private function getSegmentDuration(Camera $camera): int
    {
        $settings = RecordingSetting::forTenant($camera->tenant_id);

        return $settings->segment_duration_seconds;
    }

    /**
     * Probe actual video duration using ffprobe (available in transcoder volume).
     * Returns null on failure so callers can fall back to default.
     */
    private function probeVideoDuration(string $filePath): ?int
    {
        if (! file_exists($filePath)) {
            return null;
        }

        $cmd = "ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 " . escapeshellarg($filePath) . " 2>/dev/null";
        $output = trim((string) shell_exec($cmd));

        if ($output && is_numeric($output)) {
            return (int) round((float) $output);
        }

        return null;
    }

    /**
     * Find camera by directory name pattern.
     */
    private function findCameraByDir(string $dirName, $cameras): ?Camera
    {
        // Pattern: cam_{shortId}_{slug}
        if (preg_match('/^cam_([a-f0-9]{8})_/', $dirName, $m)) {
            $shortId = $m[1];

            return Camera::where('id', 'like', $shortId . '%')->first();
        }

        return null;
    }
}
