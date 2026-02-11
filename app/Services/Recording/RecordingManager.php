<?php

namespace App\Services\Recording;

use App\Models\Camera;
use App\Models\Recording;
use App\Models\StorageBackend;
use App\Models\Tenant;
use App\Services\Storage\StorageBackendManager;
use App\Services\Streaming\MediaMtxClient;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class RecordingManager
{
    public function __construct(
        private MediaMtxClient $mediaMtxClient,
        private StorageBackendManager $storageManager,
    ) {}

    /**
     * Enable recording on a camera's active stream path.
     */
    public function startRecording(Camera $camera): Recording
    {
        if (! $camera->relationLoaded('activeStream')) {
            $camera->load('activeStream');
        }

        $stream = $camera->activeStream;
        if (! $stream || ! $stream->isRunning()) {
            throw new \RuntimeException("Camera {$camera->id} nao possui stream ativo.");
        }

        // Check if already recording
        $existing = Recording::forCamera($camera->id)->recording()->first();
        if ($existing) {
            Log::info("Camera {$camera->id} ja esta gravando.", ['recording_id' => $existing->id]);
            return $existing;
        }

        // Check tenant storage quota
        $tenant = $camera->tenant;
        if ($tenant) {
            $usedGb = $this->calculateTenantStorage($tenant);
            $limitGb = $tenant->getStorageLimitGb();
            if ($usedGb >= $limitGb) {
                throw new \RuntimeException("Limite de armazenamento atingido ({$usedGb}GB / {$limitGb}GB).");
            }
        }

        $path = $stream->mediamtx_path;

        // Enable recording via MediaMTX API
        // We only need to set record: true — MediaMTX uses the defaults from mediamtx.yml
        // for recordPath, recordFormat, recordSegmentDuration.
        // This keeps the architecture clean: 1 RTSP → MediaMTX → HLS + Recording
        $success = $this->mediaMtxClient->editPath($path, [
            'record' => true,
        ]);

        if (! $success) {
            throw new \RuntimeException("Falha ao habilitar gravacao no MediaMTX para path '{$path}'.");
        }

        // Resolve storage backend for this recording
        $storageBackend = $this->resolveStorageBackend($camera);

        // Create recording record
        // Storage path matches MediaMTX recordPath default: /recordings/{path}/
        $recording = Recording::create([
            'camera_id' => $camera->id,
            'tenant_id' => $camera->tenant_id,
            'stream_id' => $stream->id,
            'storage_backend_id' => $storageBackend?->id,
            'status' => Recording::STATUS_RECORDING,
            'storage_path' => config('streaming.recording.base_path', '/recordings') . "/{$path}",
            'started_at' => now(),
            'codec' => $camera->codec ?? 'h264',
            'resolution' => $camera->resolution,
            'fps' => $camera->fps,
        ]);

        $camera->update(['is_recording' => true]);

        Log::info("Gravacao iniciada para camera {$camera->id}.", [
            'recording_id' => $recording->id,
            'path' => $path,
        ]);

        return $recording;
    }

    /**
     * Disable recording on a camera's active stream path.
     */
    public function stopRecording(Camera $camera): void
    {
        // Try active stream first, then latest (may be in error/reconnecting state)
        $camera->load(['activeStream', 'latestStream']);
        $stream = $camera->activeStream ?? $camera->latestStream;

        // Disable recording in MediaMTX
        if ($stream && $stream->mediamtx_path) {
            $this->mediaMtxClient->editPath($stream->mediamtx_path, [
                'record' => false,
            ]);
        }

        // Mark active recordings as completed
        $activeRecordings = Recording::forCamera($camera->id)->recording()->get();
        foreach ($activeRecordings as $recording) {
            $this->finalizeRecording($recording);
        }

        $camera->update(['is_recording' => false]);

        Log::info("Gravacao parada para camera {$camera->id}.");
    }

    /**
     * Scan filesystem for recording segments and update the Recording model.
     *
     * Only counts bytes from segment files that belong to THIS recording.
     */
    public function scanSegments(Camera $camera): void
    {
        $recording = Recording::forCamera($camera->id)->recording()->first();
        if (! $recording) {
            return;
        }

        $storagePath = $recording->storage_path;
        if (! $storagePath || ! File::isDirectory($storagePath)) {
            return;
        }

        $totalBytes = 0;
        $files = File::files($storagePath);

        foreach ($files as $file) {
            if (! str_ends_with(strtolower($file->getFilename()), '.mp4')) {
                continue;
            }

            // Match files by modification time within this recording's window
            if ($recording->started_at) {
                $fileMtime = $file->getMTime();
                $windowStart = $recording->started_at->timestamp - 60;
                $windowEnd = time() + 120; // Still recording

                if ($fileMtime < $windowStart) {
                    continue; // File belongs to an older recording
                }
            }

            $totalBytes += $file->getSize();
        }

        $recording->update([
            'file_size_bytes' => $totalBytes,
            'duration_seconds' => $recording->started_at
                ? (int) $recording->started_at->diffInSeconds(now())
                : 0,
        ]);

        // Update tenant storage quota
        if ($camera->tenant_id) {
            $this->updateTenantStorageQuota($camera->tenant_id);
        }
    }

    /**
     * Finalize a recording: calculate file size, duration, and upload to storage backend.
     *
     * Flow:
     * 1. Identify segment files that belong to this recording (by time window)
     * 2. Upload to the configured storage backend (e.g., MinIO, S3)
     * 3. Update recording metadata
     * 4. Optionally cleanup local files after successful upload
     */
    public function finalizeRecording(Recording $recording): void
    {
        $localSegments = $this->getRecordingLocalSegments($recording);
        $totalBytes = 0;

        foreach ($localSegments as $file) {
            $totalBytes += $file->getSize();
        }

        $duration = $recording->started_at
            ? (int) $recording->started_at->diffInSeconds(now())
            : 0;

        // Upload segments to storage backend if one is configured
        $this->uploadToStorageBackend($recording, $localSegments);

        $recording->markCompleted($totalBytes, $duration);
    }

    /**
     * Get local MP4 segment files that belong to a specific recording.
     *
     * @return \SplFileInfo[]
     */
    private function getRecordingLocalSegments(Recording $recording): array
    {
        $storagePath = $recording->storage_path;
        if (! $storagePath || ! File::isDirectory($storagePath)) {
            return [];
        }

        $files = File::files($storagePath);
        $startedAt = $recording->started_at;
        $endedAt = now();

        $matched = [];
        foreach ($files as $file) {
            if (! str_ends_with(strtolower($file->getFilename()), '.mp4')) {
                continue;
            }

            // Filter by time window (60s buffer before, 120s buffer after)
            if ($startedAt) {
                $fileMtime = $file->getMTime();
                $windowStart = $startedAt->timestamp - 60;
                $windowEnd = $endedAt->timestamp + 120;

                if ($fileMtime < $windowStart || $fileMtime > $windowEnd) {
                    continue;
                }
            }

            $matched[] = $file;
        }

        return $matched;
    }

    /**
     * Upload recording segments to the configured storage backend.
     *
     * @param  \SplFileInfo[]  $localSegments
     */
    private function uploadToStorageBackend(Recording $recording, array $localSegments): void
    {
        if (empty($localSegments)) {
            return;
        }

        // Resolve the storage backend
        $backend = null;

        // 1. Use the backend assigned at recording start
        if ($recording->storage_backend_id) {
            $backend = StorageBackend::find($recording->storage_backend_id);
        }

        // 2. Fallback: resolve from tenant
        if (! $backend && $recording->tenant_id) {
            $tenant = Tenant::find($recording->tenant_id);
            if ($tenant) {
                $backend = $this->storageManager->resolveBackend($tenant);
            }
        }

        // 3. Fallback: find any global default
        if (! $backend) {
            $backend = StorageBackend::whereNull('tenant_id')
                ->where('is_default', true)
                ->active()
                ->first();
        }

        if (! $backend) {
            Log::debug('Nenhum storage backend configurado. Gravacao permanece apenas local.', [
                'recording_id' => $recording->id,
            ]);

            return;
        }

        // Skip upload if backend is local driver (already on local disk)
        if ($backend->driver === StorageBackend::DRIVER_LOCAL) {
            Log::debug('Storage backend e local. Nenhum upload necessario.', [
                'recording_id' => $recording->id,
                'backend' => $backend->name,
            ]);

            return;
        }

        try {
            $driver = $this->storageManager->getDriver($backend);
            $camera = $recording->camera;
            $cameraSlug = $camera?->slug ?? $camera?->id ?? 'unknown';
            $date = $recording->started_at?->format('Y/m/d') ?? now()->format('Y/m/d');
            $uploadedCount = 0;
            $uploadedBytes = 0;

            foreach ($localSegments as $file) {
                // Build remote path: tenant_{id}/camera_{slug}/YYYY/MM/DD/filename.mp4
                $remotePath = $this->buildRemotePath($recording, $cameraSlug, $date, $file->getFilename());

                $success = $driver->storeFile($remotePath, $file->getPathname());

                if ($success) {
                    $uploadedCount++;
                    $uploadedBytes += $file->getSize();

                    Log::info('Segmento enviado ao storage backend.', [
                        'recording_id' => $recording->id,
                        'backend' => $backend->name,
                        'driver' => $backend->driver,
                        'file' => $file->getFilename(),
                        'remote_path' => $remotePath,
                        'size_bytes' => $file->getSize(),
                    ]);
                } else {
                    Log::warning('Falha ao enviar segmento ao storage backend.', [
                        'recording_id' => $recording->id,
                        'backend' => $backend->name,
                        'file' => $file->getFilename(),
                    ]);
                }
            }

            if ($uploadedCount > 0) {
                // Update recording with backend reference
                $recording->update([
                    'storage_backend_id' => $backend->id,
                ]);

                // Update backend stats
                $backend->increment('total_files', $uploadedCount);
                $backend->increment('total_bytes_written', $uploadedBytes);

                Log::info("Upload concluido: {$uploadedCount} segmentos enviados ao {$backend->name}.", [
                    'recording_id' => $recording->id,
                    'total_bytes' => $uploadedBytes,
                ]);

                // Cleanup local files after successful upload (keep space free)
                if (config('streaming.recording.cleanup_after_upload', true)) {
                    $this->cleanupLocalSegments($localSegments);
                }
            }
        } catch (\Throwable $e) {
            Log::error('Erro ao enviar gravacao ao storage backend.', [
                'recording_id' => $recording->id,
                'backend' => $backend->name,
                'error' => $e->getMessage(),
            ]);
            // Recording stays local — no data loss
        }
    }

    /**
     * Clean up local segment files after upload.
     * MediaMTX creates files as root, so PHP may lack permission.
     * Falls back to shell command if needed.
     *
     * @param  \SplFileInfo[]  $segments
     */
    private function cleanupLocalSegments(array $segments): void
    {
        foreach ($segments as $file) {
            $path = $file->getPathname();
            if (! File::exists($path)) {
                continue;
            }

            try {
                // Attempt 1: PHP delete
                $deleted = @File::delete($path);

                if (! $deleted && File::exists($path)) {
                    // Attempt 2: Shell rm (may work if container has root access)
                    @exec("rm -f " . escapeshellarg($path) . " 2>/dev/null");
                }

                if (File::exists($path)) {
                    Log::warning('Nao foi possivel remover arquivo local apos upload (permissao). Arquivo ficara para limpeza manual.', [
                        'file' => $path,
                    ]);
                } else {
                    Log::debug('Arquivo local removido apos upload.', [
                        'file' => $path,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('Erro ao tentar remover arquivo local.', [
                    'file' => $path,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Build the remote storage path for a recording segment.
     */
    private function buildRemotePath(Recording $recording, string $cameraSlug, string $date, string $filename): string
    {
        $tenantId = $recording->tenant_id ?? 'global';

        return "recordings/tenant_{$tenantId}/{$cameraSlug}/{$date}/{$filename}";
    }

    /**
     * Resolve the best storage backend for a camera's recording.
     */
    private function resolveStorageBackend(Camera $camera): ?StorageBackend
    {
        // 1. Try tenant-specific default
        if ($camera->tenant_id) {
            $tenant = $camera->tenant;
            if ($tenant) {
                $backend = $this->storageManager->resolveBackend($tenant);
                if ($backend) {
                    return $backend;
                }
            }
        }

        // 2. Try global default (null tenant_id)
        return StorageBackend::whereNull('tenant_id')
            ->where('is_default', true)
            ->active()
            ->first();
    }

    /**
     * Check if a camera is actively recording.
     */
    public function isRecording(Camera $camera): bool
    {
        return $camera->is_recording && Recording::forCamera($camera->id)->recording()->exists();
    }

    /**
     * Calculate total storage used by a tenant (in GB).
     */
    public function calculateTenantStorage(Tenant $tenant): float
    {
        $totalBytes = Recording::forTenant($tenant->id)
            ->whereIn('status', [Recording::STATUS_RECORDING, Recording::STATUS_COMPLETED])
            ->sum('file_size_bytes');

        return round($totalBytes / (1024 * 1024 * 1024), 2);
    }

    /**
     * Update tenant storage quota in QuotaUsage table.
     */
    public function updateTenantStorageQuota(string $tenantId): void
    {
        $tenant = Tenant::find($tenantId);
        if (! $tenant) {
            return;
        }

        $usedGb = $this->calculateTenantStorage($tenant);
        $quota = $tenant->getQuota('storage_gb');

        if ($quota) {
            $quota->update([
                'current_usage' => round($usedGb, 2),
                'last_calculated_at' => now(),
            ]);
        } else {
            $tenant->quotaUsages()->create([
                'resource' => 'storage_gb',
                'current_usage' => round($usedGb, 2),
                'limit_value' => $tenant->getStorageLimitGb(),
                'last_calculated_at' => now(),
            ]);
        }
    }

    /**
     * Run retention cleanup: delete recordings older than retention_days.
     */
    public function cleanupExpiredRecordings(Tenant $tenant): int
    {
        $retentionDays = $tenant->storage_retention_days
            ?? $tenant->currentPlan()?->retention_days
            ?? config('streaming.recording.default_retention_days', 30);

        $cutoff = now()->subDays($retentionDays);

        $expired = Recording::forTenant($tenant->id)
            ->where('status', Recording::STATUS_COMPLETED)
            ->where('started_at', '<', $cutoff)
            ->get();

        $count = 0;
        foreach ($expired as $recording) {
            // Delete files from filesystem
            if ($recording->storage_path && File::isDirectory($recording->storage_path)) {
                File::deleteDirectory($recording->storage_path);
            }

            $recording->update(['status' => Recording::STATUS_DELETED]);
            $recording->delete(); // soft-delete
            $count++;
        }

        if ($count > 0) {
            $this->updateTenantStorageQuota($tenant->id);
        }

        return $count;
    }

    /**
     * Get the recording storage path for a MediaMTX path.
     */
    public function getStoragePath(string $mediamtxPath): string
    {
        return config('streaming.recording.base_path', '/recordings') . "/{$mediamtxPath}";
    }
}
