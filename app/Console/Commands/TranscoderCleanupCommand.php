<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

/**
 * Limpa entradas obsoletas de transcodificacao no Redis.
 */
class TranscoderCleanupCommand extends Command
{
    protected $signature = 'transcoder:cleanup';

    protected $description = 'Limpa entradas obsoletas de transcodificacao no Redis';

    public function handle(): int
    {
        $removed = 0;

        // 1. Cleanup stale workers (not updated in 5 minutes)
        $workers = $this->getCacheKeyValues('transcoding:workers:*');
        foreach ($workers as [$cacheKey, $data]) {
            $updatedAt = $data['updated_at'] ?? $data['started_at'] ?? null;
            if ($updatedAt && now()->diffInMinutes($updatedAt) > 5) {
                Cache::forget($cacheKey);
                $removed++;
                $workerId = $data['worker_id'] ?? 'unknown';
                $this->info("Worker removido: {$workerId} (inativo > 5min)");
            }
        }

        // 2. Cleanup stale camera entries
        $cameras = $this->getCacheKeyValues('transcoding:cameras:*');
        foreach ($cameras as [$cacheKey, $data]) {
            $status = $data['status'] ?? '';
            $updatedAt = $data['updated_at'] ?? $data['requested_at'] ?? null;

            $shouldRemove = false;

            if ($status === 'pending' && $updatedAt && now()->diffInMinutes($updatedAt) > 10) {
                $shouldRemove = true;
            }

            if ($status === 'error' && $updatedAt && now()->diffInMinutes($updatedAt) > 30) {
                $shouldRemove = true;
            }

            if ($status === 'stopped' && $updatedAt && now()->diffInMinutes($updatedAt) > 5) {
                $shouldRemove = true;
            }

            if ($shouldRemove) {
                Cache::forget($cacheKey);
                $removed++;
                $cameraIdLabel = $data['camera_id'] ?? 'unknown';
                $this->info("Camera removida: {$cameraIdLabel} (status: {$status})");
            }
        }

        if ($removed > 0) {
            $this->info("Limpeza concluida: {$removed} entrada(s) removida(s).");
        } else {
            $this->info('Nenhuma entrada obsoleta encontrada.');
        }

        return self::SUCCESS;
    }

    /**
     * Get cached [key, value] pairs by scanning Redis with a pattern.
     *
     * @return array<array{0: string, 1: array}>
     */
    private function getCacheKeyValues(string $cachePattern): array
    {
        $cachePrefix = config('cache.prefix', '');
        $fullPattern = $cachePrefix . $cachePattern;

        $redis = Redis::connection('cache');
        $rawKeys = $redis->keys($fullPattern);

        $results = [];
        $redisPrefix = config('database.redis.options.prefix', '');
        $fullPrefix = $redisPrefix . $cachePrefix;

        foreach ($rawKeys as $rawKey) {
            $cacheKey = str_replace($fullPrefix, '', $rawKey);
            $data = Cache::get($cacheKey);
            if ($data && is_array($data)) {
                $results[] = [$cacheKey, $data];
            }
        }

        return $results;
    }
}
