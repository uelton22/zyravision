<?php

namespace App\Console\Commands;

use App\Models\Camera;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

/**
 * Lista o status de todos os processos de transcodificacao ativos.
 *
 * Consulta o Redis (via Cache connection) para obter informacoes dos
 * workers transcoder e seus processos FFmpeg.
 */
class TranscoderStatusCommand extends Command
{
    protected $signature = 'transcoder:status';

    protected $description = 'Lista todos os processos de transcodificacao ativos (via Redis)';

    public function handle(): int
    {
        $this->info('=== ZyraVision Transcoder Status ===');
        $this->newLine();

        // 1. List active workers
        $this->info('Workers Ativos:');
        $workers = $this->getCacheValues('transcoding:workers:*');

        if (empty($workers)) {
            $this->warn('  Nenhum worker transcoder ativo.');
        } else {
            $workerRows = [];
            foreach ($workers as $data) {
                $workerRows[] = [
                    $data['worker_id'] ?? 'N/A',
                    $data['hostname'] ?? 'N/A',
                    $data['active_count'] ?? 0,
                    $data['max_cameras'] ?? 'N/A',
                    $data['updated_at'] ?? 'N/A',
                ];
            }
            $this->table(
                ['Worker ID', 'Hostname', 'Cameras Ativas', 'Max Cameras', 'Ultima Atualizacao'],
                $workerRows
            );
        }

        $this->newLine();

        // 2. List active transcoding cameras
        $this->info('Cameras em Transcodificacao:');
        $cameras = $this->getCacheValues('transcoding:cameras:*');
        $activeCameras = array_filter($cameras, fn ($d) => in_array($d['status'] ?? '', ['pending', 'running']));

        if (empty($activeCameras)) {
            $this->warn('  Nenhuma camera em transcodificacao.');
        } else {
            $cameraRows = [];
            foreach ($activeCameras as $data) {
                $camera = Camera::find($data['camera_id'] ?? '');
                $cameraRows[] = [
                    $data['camera_id'] ?? 'N/A',
                    $camera?->name ?? 'N/A',
                    $data['quality'] ?? 'N/A',
                    $data['status'] ?? 'N/A',
                    $data['worker_id'] ?? 'N/A',
                    $data['pid'] ?? 'N/A',
                    $data['requested_at'] ?? $data['updated_at'] ?? 'N/A',
                ];
            }
            $this->table(
                ['Camera ID', 'Nome', 'Qualidade', 'Status', 'Worker', 'PID', 'Desde'],
                $cameraRows
            );
        }

        $this->newLine();
        $this->info('Total: ' . count($activeCameras) . ' camera(s) em transcodificacao, ' . count($workers) . ' worker(s) ativo(s).');

        return self::SUCCESS;
    }

    /**
     * Get cached values by scanning Redis cache DB with a pattern.
     *
     * The pattern should be relative to the Cache prefix (e.g. 'transcoding:workers:*').
     * The 'cache' Redis connection already adds the Redis options prefix, so
     * we only need to prepend the Cache prefix for the pattern.
     *
     * @return array<array>
     */
    private function getCacheValues(string $cachePattern): array
    {
        $cachePrefix = config('cache.prefix', '');
        $fullPattern = $cachePrefix . $cachePattern;

        $redis = Redis::connection('cache');
        $rawKeys = $redis->keys($fullPattern);

        $results = [];
        // The keys() result includes the full Redis prefix
        // We need to extract just the Cache key (after removing both Redis + Cache prefix)
        $redisPrefix = config('database.redis.options.prefix', '');
        $fullPrefix = $redisPrefix . $cachePrefix;

        foreach ($rawKeys as $rawKey) {
            $cacheKey = str_replace($fullPrefix, '', $rawKey);
            $data = Cache::get($cacheKey);
            if ($data && is_array($data)) {
                $results[] = $data;
            }
        }

        return $results;
    }
}
