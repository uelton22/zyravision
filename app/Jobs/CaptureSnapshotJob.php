<?php

namespace App\Jobs;

use App\Models\Camera;
use App\Models\Stream;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * Dispatches a snapshot capture task to the transcoder container via Redis.
 * The actual FFmpeg execution happens in TranscoderWorkerCommand.
 *
 * IMPORTANT: We NEVER connect directly to the camera via RTSP.
 * Instead, the transcoder captures from MediaMTX's local RTSP relay.
 *
 * Architecture: Camera → 1 RTSP → MediaMTX → HLS/Recording/Snapshot
 */
class CaptureSnapshotJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 10;

    public function __construct(
        public string $cameraId,
    ) {
        $this->onQueue('streaming');
    }

    public function handle(): void
    {
        $camera = Camera::find($this->cameraId);

        if (! $camera || ! $camera->is_active) {
            return;
        }

        $stream = Stream::forCamera($camera->id)->active()->first();

        if (! $stream || ! $stream->mediamtx_path) {
            return;
        }

        $quality = config('streaming.snapshots.quality', 80);

        Redis::rpush('transcoding:ffmpeg_tasks', json_encode([
            'action' => 'capture_snapshot',
            'camera_id' => $this->cameraId,
            'mediamtx_path' => $stream->mediamtx_path,
            'quality' => $quality,
            'dispatched_at' => now()->toIso8601String(),
        ]));

        Log::debug("Snapshot task enviada ao transcoder para camera {$this->cameraId}.");
    }
}
