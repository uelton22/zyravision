<?php

namespace App\Jobs;

use App\Events\RecordingStopped;
use App\Models\Camera;
use App\Services\Recording\RecordingManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class StopRecordingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $backoff = 3;

    public function __construct(
        public string $cameraId,
    ) {
        $this->onQueue('streaming');
    }

    public function handle(RecordingManager $recordingManager): void
    {
        $camera = Camera::find($this->cameraId);

        if (! $camera) {
            Log::warning("StopRecordingJob: camera {$this->cameraId} nao encontrada.");
            return;
        }

        try {
            $recordingManager->stopRecording($camera);
            event(new RecordingStopped($camera));
        } catch (\Throwable $e) {
            Log::error("StopRecordingJob falhou para camera {$this->cameraId}.", [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
