<?php

namespace App\Services\Camera;

use App\Models\Camera;
use App\Models\Recording;
use App\Models\RecordingSegment;
use App\Models\StorageBackend;
use App\Models\Stream;
use App\Services\Storage\StorageBackendManager;
use App\Services\Streaming\MediaMtxClient;
use App\Services\Streaming\StreamManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * Handles complete cleanup of all resources associated with a camera.
 *
 * When a camera is deleted, this service ensures that:
 * 1. Active streams are stopped and removed from MediaMTX
 * 2. FFmpeg transcoding processes are terminated
 * 3. All database records are removed (streams, recordings, segments, analytics)
 * 4. Local file storage is cleaned (recordings, snapshots, cache, logs)
 * 5. Remote storage is cleaned (S3/MinIO files)
 * 6. Redis/cache entries are purged
 *
 * This is necessary because the Camera model uses SoftDeletes, which means
 * database CASCADE ON DELETE rules do NOT fire. All related records must
 * be manually removed.
 */
class CameraCleanupService
{
    private array $cleanupLog = [];

    public function __construct(
        private MediaMtxClient $mediaMtxClient,
        private StreamManager $streamManager,
    ) {}

    /**
     * Perform complete cleanup of all resources for a camera.
     *
     * Returns a summary of what was cleaned up.
     */
    public function cleanup(Camera $camera): array
    {
        $cameraId = $camera->id;
        $cameraName = $camera->name;

        Log::info("CameraCleanupService: starting full cleanup for camera '{$cameraName}' ({$cameraId}).");

        $this->cleanupLog = [];

        // ── 1. Stop active streams and remove from MediaMTX ──
        $this->stopActiveStreams($camera);

        // ── 2. Stop FFmpeg transcoding processes ──
        $this->stopTranscodingProcesses($cameraId);

        // ── 3. Collect file paths before deleting DB records ──
        $localPaths = $this->collectLocalFilePaths($camera);
        $remoteData = $this->collectRemoteFilesByBackend($cameraId);

        // ── 4. Delete database records (must be done before camera delete) ──
        $this->deleteRelatedDatabaseRecords($camera);

        // ── 5. Clean local file storage ──
        $this->cleanLocalStorage($camera, $localPaths);

        // ── 6. Clean remote storage (S3/MinIO/Azure/GCS/etc.) ──
        $this->cleanRemoteStorage($remoteData, $camera);

        // ── 7. Clean Redis/cache entries ──
        $this->cleanCacheEntries($cameraId);

        Log::info("CameraCleanupService: cleanup completed for camera '{$cameraName}' ({$cameraId}).", $this->cleanupLog);

        return $this->cleanupLog;
    }

