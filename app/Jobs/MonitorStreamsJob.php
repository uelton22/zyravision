<?php

namespace App\Jobs;

use App\Models\Stream;
use App\Services\Streaming\MediaMtxClient;
use App\Services\Streaming\StreamManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Monitor active streams and manage recovery.
 *
 * ARCHITECTURE PRINCIPLE: 1 RTSP connection per camera.
 * - Camera → 1 RTSP → MediaMTX → HLS + Recording + Snapshot
 * - MediaMTX manages its own RTSP reconnections internally
 * - We ONLY re-register a path if it was completely removed from MediaMTX
 * - We NEVER call addPath() repeatedly — that forces MediaMTX to close and
 *   reopen the RTSP connection, stressing the camera
 */
class MonitorStreamsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public bool $failOnTimeout = true;
    public int $timeout = 120;

    public function __construct()
    {
        $this->onQueue('streaming');
    }

    public function handle(MediaMtxClient $client, StreamManager $streamManager): void
    {
        // 1. Monitor running streams (starting, active, reconnecting)
        $this->monitorRunningStreams($client);

        // 2. Auto-recover error streams with conservative backoff
        $this->recoverErrorStreams($client);
    }

    private function monitorRunningStreams(MediaMtxClient $client): void
    {
        $streams = Stream::running()->with('camera')->get();

        if ($streams->isEmpty()) {
            return;
        }

        foreach ($streams as $stream) {
            try {
                $this->checkStream($stream, $client);
            } catch (\Throwable $e) {
                Log::error("MonitorStreamsJob: error checking stream {$stream->id}.", [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Auto-recover streams in error state with CONSERVATIVE backoff.
     *
     * IMPORTANT: We use progressive backoff to avoid flooding the camera
     * with RTSP connection attempts. Cameras have limited session capacity
     * and may enter "silent protection" mode if stressed.
     */
    private function recoverErrorStreams(MediaMtxClient $client): void
    {
        $errorStreams = Stream::where('status', Stream::STATUS_ERROR)
            ->with('camera')
            ->get();

        foreach ($errorStreams as $stream) {
            if (! $stream->camera || ! $stream->camera->is_active) {
                continue;
            }

            try {
                $recoveryAttempt = $stream->metadata['recovery_attempt'] ?? 0;
                $maxRecoveryAttempts = config('streaming.limits.reconnect_attempts', 10);

                // If exceeded max recovery attempts, stop trying
                if ($recoveryAttempt >= $maxRecoveryAttempts) {
                    continue;
                }

                // Progressive backoff: 60s, 120s, 180s, 300s, 600s... max 15min
                $backoffSeconds = min(60 + ($recoveryAttempt * 60), 900);

                $lastAttempt = $stream->metadata['last_reconnect_at'] ?? null;
                if ($lastAttempt && now()->diffInSeconds($lastAttempt) < $backoffSeconds) {
                    continue;
                }

                Log::info("MonitorStreamsJob: auto-recovering stream {$stream->id} (attempt #{$recoveryAttempt}/{$maxRecoveryAttempts}, next in {$backoffSeconds}s).");

                // Check if path still exists in MediaMTX
                $pathInfo = $client->getPath($stream->mediamtx_path);

                if ($pathInfo === null) {
                    // Path was removed from MediaMTX — re-register it ONCE
                    Log::info("MonitorStreamsJob: path {$stream->mediamtx_path} not found, re-registering.");

                    $registered = $client->addPath($stream->mediamtx_path, [
                        'source' => $stream->rtsp_input_url,
                        'rtspTransport' => $stream->camera->transport ?? 'tcp',
                        'sourceOnDemand' => false,
                    ]);

                    if (! $registered) {
                        Log::error("MonitorStreamsJob: failed to re-register path {$stream->mediamtx_path}.");
                    }
                }
                // If path exists, MediaMTX is already managing reconnection — don't interfere

                // Update stream metadata
                $stream->update([
                    'status' => Stream::STATUS_RECONNECTING,
                    'error_message' => null,
                    'metadata' => array_merge($stream->metadata ?? [], [
                        'reconnect_count' => 0,
                        'last_reconnect_at' => now()->toDateTimeString(),
                        'recovery_attempt' => $recoveryAttempt + 1,
                    ]),
                ]);
            } catch (\Throwable $e) {
                Log::error("MonitorStreamsJob: recovery failed for stream {$stream->id}.", [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function checkStream(Stream $stream, MediaMtxClient $client): void
    {
        $pathInfo = $client->getPath($stream->mediamtx_path);

        // Path doesn't exist at all in MediaMTX
        if ($pathInfo === null) {
            $reconnects = $stream->metadata['reconnect_count'] ?? 0;
            $maxReconnects = 3; // Only 3 attempts for missing paths

            if ($reconnects < $maxReconnects) {
                Log::warning("MonitorStreamsJob: path {$stream->mediamtx_path} missing, re-registering (attempt {$reconnects}/{$maxReconnects}).");

                $stream->markReconnecting();
                $stream->update([
                    'metadata' => array_merge($stream->metadata ?? [], [
                        'reconnect_count' => $reconnects + 1,
                        'last_reconnect_at' => now()->toDateTimeString(),
                    ]),
                ]);

                // Re-register path in MediaMTX ONLY because it was removed
                if ($stream->rtsp_input_url) {
                    $client->addPath($stream->mediamtx_path, [
                        'source' => $stream->rtsp_input_url,
                        'rtspTransport' => $stream->camera?->transport ?? 'tcp',
                        'sourceOnDemand' => false,
                    ]);
                }
            } else {
                Log::warning("MonitorStreamsJob: path {$stream->mediamtx_path} missing after {$maxReconnects} re-registers, marking as error.");
                $stream->markError('Path removido do MediaMTX — stream não recuperável.');
                $stream->camera?->update(['is_online' => false]);
            }

            return;
        }

        // Path exists — check if source is ready
        $ready = $pathInfo['ready'] ?? false;

        if ($ready) {
            // Stream is healthy
            if ($stream->status !== Stream::STATUS_ACTIVE) {
                $stream->markActive();
                Log::info("MonitorStreamsJob: stream {$stream->id} is now active.");
            }

            $stream->updateHealthCheck([
                'readers' => count($pathInfo['readers'] ?? []),
                'bytesReceived' => $pathInfo['bytesReceived'] ?? 0,
                'reconnect_count' => 0,
                'recovery_attempt' => 0,
            ]);

            // Camera is receiving data — mark as online
            $stream->camera?->update([
                'is_online' => true,
                'last_seen_at' => now(),
            ]);

            return;
        }

        // Path exists but source is NOT ready — MediaMTX is handling reconnection
        // We DON'T call addPath() again — that would interrupt MediaMTX's own retry logic
        $reconnects = $stream->metadata['reconnect_count'] ?? 0;
        $maxReconnects = config('streaming.limits.reconnect_attempts', 10);

        // Camera is not receiving data
        $stream->camera?->update(['is_online' => false]);

        if ($stream->status !== Stream::STATUS_RECONNECTING) {
            $stream->markReconnecting();
        }

        $stream->update([
            'metadata' => array_merge($stream->metadata ?? [], [
                'reconnect_count' => $reconnects + 1,
                'last_reconnect_at' => now()->toDateTimeString(),
            ]),
        ]);

        if ($reconnects >= $maxReconnects) {
            Log::warning("MonitorStreamsJob: stream {$stream->id} not ready after {$maxReconnects} checks, marking as error (auto-recovery will retry with backoff).");
            $stream->markError('Câmera inacessível — MediaMTX não consegue conectar à fonte RTSP.');
        } else {
            Log::info("MonitorStreamsJob: stream {$stream->id} waiting for MediaMTX reconnection ({$reconnects}/{$maxReconnects}).");
        }
    }
}
