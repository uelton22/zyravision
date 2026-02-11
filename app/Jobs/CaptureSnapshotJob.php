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
use Illuminate\Support\Facades\Process;

/**
 * Capture a snapshot from the camera's stream via MediaMTX relay.
 *
 * IMPORTANT: We NEVER connect directly to the camera via RTSP.
 * Instead, we capture from MediaMTX's local RTSP relay to avoid
 * opening multiple RTSP sessions to the camera device.
 *
 * Architecture: Camera → 1 RTSP → MediaMTX → HLS/Recording/Snapshot
 */
class CaptureSnapshotJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 30;

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

        $filename = "snapshots/{$camera->id}.jpg";
        $storagePath = storage_path("app/public/{$filename}");

        // Ensure directory exists
        $dir = dirname($storagePath);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $quality = config('streaming.snapshots.quality', 80);

        // Capture snapshot from MediaMTX's LOCAL RTSP relay — NOT from the camera directly!
        // This uses the existing single RTSP connection that MediaMTX already maintains.
        $mediamtxRtspUrl = $this->getMediaMtxRtspUrl($stream->mediamtx_path);

        $result = Process::timeout(15)->run([
            'ffmpeg', '-y',
            '-rtsp_transport', 'tcp',
            '-i', $mediamtxRtspUrl,
            '-frames:v', '1',
            '-q:v', (string) round((100 - $quality) * 31 / 100),
            $storagePath,
        ]);

        if ($result->successful() && file_exists($storagePath)) {
            $camera->update([
                'snapshot_url' => "/storage/{$filename}",
            ]);

            Log::debug("Snapshot captured for camera {$camera->id} via MediaMTX relay.");
        } else {
            Log::warning("Snapshot capture failed for camera {$camera->id}.", [
                'mediamtx_path' => $stream->mediamtx_path,
                'stderr' => substr($result->errorOutput(), -200),
            ]);
        }
    }

    /**
     * Build the MediaMTX local RTSP relay URL.
     * This connects to MediaMTX (localhost), NOT to the camera.
     */
    private function getMediaMtxRtspUrl(string $mediamtxPath): string
    {
        $user = config('streaming.mediamtx.api_user', 'zyravision');
        $pass = config('streaming.mediamtx.api_pass', 'zyravision_api_secret');
        $host = config('streaming.mediamtx.rtsp_host', 'mediamtx');
        $port = config('streaming.mediamtx.rtsp_port', 8554);

        return "rtsp://{$user}:{$pass}@{$host}:{$port}/{$mediamtxPath}";
    }
}
