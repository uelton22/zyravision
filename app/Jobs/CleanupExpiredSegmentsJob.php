<?php

namespace App\Jobs;

use App\Models\RecordingSegment;
use App\Models\RecordingSetting;
use App\Models\StorageBackend;
use App\Services\Storage\StorageBackendManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class CleanupExpiredSegmentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(StorageBackendManager $storageManager): void
    {
        // Get all distinct tenant IDs
        $tenantIds = RecordingSegment::distinct()->pluck('tenant_id');

        $totalDeleted = 0;
        $totalFreed = 0;

        foreach ($tenantIds as $tenantId) {
            $settings = RecordingSetting::forTenant($tenantId);
            $retentionDays = $settings->retention_days;

            if ($retentionDays <= 0) {
                continue; // No retention limit
            }

            $cutoff = now()->subDays($retentionDays);

            $expiredSegments = RecordingSegment::query()
                ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
                ->when(! $tenantId, fn ($q) => $q->whereNull('tenant_id'))
                ->where('starts_at', '<', $cutoff)
                ->get();

            foreach ($expiredSegments as $segment) {
                try {
                    $totalFreed += $segment->file_size_bytes;

                    // Delete from remote storage
                    if ($segment->remote_path && $segment->storage_backend_id) {
                        $backend = StorageBackend::find($segment->storage_backend_id);
                        if ($backend) {
                            $driver = $storageManager->getDriver($backend);
                            $driver->delete($segment->remote_path);
                        }
                    }

                    // Delete local file
                    if ($segment->local_path && File::exists($segment->local_path)) {
                        @File::delete($segment->local_path);
                    }

                    // Soft delete the record
                    $segment->delete();
                    $totalDeleted++;
                } catch (\Throwable $e) {
                    Log::error('Erro ao limpar segmento expirado.', [
                        'segment_id' => $segment->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        if ($totalDeleted > 0) {
            Log::info('Limpeza de segmentos expirados concluida.', [
                'deleted' => $totalDeleted,
                'freed_mb' => round($totalFreed / 1024 / 1024, 2),
            ]);
        }
    }
}