    /**
     * Step 1: Stop all active streams and remove MediaMTX paths.
     */
    private function stopActiveStreams(Camera $camera): void
    {
        $streams = Stream::forCamera($camera->id)
            ->whereIn('status', [
                Stream::STATUS_STARTING,
                Stream::STATUS_ACTIVE,
                Stream::STATUS_RECONNECTING,
                Stream::STATUS_ERROR,
            ])
            ->get();

        $stoppedCount = 0;

        foreach ($streams as $stream) {
            try {
                // Remove path from MediaMTX (stops RTSP connection)
                if ($stream->mediamtx_path) {
                    $this->mediaMtxClient->removePath($stream->mediamtx_path);
                }
                $stoppedCount++;
            } catch (\Throwable $e) {
                Log::warning("CameraCleanupService: failed to remove MediaMTX path '{$stream->mediamtx_path}'.", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Also disable recording flag on camera
        $camera->update(['is_online' => false, 'is_recording' => false]);

        $this->cleanupLog['streams_stopped'] = $stoppedCount;
    }

    /**
     * Step 2: Stop FFmpeg transcoding processes for this camera.
     */
    private function stopTranscodingProcesses(string $cameraId): void
    {
        try {
            // Check for running FFmpeg process
            $cachePrefix = config('transcoding.process.cache_prefix', 'ffmpeg_transcoder:');
            $processData = Cache::get("{$cachePrefix}{$cameraId}");

            if ($processData && ! empty($processData['pid'])) {
                $pid = (int) $processData['pid'];

                // Kill the FFmpeg process
                if ($pid > 0 && posix_kill($pid, 0)) {
                    posix_kill($pid, SIGTERM);
                    Log::info("CameraCleanupService: killed FFmpeg process PID {$pid} for camera {$cameraId}.");
                }

                Cache::forget("{$cachePrefix}{$cameraId}");
            }

            // Remove from transcoding camera cache
            Cache::forget("transcoding:cameras:{$cameraId}");

            // Remove from FFmpeg index
            $index = Cache::get("{$cachePrefix}_index", []);
            $index = array_filter($index, fn ($id) => $id !== $cameraId);
            Cache::put("{$cachePrefix}_index", array_values($index), 86400);

            $this->cleanupLog['transcoding_stopped'] = true;
        } catch (\Throwable $e) {
            Log::warning("CameraCleanupService: error stopping transcoding for camera {$cameraId}.", [
                'error' => $e->getMessage(),
            ]);
            $this->cleanupLog['transcoding_stopped'] = false;
        }
    }

    /**
     * Step 3a: Collect local file paths before database records are deleted.
     * We need the mediamtx_path from streams to know the recording directory.
     */
    private function collectLocalFilePaths(Camera $camera): array
    {
        $paths = [];

        // Get all MediaMTX paths from streams (to find recording directories)
        $mediamtxPaths = Stream::forCamera($camera->id)
            ->whereNotNull('mediamtx_path')
            ->pluck('mediamtx_path')
            ->unique()
            ->toArray();

        $paths['mediamtx_paths'] = $mediamtxPaths;

        // Recording directories: /recordings/{mediamtx_path}/
        $recordingBasePath = config('streaming.recording.base_path', '/recordings');
        $paths['recording_dirs'] = array_map(
            fn ($p) => "{$recordingBasePath}/{$p}",
            $mediamtxPaths
        );

        return $paths;
    }

    /**
     * Step 3b: Collect remote file info from recording segments.
     *
     * Groups files by storage_backend_id so we can use the correct
     * driver (MinIO, S3, Azure, GCS, etc.) for each backend.
     *
     * Also collects segments without a backend_id (legacy/fallback scenario)
     * and tracks file sizes per backend for metrics update.
     *
     * Returns: [
     *     'by_backend' => [ backend_id => [remote_path, ...], ... ],
     *     'orphan_paths' => [remote_path, ...],       // segments without backend_id
     *     'bytes_by_backend' => [ backend_id => total_bytes, ... ],
     * ]
     */
    private function collectRemoteFilesByBackend(string $cameraId): array
    {
        $segments = RecordingSegment::where('camera_id', $cameraId)
            ->whereNotNull('remote_path')
            ->where('remote_path', '!=', '')
            ->select('remote_path', 'storage_backend_id', 'file_size_bytes')
            ->get();

        $byBackend = [];
        $orphanPaths = [];
        $bytesByBackend = [];

        foreach ($segments as $segment) {
            $backendId = $segment->storage_backend_id;

            if ($backendId) {
                if (! isset($byBackend[$backendId])) {
                    $byBackend[$backendId] = [];
                    $bytesByBackend[$backendId] = 0;
                }
                $byBackend[$backendId][] = $segment->remote_path;
                $bytesByBackend[$backendId] += (int) ($segment->file_size_bytes ?? 0);
            } else {
                // Segment uploaded but no backend_id recorded (legacy)
                $orphanPaths[] = $segment->remote_path;
            }
        }

        return [
            'by_backend' => $byBackend,
            'orphan_paths' => $orphanPaths,
            'bytes_by_backend' => $bytesByBackend,
        ];
    }

    /**
     * Step 4: Delete all related database records.
     *
     * Since Camera uses SoftDeletes, CASCADE ON DELETE does NOT fire.
     * We must manually delete all dependent records.
     *
     * Order matters: delete children first (segments → recordings → streams).
     */
    private function deleteRelatedDatabaseRecords(Camera $camera): void
    {
        $cameraId = $camera->id;

        // Recording segments (must be deleted before recordings)
        $segmentsDeleted = RecordingSegment::where('camera_id', $cameraId)->delete();
        $this->cleanupLog['segments_deleted'] = $segmentsDeleted;

        // Recordings
        $recordingsDeleted = Recording::where('camera_id', $cameraId)->delete();
        $this->cleanupLog['recordings_deleted'] = $recordingsDeleted;

        // Streams (all statuses)
        $streamsDeleted = Stream::where('camera_id', $cameraId)->delete();
        $this->cleanupLog['streams_deleted'] = $streamsDeleted;

        // Camera analytics daily
        try {
            DB::table('camera_analytics_daily')->where('camera_id', $cameraId)->delete();
            $this->cleanupLog['analytics_deleted'] = true;
        } catch (\Throwable $e) {
            // Table may not exist
            $this->cleanupLog['analytics_deleted'] = false;
        }
    }

    /**
     * Step 5: Clean local file storage.
     *
     * Removes:
     * - Recording directories: /recordings/{mediamtx_path}/
     * - Snapshots: storage/app/public/snapshots/{camera_id}.jpg
     * - Segment cache: storage/app/segment_cache/{camera_id}/
     * - FFmpeg logs: storage/logs/ffmpeg_{camera_id}.log
     */
    private function cleanLocalStorage(Camera $camera, array $localPaths): void
    {
        $cameraId = $camera->id;
        $freedBytes = 0;
        $deletedFiles = 0;

        // 5a. Recording directories (MediaMTX recording files)
        foreach ($localPaths['recording_dirs'] ?? [] as $dir) {
            if (is_dir($dir)) {
                $size = $this->getDirectorySize($dir);
                $freedBytes += $size;
                $deletedFiles += $this->countFilesInDirectory($dir);

                File::deleteDirectory($dir);
                Log::info("CameraCleanupService: deleted recording directory '{$dir}' ({$this->formatBytes($size)}).");
            }
        }

        // 5b. Snapshot file
        $snapshotPath = storage_path("app/public/snapshots/{$cameraId}.jpg");
        if (File::exists($snapshotPath)) {
            $freedBytes += File::size($snapshotPath);
            $deletedFiles++;
            File::delete($snapshotPath);
        }

        // 5c. Segment cache directory
        $segmentCacheDir = storage_path("app/segment_cache/{$cameraId}");
        if (is_dir($segmentCacheDir)) {
            $freedBytes += $this->getDirectorySize($segmentCacheDir);
            $deletedFiles += $this->countFilesInDirectory($segmentCacheDir);
            File::deleteDirectory($segmentCacheDir);
        }

        // 5d. FFmpeg log file
        $ffmpegLog = storage_path("logs/ffmpeg_{$cameraId}.log");
        if (File::exists($ffmpegLog)) {
            $freedBytes += File::size($ffmpegLog);
            $deletedFiles++;
            File::delete($ffmpegLog);
        }

        $this->cleanupLog['local_files_deleted'] = $deletedFiles;
        $this->cleanupLog['local_space_freed'] = $this->formatBytes($freedBytes);
        $this->cleanupLog['local_space_freed_bytes'] = $freedBytes;
    }

    /**
     * Step 6: Clean remote storage using the correct driver per backend.
     *
     * Uses StorageBackendManager to get the proper driver (MinIO, S3, Azure,
     * GCS, NAS, SFTP, etc.) for each storage backend, then deletes all files.
     *
     * Also:
     * - Handles orphan segments (uploaded but no backend_id recorded)
     * - Attempts to delete the camera's entire directory in each backend
     * - Updates storage backend metrics (used_capacity_gb, total_files)
     *
     * @param array $remoteData Collection with keys: by_backend, orphan_paths, bytes_by_backend
     * @param Camera $camera The camera being deleted
     */
    private function cleanRemoteStorage(array $remoteData, Camera $camera): void
    {
        $byBackend = $remoteData['by_backend'] ?? [];
        $orphanPaths = $remoteData['orphan_paths'] ?? [];
        $bytesByBackend = $remoteData['bytes_by_backend'] ?? [];

        $totalFiles = 0;
        $deletedFiles = 0;
        $backendsProcessed = 0;
        $processedBackendIds = [];

        if (empty($byBackend) && empty($orphanPaths)) {
            $this->cleanupLog['remote_files_deleted'] = 0;
            $this->cleanupLog['remote_files_total'] = 0;
            $this->cleanupLog['remote_backends_processed'] = 0;

            // Even with no segments, try to clean the directory from the tenant's default backend
            $this->cleanDefaultBackendDirectory($camera);

            return;
        }

        $storageManager = app(StorageBackendManager::class);

        // ── 6a. Delete files grouped by their known storage backend ──
        foreach ($byBackend as $backendId => $remotePaths) {
            $totalFiles += count($remotePaths);

            try {
                $backend = StorageBackend::find($backendId);

                if (! $backend) {
                    Log::warning("CameraCleanupService: storage backend '{$backendId}' not found, skipping " . count($remotePaths) . ' remote files.');
                    continue;
                }

                // Skip local driver (local files are handled in cleanLocalStorage)
                if ($backend->driver === StorageBackend::DRIVER_LOCAL) {
                    continue;
                }

                // Get the correct driver instance for this backend
                $driver = $storageManager->getDriver($backend);

                $fileCount = count($remotePaths);
                Log::info("CameraCleanupService: cleaning {$fileCount} files from backend '{$backend->name}' ({$backend->driver}).", [
                    'backend_id' => $backendId,
                    'driver' => $backend->driver,
                ]);

                // Delete individual files
                foreach ($remotePaths as $remotePath) {
                    try {
                        $driver->delete($remotePath);
                        $deletedFiles++;
                    } catch (\Throwable $e) {
                        Log::warning("CameraCleanupService: failed to delete '{$remotePath}' from '{$backend->name}'.", [
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                // Delete the camera's directory in this backend
                $this->deleteRemoteCameraDirectory($driver, $camera, $backend);

                // Update backend storage metrics
                $this->updateBackendMetrics($backend, $fileCount, $bytesByBackend[$backendId] ?? 0);

                $processedBackendIds[] = $backendId;
                $backendsProcessed++;
            } catch (\Throwable $e) {
                Log::error("CameraCleanupService: error processing backend '{$backendId}'.", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // ── 6b. Handle orphan segments (uploaded without storage_backend_id) ──
        if (! empty($orphanPaths)) {
            $totalFiles += count($orphanPaths);
            $orphanDeleted = $this->cleanOrphanRemoteFiles($orphanPaths, $camera, $storageManager);
            $deletedFiles += $orphanDeleted;
        }

        // ── 6c. Try to clean directory from default backend (even if no segments were there) ──
        $this->cleanDefaultBackendDirectory($camera, $processedBackendIds);

        $this->cleanupLog['remote_files_deleted'] = $deletedFiles;
        $this->cleanupLog['remote_files_total'] = $totalFiles;
        $this->cleanupLog['remote_backends_processed'] = $backendsProcessed;
        $this->cleanupLog['remote_orphan_files'] = count($orphanPaths);
    }

    /**
     * Handle segments that were uploaded but don't have storage_backend_id recorded.
     *
     * Falls back to the tenant's default backend or global default.
     */
    private function cleanOrphanRemoteFiles(array $orphanPaths, Camera $camera, StorageBackendManager $storageManager): int
    {
        $deleted = 0;

        // Try tenant's default backend, then global default
        $backend = $this->resolveDefaultBackend($camera->tenant_id);

        if (! $backend || $backend->driver === StorageBackend::DRIVER_LOCAL) {
            Log::warning("CameraCleanupService: no remote default backend found for orphan files, skipping " . count($orphanPaths) . ' files.');
            return 0;
        }

        try {
            $driver = $storageManager->getDriver($backend);

            Log::info("CameraCleanupService: cleaning " . count($orphanPaths) . " orphan remote files using default backend '{$backend->name}' ({$backend->driver}).");

            foreach ($orphanPaths as $remotePath) {
                try {
                    $driver->delete($remotePath);
                    $deleted++;
                } catch (\Throwable $e) {
                    Log::debug("CameraCleanupService: orphan file delete failed '{$remotePath}': {$e->getMessage()}");
                }
            }
        } catch (\Throwable $e) {
            Log::warning("CameraCleanupService: failed to process orphan remote files: {$e->getMessage()}");
        }

        return $deleted;
    }

    /**
     * Attempt to clean the camera's directory from the tenant's default backend,
     * even if no segments were tracked for that backend (e.g., files uploaded
     * but records lost, or directory structure left behind).
     *
     * @param array $alreadyProcessedIds Backend IDs already handled in the main loop
     */
    private function cleanDefaultBackendDirectory(Camera $camera, array $alreadyProcessedIds = []): void
    {
        $backend = $this->resolveDefaultBackend($camera->tenant_id);

        if (! $backend) {
            return;
        }

        // Skip if we already processed this backend
        if (in_array($backend->id, $alreadyProcessedIds, true)) {
            return;
        }

        // Skip local driver
        if ($backend->driver === StorageBackend::DRIVER_LOCAL) {
            return;
        }

        try {
            $storageManager = app(StorageBackendManager::class);
            $driver = $storageManager->getDriver($backend);
            $this->deleteRemoteCameraDirectory($driver, $camera, $backend);
        } catch (\Throwable $e) {
            Log::debug("CameraCleanupService: default backend directory cleanup failed: {$e->getMessage()}");
        }
    }

    /**
     * Resolve the default storage backend for a tenant.
     *
     * Priority: tenant-specific default → global default.
     */
    private function resolveDefaultBackend(?string $tenantId): ?StorageBackend
    {
        if ($tenantId) {
            // 1. Try tenant-specific default backend first
            $backend = StorageBackend::where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->where('is_default', true)
                ->first();

            if ($backend) {
                return $backend;
            }
        }

        // 2. Fall back to global default backend
        return StorageBackend::whereNull('tenant_id')
            ->where('is_active', true)
            ->where('is_default', true)
            ->first();
    }

    /**
     * Update backend storage metrics after deleting files.
     *
     * Decrements total_files and used_capacity_gb.
     */
    private function updateBackendMetrics(StorageBackend $backend, int $filesDeleted, int $bytesDeleted): void
    {
        try {
            $newTotalFiles = max(0, ($backend->total_files ?? 0) - $filesDeleted);
            $gbDeleted = $bytesDeleted / (1024 * 1024 * 1024);
            $newUsedGb = max(0, ($backend->used_capacity_gb ?? 0) - $gbDeleted);

            $backend->update([
                'total_files' => $newTotalFiles,
                'used_capacity_gb' => round($newUsedGb, 6),
            ]);

            Log::debug("CameraCleanupService: updated backend '{$backend->name}' metrics: -{$filesDeleted} files, -{$this->formatBytes($bytesDeleted)}.");
        } catch (\Throwable $e) {
            Log::debug("CameraCleanupService: failed to update backend metrics: {$e->getMessage()}");
        }
    }

    /**
     * Try to delete the camera's entire directory tree in the remote backend.
     *
     * This cleans up the directory structure:
     * recordings/tenant_{tenantId}/{cameraSlug}/
     *
     * Uses deleteDirectory() which is implemented by all storage drivers.
     */
    private function deleteRemoteCameraDirectory($driver, Camera $camera, StorageBackend $backend): void
    {
        try {
            // Build the camera directory path used by SegmentWatcherService
            $tenantId = $camera->tenant_id;
            $cameraSlug = $camera->slug;

            if (! $tenantId || ! $cameraSlug) {
                return;
            }

            $cameraDir = "recordings/tenant_{$tenantId}/{$cameraSlug}";

            $driver->deleteDirectory($cameraDir);
            Log::info("CameraCleanupService: deleted remote directory '{$cameraDir}' from backend '{$backend->name}'.");
        } catch (\Throwable $e) {
            // Not critical — some drivers may not support deleteDirectory
            Log::debug("CameraCleanupService: could not delete remote directory from backend '{$backend->name}': {$e->getMessage()}");
        }
    }

    /**
     * Step 7: Clean Redis/cache entries related to this camera.
     */
    private function cleanCacheEntries(string $cameraId): void
    {
        $keysCleared = 0;

        // Transcoding camera cache
        if (Cache::forget("transcoding:cameras:{$cameraId}")) {
            $keysCleared++;
        }

        // FFmpeg transcoder cache
        $cachePrefix = config('transcoding.process.cache_prefix', 'ffmpeg_transcoder:');
        if (Cache::forget("{$cachePrefix}{$cameraId}")) {
            $keysCleared++;
        }

        $this->cleanupLog['cache_keys_cleared'] = $keysCleared;
    }

    // ─── Helpers ──────────────────────────────────────────

    private function getDirectorySize(string $path): int
    {
        $size = 0;

        try {
            foreach (File::allFiles($path) as $file) {
                $size += $file->getSize();
            }
        } catch (\Throwable) {
            // Ignore errors
        }

        return $size;
    }

    private function countFilesInDirectory(string $path): int
    {
        try {
            return count(File::allFiles($path));
        } catch (\Throwable) {
            return 0;
        }
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return round($bytes / 1073741824, 2) . ' GB';
        }
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' B';
    }
}
