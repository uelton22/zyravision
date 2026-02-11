<?php

namespace App\Jobs;

use App\Events\StreamFailed;
use App\Events\StreamStarted;
use App\Models\Camera;
use App\Services\Streaming\StreamManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class StartStreamJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 5;

    public function __construct(
        public string $cameraId,
    ) {
        $this->onQueue('streaming');
    }

    public function handle(StreamManager $streamManager): void
    {
        $camera = Camera::find($this->cameraId);

        if (! $camera) {
            Log::warning("StartStreamJob: camera {$this->cameraId} not found.");
            return;
        }

        if (! $camera->is_active) {
            Log::info("StartStreamJob: camera {$this->cameraId} is inactive, skipping.");
            return;
        }

        try {
            $stream = $streamManager->startStream($camera);

            if ($stream->hasError()) {
                event(new StreamFailed($stream, $stream->error_message));
            } else {
                event(new StreamStarted($stream));
            }
        } catch (\Throwable $e) {
            Log::error("StartStreamJob failed for camera {$this->cameraId}.", [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
