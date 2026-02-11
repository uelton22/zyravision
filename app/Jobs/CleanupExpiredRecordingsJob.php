<?php

namespace App\Jobs;

use App\Models\Tenant;
use App\Services\Recording\RecordingManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CleanupExpiredRecordingsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 300;

    public function __construct()
    {
        $this->onQueue('streaming');
    }

    public function handle(RecordingManager $recordingManager): void
    {
        $tenants = Tenant::active()->get();

        foreach ($tenants as $tenant) {
            try {
                $deleted = $recordingManager->cleanupExpiredRecordings($tenant);
                if ($deleted > 0) {
                    Log::info("CleanupExpiredRecordingsJob: {$deleted} gravacoes removidas do tenant {$tenant->id}.");
                }
            } catch (\Throwable $e) {
                Log::error("CleanupExpiredRecordingsJob: erro para tenant {$tenant->id}.", [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
