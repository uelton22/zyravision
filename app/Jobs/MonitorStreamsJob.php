<?php

namespace App\Jobs;

use App\Models\Stream;
use App\Services\Recording\RecordingManager;
use App\Services\Streaming\MediaMtxClient;
use App\Services\Streaming\StreamManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Unified stream monitor and auto-recovery.
 *
 * ARCHITECTURE: 1 RTSP connection per camera → MediaMTX → HLS + Recording + Snapshot
 *
 * HEALTH-BASED MONITORING (inspired by NUV):
 *
 * 1. Check MediaMTX path: ready + bytesReceived
 * 2. If healthy → ACTIVE, reset everything
 * 3. If NOT healthy → start tolerance timer
 *    - First 90s: wait for MediaMTX to reconnect on its own (do NOT interfere)
 *    - After 90s: hard reset (removePath + addPath), equivalent to "Forçar Reinício"
 *    - After hard reset: wait 90s, check again
 *    - After 5 hard resets: maintenance mode (hard reset every 5 min)
 *
 * KEY PRINCIPLES:
 * - NO editPath "soft recovery" — it's proven ineffective and disrupts MediaMTX reconnection
 * - NO sleep() calls — everything is async, checked across cycles
 * - NO separate "monitor" vs "recovery" flows — one unified pass
 * - Camera marked offline only after 60s (not immediately)
 * - THE SYSTEM NEVER PERMANENTLY GIVES UP
 */
class MonitorStreamsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public bool $failOnTimeout = true;
    public int $timeout = 60;

    private RecordingManager $recordingManager;

    public function __construct()
    {
        $this->onQueue('streaming');
    }

    public function handle(MediaMtxClient $client, StreamManager $streamManager, RecordingManager $recordingManager): void
    {
        $this->recordingManager = $recordingManager;

        // Process ALL non-stopped streams in one unified pass
        $streams = Stream::whereNotIn('status', [Stream::STATUS_STOPPED])
            ->with('camera')
            ->get();

        if ($streams->isEmpty()) {
            return;
        }

        foreach ($streams as $stream) {
            if (! $stream->camera || ! $stream->camera->is_active) {
                continue;
            }

            try {
                $this->processStream($stream, $client, $streamManager);
            } catch (\Throwable $e) {
                Log::error("Monitor: erro ao processar stream {$stream->id}: {$e->getMessage()}");
            }
        }
    }

    /**
     * Process a single stream: check health and take action.
     */
    private function processStream(Stream $stream, MediaMtxClient $client, StreamManager $streamManager): void
    {
        $pathInfo = $client->getPath($stream->mediamtx_path);
        $ready = $pathInfo !== null && ($pathInfo['ready'] ?? false);
        $bytesReceived = $pathInfo !== null ? ($pathInfo['bytesReceived'] ?? 0) : 0;
        $previousBytes = $stream->metadata['last_bytes_received'] ?? 0;
        $bytesFlowing = $bytesReceived > $previousBytes && $previousBytes > 0;

        // Always update bytes tracking for bitrate analysis
        $stream->update([
            'metadata' => array_merge($stream->metadata ?? [], [
                'last_bytes_received' => $bytesReceived,
                'last_check_at' => now()->toDateTimeString(),
            ]),
        ]);

        // ── HEALTHY: source ready OR bytes still flowing ──
        if ($ready || ($pathInfo !== null && $bytesFlowing)) {
            $this->onHealthy($stream, $pathInfo);
            return;
        }

        // ── NOT HEALTHY: path missing or source not ready ──
        $this->onUnhealthy($stream, $streamManager, $pathInfo === null);
    }

    /**
     * Stream is healthy — mark active, reset counters, ensure recording.
     */
    private function onHealthy(Stream $stream, ?array $pathInfo): void
    {
        $wasRecovering = $stream->status !== Stream::STATUS_ACTIVE;

        if ($wasRecovering) {
            $hardResets = $stream->metadata['hard_reset_count'] ?? 0;

            $stream->markActive();
            Log::info("Monitor: stream {$stream->id} RECUPERADO!", [
                'status_anterior' => $stream->getOriginal('status'),
                'hard_resets_usados' => $hardResets,
                'camera' => $stream->camera?->name,
            ]);
        }

        // Reset all recovery trackers
        $stream->updateHealthCheck([
            'readers' => count($pathInfo['readers'] ?? []),
            'bytesReceived' => $pathInfo['bytesReceived'] ?? 0,
            'not_ready_since' => null,
            'hard_reset_count' => 0,
            'last_hard_reset_at' => null,
        ]);

        $stream->camera?->update(['is_online' => true, 'last_seen_at' => now()]);

        // Ensure recording is running when stream recovers
        if ($wasRecovering) {
            $this->ensureRecording($stream);
        }
    }

    /**
     * Stream is NOT healthy — decide: wait, or hard reset.
     *
     * Timeline:
     *   0-90s:  Wait for MediaMTX reconnection (do NOT interfere)
     *   90s+:   Hard reset every 90s (aggressive phase)
     *   5+ HRs: Maintenance mode — hard reset every 5 min
     */
    private function onUnhealthy(Stream $stream, StreamManager $streamManager, bool $pathMissing): void
    {
        $meta = $stream->metadata ?? [];
        $notReadySince = $meta['not_ready_since'] ?? null;
        $hardResetCount = $meta['hard_reset_count'] ?? 0;
        $lastHardReset = $meta['last_hard_reset_at'] ?? null;

        // Start tracking when we first noticed the problem
        if (! $notReadySince) {
            $notReadySince = now()->toDateTimeString();
            $stream->update([
                'metadata' => array_merge($meta, [
                    'not_ready_since' => $notReadySince,
                ]),
            ]);
        }

        $unhealthySeconds = (int) now()->diffInSeconds($notReadySince);

        // Set RECONNECTING status if not already in error/reconnecting
        if ($stream->status === Stream::STATUS_ACTIVE || $stream->status === Stream::STATUS_STARTING) {
            $stream->markReconnecting();
            Log::info("Monitor: stream {$stream->id} ({$stream->camera?->name}) fonte nao pronta, aguardando reconexao...");
        }

        // Mark camera offline only after threshold (default 60s)
        $offlineThreshold = config('streaming.recovery.offline_after_seconds', 60);
        if ($unhealthySeconds >= $offlineThreshold) {
            $stream->camera?->update(['is_online' => false]);
        }

        // ── Determine if we should hard reset ──

        // If path is completely missing from MediaMTX: reset immediately (no waiting)
        $toleranceSeconds = $pathMissing
            ? 0
            : config('streaming.recovery.tolerance_before_reset_seconds', 90);

        if ($unhealthySeconds < $toleranceSeconds) {
            Log::info("Monitor: stream {$stream->id} aguardando MediaMTX reconectar ({$unhealthySeconds}s/{$toleranceSeconds}s).", [
                'camera' => $stream->camera?->name,
                'hard_resets' => $hardResetCount,
            ]);
            return;
        }

        // Check cooldown since last hard reset
        $resetInterval = $this->getResetInterval($hardResetCount);
        $sinceLastReset = $lastHardReset ? (int) now()->diffInSeconds($lastHardReset) : PHP_INT_MAX;

        if ($sinceLastReset < $resetInterval) {
            Log::info("Monitor: stream {$stream->id} cooldown ({$sinceLastReset}s/{$resetInterval}s) antes do proximo reset.", [
                'camera' => $stream->camera?->name,
                'hard_resets' => $hardResetCount,
            ]);
            return;
        }

        // ── EXECUTE HARD RESET ──
        $phase = $hardResetCount >= config('streaming.recovery.maintenance_after_resets', 5)
            ? 'MANUTENCAO'
            : 'AGRESSIVO';

        Log::warning("Monitor: [Hard Reset #{$hardResetCount} - {$phase}] stream {$stream->id} ({$unhealthySeconds}s sem resposta)", [
            'camera' => $stream->camera?->name,
            'camera_id' => $stream->camera_id,
            'path' => $stream->mediamtx_path,
            'path_missing' => $pathMissing,
        ]);

        $stream->camera?->update(['is_online' => false]);

        $success = $streamManager->hardResetStream($stream);

        $stream->update([
            'metadata' => array_merge($stream->metadata ?? [], [
                'hard_reset_count' => $hardResetCount + 1,
                'last_hard_reset_at' => now()->toDateTimeString(),
            ]),
        ]);

        $resetNumber = $hardResetCount + 1;

        if ($success) {
            $stream->update([
                'status' => Stream::STATUS_RECONNECTING,
                'error_message' => "Auto-recovery: hard reset #{$resetNumber} executado.",
            ]);
        } else {
            $stream->markError("Hard reset #{$resetNumber} falhou — tentando novamente em {$resetInterval}s.");
        }
    }

    /**
     * Get the interval between hard resets based on how many we've already done.
     *
     * First 5 resets: aggressive interval (default 90s)
     * After 5 resets: maintenance interval (default 300s = 5 min)
     */
    private function getResetInterval(int $hardResetCount): int
    {
        $aggressiveInterval = config('streaming.recovery.aggressive_reset_interval_seconds', 90);
        $maintenanceInterval = config('streaming.recovery.maintenance_interval_seconds', 300);
        $maintenanceThreshold = config('streaming.recovery.maintenance_after_resets', 5);

        return $hardResetCount >= $maintenanceThreshold
            ? $maintenanceInterval
            : $aggressiveInterval;
    }

    /**
     * Ensure continuous recording is running when a stream recovers.
     */
    private function ensureRecording(Stream $stream): void
    {
        $camera = $stream->camera;

        if (! $camera || ! $camera->continuous_recording || $camera->is_recording) {
            return;
        }

        try {
            $camera->load('activeStream');
            $this->recordingManager->startRecording($camera);
            Log::info("Monitor: gravacao continua reativada para camera {$camera->id} ({$camera->name}).");
        } catch (\Throwable $e) {
            Log::warning("Monitor: falha ao reiniciar gravacao para camera {$camera->id}: {$e->getMessage()}");
        }
    }
}
