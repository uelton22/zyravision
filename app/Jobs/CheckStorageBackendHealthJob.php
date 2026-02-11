<?php

namespace App\Jobs;

use App\Models\StorageBackend;
use App\Services\Storage\StorageBackendManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckStorageBackendHealthJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 120;

    public function __construct(
        private ?string $backendId = null,
    ) {}

    public function handle(StorageBackendManager $manager): void
    {
        if ($this->backendId) {
            // Check a specific backend
            $backend = StorageBackend::find($this->backendId);
            if ($backend && $backend->is_active) {
                $this->checkBackend($manager, $backend);
            }

            return;
        }

        // Check all active backends
        $backends = StorageBackend::where('is_active', true)->get();
        $checked = 0;
        $degraded = 0;
        $offline = 0;

        foreach ($backends as $backend) {
            $result = $this->checkBackend($manager, $backend);
            $checked++;

            if ($result['status'] === StorageBackend::HEALTH_DEGRADED) {
                $degraded++;
            } elseif ($result['status'] === StorageBackend::HEALTH_OFFLINE) {
                $offline++;
            }
        }

        Log::info('Storage backend health check completed.', [
            'checked' => $checked,
            'degraded' => $degraded,
            'offline' => $offline,
        ]);
    }

    private function checkBackend(StorageBackendManager $manager, StorageBackend $backend): array
    {
        try {
            $result = $manager->testConnection($backend);

            // Log status changes
            $previousStatus = $backend->getOriginal('health_status');
            if ($previousStatus !== $result['status'] && $previousStatus !== StorageBackend::HEALTH_UNKNOWN) {
                Log::warning("Storage backend health changed: {$backend->name}", [
                    'backend_id' => $backend->id,
                    'tenant_id' => $backend->tenant_id,
                    'previous' => $previousStatus,
                    'current' => $result['status'],
                    'message' => $result['message'],
                ]);
            }

            return $result;
        } catch (\Throwable $e) {
            Log::error("Storage backend health check failed for {$backend->name}: {$e->getMessage()}", [
                'backend_id' => $backend->id,
            ]);

            $backend->update([
                'health_status' => StorageBackend::HEALTH_OFFLINE,
                'last_health_check_at' => now(),
                'health_check_message' => $e->getMessage(),
            ]);

            return [
                'status' => StorageBackend::HEALTH_OFFLINE,
                'message' => $e->getMessage(),
            ];
        }
    }
}
