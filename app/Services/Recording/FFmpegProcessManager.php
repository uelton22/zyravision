<?php

namespace App\Services\Recording;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Manages FFmpeg child processes: start, stop, track PIDs via Redis cache.
 *
 * Each running FFmpeg transcoder is tracked as a cache entry:
 *   ffmpeg_transcoder:{camera_id} => ['pid' => int, 'started_at' => string, 'path' => string]
 */
class FFmpegProcessManager
{
    private string $cachePrefix;

    private int $cacheTtl;

    public function __construct()
    {
        $this->cachePrefix = config('transcoding.process.cache_prefix', 'ffmpeg_transcoder:');
        $this->cacheTtl = config('transcoding.process.cache_ttl', 86400);
    }

    /**
     * Start a new FFmpeg process and track its PID.
     *
     * @param  string  $cameraId  Camera UUID
     * @param  array  $command  FFmpeg command as array of arguments
     * @param  string  $mediamtxPath  MediaMTX stream path
     * @return int|null PID of the started process, or null on failure
     */
    public function startProcess(string $cameraId, array $command, string $mediamtxPath): ?int
    {
        // Check if already running
        if ($this->isAlive($cameraId)) {
            Log::warning("FFmpeg transcoder ja esta rodando para camera {$cameraId}.");

            return $this->getPid($cameraId);
        }

        // Check max concurrent limit
        $activeCount = count($this->getAll());
        $maxConcurrent = config('transcoding.process.max_concurrent', 20);
        if ($activeCount >= $maxConcurrent) {
            Log::error("Limite de processos de transcodificacao atingido ({$activeCount}/{$maxConcurrent}).");

            return null;
        }

        // Build the shell command string
        $cmdString = implode(' ', array_map('escapeshellarg', $command));

        // Redirect stderr to a log file, stdout to /dev/null
        $logFile = storage_path("logs/ffmpeg_{$cameraId}.log");
        $fullCmd = "{$cmdString} > /dev/null 2>> " . escapeshellarg($logFile) . ' & echo $!';

        // Start the process in background
        $output = [];
        $returnCode = 0;
        exec($fullCmd, $output, $returnCode);

        $pid = (int) ($output[0] ?? 0);

        if ($pid <= 0) {
            Log::error("Falha ao iniciar FFmpeg para camera {$cameraId}.", [
                'return_code' => $returnCode,
            ]);

            return null;
        }

        // Store PID in cache
        Cache::put($this->cachePrefix . $cameraId, [
            'pid' => $pid,
            'started_at' => now()->toIso8601String(),
            'path' => $mediamtxPath,
            'log_file' => $logFile,
        ], $this->cacheTtl);

        Log::info("FFmpeg transcoder iniciado para camera {$cameraId}.", [
            'pid' => $pid,
            'path' => $mediamtxPath,
        ]);

        return $pid;
    }

    /**
     * Stop an FFmpeg process for a camera.
     */
    public function stopProcess(string $cameraId): bool
    {
        $data = Cache::get($this->cachePrefix . $cameraId);

        if (! $data || ! isset($data['pid'])) {
            Cache::forget($this->cachePrefix . $cameraId);

            return true;
        }

        $pid = (int) $data['pid'];

        if (! $this->pidIsAlive($pid)) {
            Cache::forget($this->cachePrefix . $cameraId);
            Log::info("FFmpeg PID {$pid} ja nao esta rodando para camera {$cameraId}.");

            return true;
        }

        // Graceful stop: SIGTERM
        posix_kill($pid, SIGTERM);

        $stopTimeout = config('transcoding.ffmpeg.stop_timeout', 5);
        $waited = 0;

        while ($waited < $stopTimeout && $this->pidIsAlive($pid)) {
            usleep(500000); // 0.5s
            $waited += 0.5;
        }

        // Force kill if still alive
        if ($this->pidIsAlive($pid)) {
            posix_kill($pid, SIGKILL);
            usleep(500000);
            Log::warning("FFmpeg PID {$pid} teve que ser forçado (SIGKILL) para camera {$cameraId}.");
        }

        Cache::forget($this->cachePrefix . $cameraId);

        Log::info("FFmpeg transcoder parado para camera {$cameraId}.", ['pid' => $pid]);

        return true;
    }

    /**
     * Check if the FFmpeg process for a camera is still running.
     */
    public function isAlive(string $cameraId): bool
    {
        $data = Cache::get($this->cachePrefix . $cameraId);

        if (! $data || ! isset($data['pid'])) {
            return false;
        }

        $alive = $this->pidIsAlive((int) $data['pid']);

        // Cleanup stale entry
        if (! $alive) {
            Cache::forget($this->cachePrefix . $cameraId);
        }

        return $alive;
    }

    /**
     * Get the PID for a camera's transcoder.
     */
    public function getPid(string $cameraId): ?int
    {
        $data = Cache::get($this->cachePrefix . $cameraId);

        return $data['pid'] ?? null;
    }

    /**
     * Get process info for a camera.
     */
    public function getProcessInfo(string $cameraId): ?array
    {
        $data = Cache::get($this->cachePrefix . $cameraId);

        if (! $data) {
            return null;
        }

        $pid = (int) $data['pid'];
        $alive = $this->pidIsAlive($pid);

        if (! $alive) {
            Cache::forget($this->cachePrefix . $cameraId);

            return null;
        }

        return [
            'pid' => $pid,
            'started_at' => $data['started_at'],
            'path' => $data['path'] ?? null,
            'log_file' => $data['log_file'] ?? null,
            'alive' => true,
        ];
    }

    /**
     * Get all active transcoder processes.
     *
     * @return array<string, array> camera_id => process info
     */
    public function getAll(): array
    {
        // We need to scan cache keys. With Redis, use pattern matching.
        // Fallback: maintain a set of known camera IDs.
        $indexKey = $this->cachePrefix . '_index';
        $cameraIds = Cache::get($indexKey, []);
        $active = [];

        foreach ($cameraIds as $cameraId) {
            $info = $this->getProcessInfo($cameraId);
            if ($info) {
                $active[$cameraId] = $info;
            }
        }

        // Update index
        Cache::put($indexKey, array_keys($active), $this->cacheTtl);

        return $active;
    }

    /**
     * Register a camera ID in the process index.
     */
    public function addToIndex(string $cameraId): void
    {
        $indexKey = $this->cachePrefix . '_index';
        $cameraIds = Cache::get($indexKey, []);

        if (! in_array($cameraId, $cameraIds)) {
            $cameraIds[] = $cameraId;
            Cache::put($indexKey, $cameraIds, $this->cacheTtl);
        }
    }

    /**
     * Clean up dead processes from the registry.
     *
     * @return int Number of stale entries removed
     */
    public function cleanup(): int
    {
        $indexKey = $this->cachePrefix . '_index';
        $cameraIds = Cache::get($indexKey, []);
        $removed = 0;
        $alive = [];

        foreach ($cameraIds as $cameraId) {
            if ($this->isAlive($cameraId)) {
                $alive[] = $cameraId;
            } else {
                Cache::forget($this->cachePrefix . $cameraId);
                $removed++;
            }
        }

        Cache::put($indexKey, $alive, $this->cacheTtl);

        if ($removed > 0) {
            Log::info("FFmpeg cleanup: {$removed} processos mortos removidos do registro.");
        }

        return $removed;
    }

    /**
     * Check if a PID is alive on the system.
     */
    private function pidIsAlive(int $pid): bool
    {
        if ($pid <= 0) {
            return false;
        }

        // posix_kill with signal 0 checks if process exists
        return posix_kill($pid, 0);
    }
}
