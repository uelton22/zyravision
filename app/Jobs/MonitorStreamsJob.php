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
 * Monitor active streams and manage self-healing recovery.
 *
 * ARCHITECTURE PRINCIPLE: 1 RTSP connection per camera.
 * - Camera → 1 RTSP → MediaMTX → HLS + Recording + Snapshot
 * - MediaMTX manages its own RTSP reconnections internally
 *
 * SELF-HEALING STRATEGY (3 phases):
 *
 * Phase 1 — Soft Recovery:
 *   Re-register missing paths or wait for MediaMTX internal reconnection.
 *   Fast backoff (30s–120s) for transient network issues.
 *
 * Phase 2 — Hard Reset:
 *   Remove path completely from MediaMTX + re-add (fresh RTSP connection).
 *   Triggered after N soft failures. Equivalent to manual "Force Reset".
 *
 * Phase 3 — Maintenance Mode:
 *   Slow periodic hard resets indefinitely (every 5 min by default).
 *   The system NEVER permanently gives up. No human intervention required.
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

        // 2. Auto-recover error streams with intelligent strategy
        $this->recoverErrorStreams($client, $streamManager);
    }

    // ─────────────────────────────────────────────────────────
    // Phase 0: Monitor running streams
    // ─────────────────────────────────────────────────────────

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

    private function checkStream(Stream $stream, MediaMtxClient $client): void
    {
        $pathInfo = $client->getPath($stream->mediamtx_path);

        // ── Path doesn't exist in MediaMTX ──
        if ($pathInfo === null) {
            $this->handleMissingPath($stream, $client);
            return;
        }

        // ── Path exists — check if source is ready ──
        $ready = $pathInfo['ready'] ?? false;

        if ($ready) {
            $this->handleHealthyStream($stream, $pathInfo, $client);
            return;
        }

        // ── Path exists but NOT ready — source disconnected ──
        $this->handleNotReadyStream($stream);
    }

    /**
     * Handle a stream whose path is completely missing from MediaMTX.
     * Re-registers it (up to 3 times), then marks as error for recovery.
     */
    private function handleMissingPath(Stream $stream, MediaMtxClient $client): void
    {
        $reconnects = $stream->metadata['reconnect_count'] ?? 0;
        $maxReconnects = 3;

        if ($reconnects < $maxReconnects) {
            Log::warning("MonitorStreamsJob: path {$stream->mediamtx_path} missing, re-registering (attempt {$reconnects}/{$maxReconnects}).");

            $stream->markReconnecting();
            $stream->update([
                'metadata' => array_merge($stream->metadata ?? [], [
                    'reconnect_count' => $reconnects + 1,
                    'last_reconnect_at' => now()->toDateTimeString(),
                ]),
            ]);

            if ($stream->rtsp_input_url) {
                $client->addPath($stream->mediamtx_path, [
                    'source' => $stream->rtsp_input_url,
                    'rtspTransport' => $stream->camera?->transport ?? 'tcp',
                    'sourceOnDemand' => false,
                ]);
            }
        } else {
            Log::warning("MonitorStreamsJob: path {$stream->mediamtx_path} missing after {$maxReconnects} re-registers, marking as error for auto-recovery.");
            $stream->markError('Path removido do MediaMTX — aguardando auto-recovery.');
            $stream->camera?->update(['is_online' => false]);
        }
    }

    /**
     * Handle a healthy stream (path exists and source is ready).
     * Resets all recovery counters on success.
     * Also ensures recording is enabled if the camera has continuous_recording.
     */
    private function handleHealthyStream(Stream $stream, array $pathInfo, MediaMtxClient $client): void
    {
        $wasRecovering = in_array($stream->status, [Stream::STATUS_STARTING, Stream::STATUS_RECONNECTING]);

        if ($stream->status !== Stream::STATUS_ACTIVE) {
            $stream->markActive();

            if ($wasRecovering) {
                Log::info("MonitorStreamsJob: stream {$stream->id} recovered successfully!", [
                    'previous_status' => $stream->getOriginal('status'),
                    'recovery_attempts' => $stream->metadata['recovery_attempt'] ?? 0,
                    'hard_resets' => $stream->metadata['hard_reset_count'] ?? 0,
                ]);
            }

            // When a stream transitions to active, ensure recording is enabled
            // if the camera has continuous_recording configured.
            // This covers the case where recording couldn't start during stream
            // creation (because stream wasn't ready yet).
            $camera = $stream->camera;
            if ($camera && $camera->continuous_recording && ! $camera->is_recording) {
                $recorded = $client->editPath($stream->mediamtx_path, ['record' => true]);
                if ($recorded) {
                    $camera->update(['is_recording' => true]);
                    Log::info("MonitorStreamsJob: gravacao continua ativada para camera {$camera->id} (stream ficou ativo).");
                }
            }
        }

        // Reset ALL recovery counters on success — clean slate
        $stream->updateHealthCheck([
            'readers' => count($pathInfo['readers'] ?? []),
            'bytesReceived' => $pathInfo['bytesReceived'] ?? 0,
            'reconnect_count' => 0,
            'recovery_attempt' => 0,
            'hard_reset_count' => 0,
            'recovery_phase' => null,
        ]);

        $stream->camera?->update([
            'is_online' => true,
            'last_seen_at' => now(),
        ]);
    }

    /**
     * Handle a stream where the path exists but source is NOT ready.
     *
     * Uses a configurable threshold (default: 6 checks = 3 minutes at 30s intervals)
     * before marking as error. This gives MediaMTX time to reconnect on its own.
     */
    private function handleNotReadyStream(Stream $stream): void
    {
        $reconnects = $stream->metadata['reconnect_count'] ?? 0;
        $maxReconnects = config('streaming.recovery.not_ready_checks_before_error', 6);

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
            Log::warning("MonitorStreamsJob: stream {$stream->id} not ready after {$maxReconnects} checks (~" . ($maxReconnects * 30) . "s), marking as error for auto-recovery.");
            $stream->markError('Câmera inacessível — fonte RTSP não responde. Auto-recovery em andamento.');
        } else {
            Log::info("MonitorStreamsJob: stream {$stream->id} waiting for MediaMTX reconnection ({$reconnects}/{$maxReconnects}).");
        }
    }

    // ─────────────────────────────────────────────────────────
    // Self-Healing Recovery: 3-Phase Strategy
    // ─────────────────────────────────────────────────────────

    /**
     * Auto-recover streams in error state with intelligent 3-phase strategy.
     *
     * THE SYSTEM NEVER PERMANENTLY GIVES UP.
     *
     * Phase 1 (Soft): Re-register path or wait for MediaMTX reconnection.
     *   - Fast backoff for transient issues (30s–120s).
     *
     * Phase 2 (Hard Reset): Full path removal + re-registration.
     *   - Equivalent to clicking "Force Reset" manually.
     *   - Clears stuck state in MediaMTX.
     *
     * Phase 3 (Maintenance): Periodic hard resets indefinitely.
     *   - Every 5 minutes (configurable).
     *   - Ensures camera recovers even after extended outages.
     */
    private function recoverErrorStreams(MediaMtxClient $client, StreamManager $streamManager): void
    {
        $errorStreams = Stream::where('status', Stream::STATUS_ERROR)
            ->with('camera')
            ->get();

        foreach ($errorStreams as $stream) {
            if (! $stream->camera || ! $stream->camera->is_active) {
                continue;
            }

            try {
                $this->recoverStream($stream, $client, $streamManager);
            } catch (\Throwable $e) {
                Log::error("MonitorStreamsJob: recovery failed for stream {$stream->id}.", [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Determine recovery phase and execute the appropriate strategy.
     */
    private function recoverStream(Stream $stream, MediaMtxClient $client, StreamManager $streamManager): void
    {
        $recoveryAttempt = $stream->metadata['recovery_attempt'] ?? 0;
        $hardResetCount = $stream->metadata['hard_reset_count'] ?? 0;

        $softMax = config('streaming.recovery.soft_max_attempts', 5);
        $softBeforeHard = config('streaming.recovery.soft_attempts_before_hard_reset', 3);
        $hardMax = config('streaming.recovery.hard_max_attempts', 3);

        // Determine which phase we're in
        $totalSoftAttempts = $recoveryAttempt - $hardResetCount;

        if ($recoveryAttempt < $softBeforeHard) {
            // ── Phase 1: Soft Recovery ──
            $this->executeSoftRecovery($stream, $client, $recoveryAttempt);
        } elseif ($hardResetCount < $hardMax) {
            // ── Phase 2: Hard Reset ──
            $this->executeHardReset($stream, $streamManager, $recoveryAttempt, $hardResetCount);
        } else {
            // ── Phase 3: Maintenance Mode (never gives up) ──
            $this->executeMaintenanceRecovery($stream, $streamManager, $recoveryAttempt, $hardResetCount);
        }
    }

    /**
     * Phase 1: Soft Recovery
     *
     * Re-registers missing paths or waits for MediaMTX internal reconnection.
     * Uses exponential backoff: 30s, 45s, 60s, 90s, 120s (max).
     */
    private function executeSoftRecovery(Stream $stream, MediaMtxClient $client, int $attempt): void
    {
        $initialBackoff = config('streaming.recovery.soft_initial_backoff_seconds', 30);
        $maxBackoff = config('streaming.recovery.soft_max_backoff_seconds', 120);

        // Exponential backoff: 30, 45, 67, 100, 120 (capped)
        $backoffSeconds = min($initialBackoff * pow(1.5, $attempt), $maxBackoff);

        if (! $this->hasBackoffElapsed($stream, $backoffSeconds)) {
            return;
        }

        Log::info("MonitorStreamsJob: [Phase 1 - Soft Recovery] stream {$stream->id} (attempt #{$attempt}).", [
            'backoff' => round($backoffSeconds) . 's',
        ]);

        // Check if path still exists in MediaMTX
        $pathInfo = $client->getPath($stream->mediamtx_path);

        if ($pathInfo === null) {
            // Path missing — re-register it
            Log::info("MonitorStreamsJob: path {$stream->mediamtx_path} not found, re-registering.");

            $client->addPath($stream->mediamtx_path, [
                'source' => $stream->rtsp_input_url,
                'rtspTransport' => $stream->camera->transport ?? 'tcp',
                'sourceOnDemand' => false,
            ]);
        }
        // If path exists but not ready, MediaMTX is handling reconnection

        $this->updateRecoveryMetadata($stream, $attempt + 1);
    }

    /**
     * Phase 2: Hard Reset
     *
     * Removes the path completely from MediaMTX and re-registers it.
     * This is the automated equivalent of the manual "Force Reset" button.
     * Forces MediaMTX to open a brand new RTSP connection.
     */
    private function executeHardReset(Stream $stream, StreamManager $streamManager, int $attempt, int $hardResetCount): void
    {
        $cooldown = config('streaming.recovery.hard_reset_cooldown_seconds', 60);

        if (! $this->hasBackoffElapsed($stream, $cooldown)) {
            return;
        }

        Log::warning("MonitorStreamsJob: [Phase 2 - Hard Reset] stream {$stream->id} (hard reset #{$hardResetCount}, total attempt #{$attempt}).", [
            'camera_id' => $stream->camera_id,
            'path' => $stream->mediamtx_path,
        ]);

        $success = $streamManager->hardResetStream($stream);

        // Update metadata
        $stream->update([
            'metadata' => array_merge($stream->metadata ?? [], [
                'reconnect_count' => 0,
                'last_reconnect_at' => now()->toDateTimeString(),
                'recovery_attempt' => $attempt + 1,
                'hard_reset_count' => $hardResetCount + 1,
                'last_hard_reset_at' => now()->toDateTimeString(),
                'recovery_phase' => 'hard_reset',
            ]),
        ]);

        if (! $success) {
            // Hard reset failed — stream stays in error for next attempt
            $stream->update([
                'status' => Stream::STATUS_ERROR,
                'error_message' => 'Hard reset falhou — tentando novamente.',
            ]);
        }
    }

    /**
     * Phase 3: Maintenance Mode
     *
     * THE SYSTEM NEVER PERMANENTLY GIVES UP.
     *
     * After all soft + hard recovery attempts are exhausted, the system enters
     * maintenance mode: periodic hard resets at a slower interval (default: 5 min).
     *
     * This handles scenarios like:
     * - Camera power was cut for hours
     * - Network outage that lasted longer than initial recovery window
     * - Camera firmware restarted
     * - Temporary infrastructure issues
     *
     * The camera will automatically recover as soon as it comes back online.
     */
    private function executeMaintenanceRecovery(Stream $stream, StreamManager $streamManager, int $attempt, int $hardResetCount): void
    {
        $maintenanceInterval = config('streaming.recovery.maintenance_interval_seconds', 300);

        if (! $this->hasBackoffElapsed($stream, $maintenanceInterval)) {
            return;
        }

        Log::info("MonitorStreamsJob: [Phase 3 - Maintenance] stream {$stream->id} maintenance hard reset (attempt #{$attempt}, hard resets: {$hardResetCount}).", [
            'camera_id' => $stream->camera_id,
            'path' => $stream->mediamtx_path,
            'interval' => $maintenanceInterval . 's',
        ]);

        $success = $streamManager->hardResetStream($stream);

        $stream->update([
            'metadata' => array_merge($stream->metadata ?? [], [
                'reconnect_count' => 0,
                'last_reconnect_at' => now()->toDateTimeString(),
                'recovery_attempt' => $attempt + 1,
                'hard_reset_count' => $hardResetCount + 1,
                'last_hard_reset_at' => now()->toDateTimeString(),
                'recovery_phase' => 'maintenance',
            ]),
        ]);

        if (! $success) {
            $stream->update([
                'status' => Stream::STATUS_ERROR,
                'error_message' => 'Auto-recovery em modo manutenção — tentando periodicamente.',
            ]);
        }
    }

    // ─────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────

    /**
     * Check if enough time has elapsed since the last recovery attempt.
     */
    private function hasBackoffElapsed(Stream $stream, float $backoffSeconds): bool
    {
        $lastAttempt = $stream->metadata['last_reconnect_at'] ?? null;

        if (! $lastAttempt) {
            return true;
        }

        return now()->diffInSeconds($lastAttempt) >= $backoffSeconds;
    }

    /**
     * Update stream metadata after a soft recovery attempt.
     */
    private function updateRecoveryMetadata(Stream $stream, int $nextAttempt): void
    {
        $stream->update([
            'status' => Stream::STATUS_RECONNECTING,
            'error_message' => null,
            'metadata' => array_merge($stream->metadata ?? [], [
                'reconnect_count' => 0,
                'last_reconnect_at' => now()->toDateTimeString(),
                'recovery_attempt' => $nextAttempt,
                'recovery_phase' => 'soft',
            ]),
        ]);
    }
}
