<?php

namespace App\Services\Streaming;

use App\Models\Camera;
use App\Models\Recording;
use App\Models\Stream;
use App\Services\Camera\StreamUrlResolver;
use App\Services\Recording\RecordingManager;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class StreamManager
{
    public function __construct(
        private MediaMtxClient $mediaMtxClient,
        private StreamUrlResolver $streamUrlResolver,
        private RecordingManager $recordingManager,
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
        // readTimeout/writeTimeout give MediaMTX more time before dropping the connection
        $config = [
            'source' => $rtspUrl,
            'rtspTransport' => $camera->transport ?? 'tcp',
            'sourceOnDemand' => false,
            'readTimeout' => '30s',
            'writeTimeout' => '30s',
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

        // Enable continuous recording if configured.
        // Uses RecordingManager to correctly handle quality settings (MediaMTX vs FFmpeg).
        // If not ready yet, MonitorStreamsJob will enable recording when stream becomes active.
        if ($camera->continuous_recording) {
            if ($ready) {
                try {
                    $camera->load('activeStream');
                    $this->recordingManager->startRecording($camera);
                    Log::info("Gravacao continua habilitada para camera {$camera->id}.", ['path' => $path]);
                } catch (\Throwable $e) {
                    Log::warning("Falha ao iniciar gravacao continua para camera {$camera->id}: {$e->getMessage()}");
                }
            } else {
                Log::info("Gravacao continua pendente para camera {$camera->id} (stream ainda nao esta pronto — sera ativada pelo monitor).");
            }
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

        // If camera is recording, stop properly (handles both MediaMTX and FFmpeg)
        if ($camera && $camera->is_recording) {
            try {
                $this->recordingManager->stopRecording($camera);
            } catch (\Throwable $e) {
                Log::warning("Falha ao parar gravacao durante stop do stream: {$e->getMessage()}", [
                    'camera_id' => $camera->id,
                ]);
                // Fallback: direct stop
                $this->mediaMtxClient->editPath($stream->mediamtx_path, ['record' => false]);
                $camera->update(['is_recording' => false]);
            }
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
     * then re-registers it with a fresh RTSP connection.
     *
     * This is the automated equivalent of the "Force Reset" button.
     *
     * NON-BLOCKING: This method does NOT sleep/wait for readiness.
     * The MonitorStreamsJob will detect recovery on the next cycle.
     * This keeps the worker fast and prevents blocking when multiple
     * cameras need recovery simultaneously.
     */
    public function hardResetStream(Stream $stream): bool
    {
        $camera = $stream->camera;

        if (! $camera || ! $camera->is_active) {
            Log::warning("hardResetStream: camera not active for stream {$stream->id}.");
            return false;
        }

        $path = $stream->mediamtx_path;

        if (! $path) {
            Log::error("hardResetStream: missing path for stream {$stream->id}.");
            return false;
        }

        // Resolve fresh RTSP URL from camera (not the stale one stored in stream)
        // This ensures recovery works even if camera credentials/IP changed
        $rtspUrl = $stream->rtsp_input_url;
        try {
            $freshUrl = $this->streamUrlResolver->resolve($camera)['rtsp_url'] ?? null;
            if ($freshUrl) {
                if ($freshUrl !== $rtspUrl) {
                    Log::info("hardResetStream: RTSP URL changed for stream {$stream->id}, updating.", [
                        'old_masked' => preg_replace('/\/\/(.+?)@/', '//***@', $rtspUrl),
                        'new_masked' => preg_replace('/\/\/(.+?)@/', '//***@', $freshUrl),
                    ]);
                    $stream->update(['rtsp_input_url' => $freshUrl]);
                }
                $rtspUrl = $freshUrl;
            }
        } catch (\Throwable $e) {
            Log::warning("hardResetStream: failed to resolve fresh RTSP URL, using stored URL.", [
                'error' => $e->getMessage(),
            ]);
        }

        if (! $rtspUrl) {
            Log::error("hardResetStream: no RTSP URL available for stream {$stream->id}.");
            return false;
        }

        Log::info("hardResetStream: performing hard reset for stream {$stream->id}.", [
            'path' => $path,
            'camera_id' => $camera->id,
            'camera' => $camera->name,
        ]);

        // Step 1: Remove path completely from MediaMTX (kills RTSP connection)
        $this->mediaMtxClient->removePath($path);

        // Step 2: Brief pause (500ms) to let MediaMTX clean up internal state
        usleep(500000);

        // Step 3: Re-register path with fresh RTSP connection
        // Include readTimeout to give MediaMTX more time to connect to slow cameras
        $config = [
            'source' => $rtspUrl,
            'rtspTransport' => $camera->transport ?? 'tcp',
            'sourceOnDemand' => false,
            'readTimeout' => '30s',
            'writeTimeout' => '30s',
        ];

        $registered = $this->mediaMtxClient->addPath($path, $config);

        if (! $registered) {
            Log::error("hardResetStream: failed to re-register path {$path}.");
            return false;
        }

        // Step 4: Mark stream as reconnecting (monitor will check readiness)
        $stream->update([
            'status' => Stream::STATUS_RECONNECTING,
            'error_message' => null,
            'last_health_check' => now(),
        ]);

        // Step 5: Finalize any stale recordings from before the reset
        if ($camera->continuous_recording) {
            try {
                Recording::forCamera($camera->id)
                    ->recording()
                    ->each(fn (Recording $r) => $r->markCompleted());
                $camera->update(['is_recording' => false]);
            } catch (\Throwable $e) {
                Log::warning("hardResetStream: falha ao limpar recordings stale: {$e->getMessage()}", [
                    'camera_id' => $camera->id,
                ]);
            }
        }

        Log::info("hardResetStream: path re-registrado para stream {$stream->id}, monitor verificara no proximo ciclo.");
        return true;
    }

    /**
     * Get the current status of a stream from MediaMTX.
     *
     * READ-ONLY: This method only READS the current state. It does NOT
     * modify stream status to avoid race conditions with MonitorStreamsJob.
     *
     * Only the MonitorStreamsJob should change stream status — it has the full
     * context (timing, counters, recovery phase) to make correct decisions.
     *
     * Exception: If the stream is ready but was STARTING/RECONNECTING,
     * we upgrade to ACTIVE (positive transition only — never degrade).
     */
    public function getStreamStatus(Stream $stream): array
    {
        $metrics = $this->mediaMtxClient->getPathMetrics($stream->mediamtx_path);
        $ready = $metrics['ready'] ?? false;

        // Only allow POSITIVE transitions (not-ready → ready)
        // The MonitorStreamsJob handles negative transitions (ready → not-ready)
        if ($ready && in_array($stream->status, [Stream::STATUS_STARTING, Stream::STATUS_RECONNECTING])) {
            $stream->markActive();
            $stream->camera?->update(['is_online' => true, 'last_seen_at' => now()]);
            $stream->updateHealthCheck([
                'not_ready_since' => null,
                'hard_reset_count' => 0,
                'last_hard_reset_at' => null,
            ]);
            Log::info("getStreamStatus: stream {$stream->id} detectado como pronto, marcado ACTIVE.");
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
