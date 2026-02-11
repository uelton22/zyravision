<?php

namespace App\Jobs;

use App\Events\StreamStopped;
use App\Models\Stream;
use App\Services\Streaming\StreamManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class StopStreamJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $streamId,
    ) {
        $this->onQueue('streaming');
    }

    public function handle(StreamManager $streamManager): void
    {
        $stream = Stream::find($this->streamId);

        if (! $stream) {
            Log::warning("StopStreamJob: stream {$this->streamId} not found.");
            return;
        }

        if ($stream->isStopped()) {
            Log::info("StopStreamJob: stream {$this->streamId} already stopped.");
            return;
        }

        try {
            $streamManager->stopStream($stream);
            event(new StreamStopped($stream));
        } catch (\Throwable $e) {
            Log::error("StopStreamJob failed for stream {$this->streamId}.", [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
