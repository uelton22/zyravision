<?php

namespace App\Jobs;

use App\Models\Camera;
use App\Services\Recording\RecordingManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ScanRecordingSegmentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 120;

    public function __construct()
    {
        $this->onQueue('streaming');
    }

    public function handle(RecordingManager $recordingManager): void
    {
        $cameras = Camera::where('is_recording', true)
            ->with('activeStream')
            ->get();

        foreach ($cameras as $camera) {
            try {
                $recordingManager->scanSegments($camera);
            } catch (\Throwable $e) {
                Log::error("ScanRecordingSegmentsJob: erro ao escanear camera {$camera->id}.", [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
