<?php

namespace App\Services\Streaming;

use App\Models\Camera;
use App\Models\Recording;
use App\Models\Stream;
use App\Services\Camera\StreamUrlResolver;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class StreamManager
{
    public function __construct(
        private MediaMtxClient $mediaMtxClient,
        private StreamUrlResolver $streamUrlResolver,
    ) {}

    /**
     * Start a stream for a camera.
     * Registers the RTSP source in MediaMTX and creates a Stream record.
     */
    public function startStream(Camera $camera): Stream
    {
        // Check for existing active stream
        $existing = Stream::forCamera($camera->id)->running()->first();
        if ($existing) {
            Log::info("Stream already running for camera {$camera->id}", ['stream_id' => $existing->id]);
            return $existing;
        }

        $path = $this->generateMediaMtxPath($camera);
        $rtspUrl = $this->resolveRtspUrl($camera);

        // Remove any old stopped/error streams with same path to avoid unique constraint conflict
        Stream::where('mediamtx_path', $path)
            ->whereIn('status', [Stream::STATUS_STOPPED, Stream::STATUS_ERROR])
            ->delete();

        // Also ensure the path is clean in MediaMTX
        $this->mediaMtxClient->removePath($path);

        // Create stream record
        $stream = Stream::create([
            'camera_id' => $camera->id,
            'tenant_id' => $camera->tenant_id,
            'status' => Stream::STATUS_STARTING,
            'mediamtx_path' => $path,
            'rtsp_input_url' => $rtspUrl,
            'hls_url' => $this->buildHlsUrl($path),
            'webrtc_url' => $this->buildWebrtcUrl($path),
        ]);

        // Register path in MediaMTX with RTSP source
        // Use rtspTransport (not sourceProtocol) — this is the correct API field
        $config = [
            'source' => $rtspUrl,
            'rtspTransport' => $camera->transport ?? 'tcp',
            'sourceOnDemand' => false,
        ];

        $registered = $this->mediaMtxClient->addPath($path, $config);

        if (! $registered) {
            $stream->markError('Failed to register path in MediaMTX.');
            $camera->update(['is_online' => false]);
            Log::error("Failed to register MediaMTX path for camera {$camera->id}.", [
                'path' => $path,
                'rtsp_url' => $rtspUrl,
            ]);

            return $stream;
        }

        // Wait briefly then verify if source is connecting
        sleep(2);
        $ready = $this->mediaMtxClient->isPathReady($path);

        if ($ready) {
            $stream->markActive();
            $camera->update(['is_online' => true, 'last_seen_at' => now()]);
        } else {
            // Not ready yet, but path was registered — mark as starting
            $stream->update(['status' => Stream::STATUS_STARTING, 'started_at' => now()]);
        }

        // Enable continuous recording if configured
        if ($camera->continuous_recording && $ready) {
            $this->mediaMtxClient->editPath($path, ['record' => true]);
            $camera->update(['is_recording' => true]);
            Log::info("Gravacao continua habilitada para camera {$camera->id}.", ['path' => $path]);
        }

        Log::info("Stream started for camera {$camera->id}.", [
            'stream_id' => $stream->id,
            'path' => $path,
            'ready' => $ready,
            'continuous_recording' => $camera->continuous_recording,
        ]);

        return $stream;
    }

    /**
     * Stop an active stream.
     */
    public function stopStream(Stream $stream): void
    {
        $camera = $stream->camera;

        // If camera is recording, disable recording first
        if ($camera && $camera->is_recording) {
            $this->mediaMtxClient->editPath($stream->mediamtx_path, ['record' => false]);
            Recording::forCamera($camera->id)
                ->recording()
                ->each(fn (Recording $r) => $r->markCompleted());
            $camera->update(['is_recording' => false]);
            Log::info("Recording stopped automatically when stream stopped.", [
                'camera_id' => $camera->id,
            ]);
        }

        $this->mediaMtxClient->removePath($stream->mediamtx_path);
        $stream->markStopped();

        // Update camera status
        if ($camera) {
            $hasOtherActive = Stream::forCamera($camera->id)
                ->where('id', '!=', $stream->id)
                ->running()
                ->exists();

            if (! $hasOtherActive) {
                $camera->update(['is_online' => false]);
            }
        }

        Log::info("Stream stopped.", [
            'stream_id' => $stream->id,
            'path' => $stream->mediamtx_path,
        ]);
    }

    /**
     * Restart a stream (stop + start).
     */
    public function restartStream(Stream $stream): Stream
    {
        $camera = $stream->camera;
        $this->stopStream($stream);

        return $this->startStream($camera);
    }

    /**
     * Get the current status of a stream from MediaMTX.
     */
    public function getStreamStatus(Stream $stream): array
    {
        $metrics = $this->mediaMtxClient->getPathMetrics($stream->mediamtx_path);
        $ready = $metrics['ready'] ?? false;

        // Update status based on real state
        if ($stream->isRunning() && ! $ready) {
            // Stream was active but source dropped
            if ($stream->started_at && $stream->started_at->diffInSeconds(now()) > 30) {
                $stream->markError('Source dropped — stream is no longer receiving data.');
                // Camera is no longer receiving data — mark as offline
                $stream->camera?->update(['is_online' => false]);
            }
        } elseif ($stream->status === Stream::STATUS_STARTING && $ready) {
            $stream->markActive();
            $stream->camera?->update(['is_online' => true, 'last_seen_at' => now()]);
        }

        return [
            'stream_id' => $stream->id,
            'status' => $stream->fresh()->status,
            'hls_url' => $stream->hls_url,
            'webrtc_url' => $stream->webrtc_url,
            'started_at' => $stream->started_at?->toDateTimeString(),
            'uptime_seconds' => $stream->started_at ? $stream->started_at->diffInSeconds(now()) : 0,
            'mediamtx' => $metrics,
        ];
    }

    /**
     * Generate a unique MediaMTX path for a camera.
     */
    public function generateMediaMtxPath(Camera $camera): string
    {
        $shortId = substr($camera->id, 0, 8);
        $slug = Str::slug($camera->name);

        return "cam_{$shortId}_{$slug}";
    }

    /**
     * Build HLS URL for the frontend.
     */
    public function buildHlsUrl(string $path): string
    {
        $baseUrl = config('streaming.hls.base_url', '/hls');

        return "{$baseUrl}/{$path}/index.m3u8";
    }

    /**
     * Build WebRTC URL for the frontend.
     */
    public function buildWebrtcUrl(string $path): string
    {
        return "/webrtc/{$path}";
    }

    /**
     * Resolve the RTSP URL for a camera using existing URL builders.
     */
    private function resolveRtspUrl(Camera $camera): string
    {
        $urls = $this->streamUrlResolver->resolve($camera);

        $rtspUrl = $urls['rtsp_url'] ?? null;

        if (! $rtspUrl) {
            throw new \RuntimeException("Could not resolve RTSP URL for camera {$camera->id}.");
        }

        return $rtspUrl;
    }
}
