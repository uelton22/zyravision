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

class UpdateStorageQuotaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 120;

    public function __construct()
    {
        $this->onQueue('default');
    }

    public function handle(RecordingManager $recordingManager): void
    {
        $tenants = Tenant::active()->get();

        foreach ($tenants as $tenant) {
            try {
                $recordingManager->updateTenantStorageQuota($tenant->id);
            } catch (\Throwable $e) {
                Log::error("UpdateStorageQuotaJob: erro para tenant {$tenant->id}.", [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
