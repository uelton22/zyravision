<?php

namespace App\Jobs;

use App\Events\RecordingStarted;
use App\Models\Camera;
use App\Services\Recording\RecordingManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class StartRecordingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 5;

    public function __construct(
        public string $cameraId,
    ) {
        $this->onQueue('streaming');
    }

    public function handle(RecordingManager $recordingManager): void
    {
        $camera = Camera::find($this->cameraId);

        if (! $camera || ! $camera->is_active) {
            Log::warning("StartRecordingJob: camera {$this->cameraId} nao encontrada ou inativa.");
            return;
        }

        try {
            $recording = $recordingManager->startRecording($camera);
            event(new RecordingStarted($recording));
        } catch (\Throwable $e) {
            Log::error("StartRecordingJob falhou para camera {$this->cameraId}.", [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
