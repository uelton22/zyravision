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
     * Hard reset a stream — removes path from MediaMTX completely,
     * waits briefly, then re-registers it with a fresh connection.
     *
     * This is the automated equivalent of the "Force Reset" button.
     * Used when soft recovery (waiting for MediaMTX reconnection) fails.
     *
     * IMPORTANT: This forces MediaMTX to close the existing RTSP connection
     * and open a brand new one. Use sparingly to avoid stressing cameras.
     */
    public function hardResetStream(Stream $stream): bool
    {
        $camera = $stream->camera;

        if (! $camera || ! $camera->is_active) {
            Log::warning("hardResetStream: camera not active for stream {$stream->id}.");
            return false;
        }

        $path = $stream->mediamtx_path;
        $rtspUrl = $stream->rtsp_input_url;

        if (! $path || ! $rtspUrl) {
            Log::error("hardResetStream: missing path or RTSP URL for stream {$stream->id}.");
            return false;
        }

        Log::info("hardResetStream: performing hard reset for stream {$stream->id}.", [
            'path' => $path,
            'camera_id' => $camera->id,
        ]);

        // Step 1: Remove path completely from MediaMTX (kills RTSP connection)
        $this->mediaMtxClient->removePath($path);

        // Step 2: Brief delay to allow cleanup
        $delay = config('streaming.recovery.hard_reset_delay_seconds', 3);
        sleep($delay);

        // Step 3: Re-register path with fresh RTSP connection
        $config = [
            'source' => $rtspUrl,
            'rtspTransport' => $camera->transport ?? 'tcp',
            'sourceOnDemand' => false,
        ];

        $registered = $this->mediaMtxClient->addPath($path, $config);

        if (! $registered) {
            Log::error("hardResetStream: failed to re-register path {$path}.");
            return false;
        }

        // Step 4: Mark stream as reconnecting
        $stream->update([
            'status' => Stream::STATUS_RECONNECTING,
            'error_message' => null,
            'last_health_check' => now(),
        ]);

        // Step 5: Wait and check if source connected
        sleep(2);
        $ready = $this->mediaMtxClient->isPathReady($path);

        if ($ready) {
            $stream->markActive();
            $camera->update(['is_online' => true, 'last_seen_at' => now()]);

            // Re-enable continuous recording if configured
            if ($camera->continuous_recording) {
                $this->mediaMtxClient->editPath($path, ['record' => true]);
                $camera->update(['is_recording' => true]);
            }

            Log::info("hardResetStream: stream {$stream->id} recovered successfully via hard reset.");
            return true;
        }

        // Not ready yet but path was re-registered — monitor will pick it up
        Log::info("hardResetStream: path re-registered for stream {$stream->id}, waiting for MediaMTX to connect.");
        return true;
    }

    /**
     * Get the current status of a stream from MediaMTX.
     *
     * IMPORTANT: This method does NOT mark streams as error on a single check.
     * Transient disconnections are normal — only the MonitorStreamsJob should
     * mark streams as error after multiple consecutive failed checks.
     * This prevents a single status poll from permanently breaking a stream.
     */
    public function getStreamStatus(Stream $stream): array
    {
        $metrics = $this->mediaMtxClient->getPathMetrics($stream->mediamtx_path);
        $ready = $metrics['ready'] ?? false;

        // Update status based on real state
        if ($stream->isRunning() && ! $ready) {
            // Source is currently not ready — mark as reconnecting (NOT error)
            // The MonitorStreamsJob will handle error escalation after multiple checks
            if ($stream->status === Stream::STATUS_ACTIVE &&
                $stream->started_at &&
                $stream->started_at->diffInSeconds(now()) > 30) {
                $stream->markReconnecting();
                $stream->camera?->update(['is_online' => false]);
                Log::info("getStreamStatus: stream {$stream->id} source dropped, marked as reconnecting (monitor will handle recovery).");
            }
        } elseif ($stream->status === Stream::STATUS_STARTING && $ready) {
            $stream->markActive();
            $stream->camera?->update(['is_online' => true, 'last_seen_at' => now()]);
        } elseif ($stream->status === Stream::STATUS_RECONNECTING && $ready) {
            // Stream recovered on its own (MediaMTX reconnected)
            $stream->markActive();
            $stream->camera?->update(['is_online' => true, 'last_seen_at' => now()]);
            $stream->updateHealthCheck([
                'reconnect_count' => 0,
                'recovery_attempt' => 0,
                'hard_reset_count' => 0,
            ]);
            Log::info("getStreamStatus: stream {$stream->id} recovered automatically.");
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
