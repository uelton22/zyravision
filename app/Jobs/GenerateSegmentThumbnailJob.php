<?php

namespace App\Jobs;

use App\Models\RecordingSegment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * Dispatches a thumbnail generation task to the transcoder container via Redis.
 * The actual FFmpeg execution happens in TranscoderWorkerCommand.
 */
class GenerateSegmentThumbnailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 10;

    public function __construct(
        public string $segmentId,
    ) {
        $this->onQueue('streaming');
    }

    public function handle(): void
    {
        $segment = RecordingSegment::find($this->segmentId);

        if (! $segment) {
            return;
        }

        if ($segment->thumbnail_path) {
            return;
        }

        if (! $segment->local_path) {
            Log::debug("GenerateSegmentThumbnail: local_path ausente para segmento {$this->segmentId}.");
            return;
        }

        Redis::rpush('transcoding:ffmpeg_tasks', json_encode([
            'action' => 'generate_thumbnail',
            'segment_id' => $this->segmentId,
            'dispatched_at' => now()->toIso8601String(),
        ]));

        Log::debug("Thumbnail task enviada ao transcoder para segmento {$this->segmentId}.");
    }
}
