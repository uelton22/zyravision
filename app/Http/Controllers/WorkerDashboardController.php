<?php

namespace App\Http\Controllers;

use App\Models\Camera;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Worker Dashboard Controller
 *
 * Exibe o status de todos os workers do sistema:
 * - Transcoder Workers (FFmpeg)
 * - Queue Workers (Horizon)
 * - Web/Nginx
 * - Sistema geral
 *
 * Dados obtidos via Redis Cache e Horizon API.
 */
class WorkerDashboardController extends Controller
{
    /**
     * GET /settings/workers — Pagina Inertia do dashboard de workers.
     */
    public function index(): Response
    {
        return Inertia::render('Settings/Workers/Index', [
            'workers' => $this->getWorkersData(),
        ]);
    }

    /**
     * GET /api/v1/system/workers — API JSON para polling/refresh.
     */
    public function api(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->getWorkersData(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Coleta dados de todos os workers do sistema.
     */
    private function getWorkersData(): array
    {
        return [
            'transcoders' => $this->getTranscoderWorkers(),
            'cameras_transcoding' => $this->getTranscodingCameras(),
            'queue' => $this->getQueueStatus(),
            'system' => $this->getSystemMetrics(),
            'alerts' => $this->getAlerts(),
        ];
    }

    // ─── Transcoder Workers ──────────────────────────────────

    private function getTranscoderWorkers(): array
    {
        $workers = $this->getCacheValues('transcoding:workers:*');

        return array_map(function ($data) {
            $updatedAt = $data['updated_at'] ?? null;
            $isStale = false;

            if ($updatedAt) {
                try {
                    $lastUpdate = \Carbon\Carbon::parse($updatedAt);
                    $isStale = $lastUpdate->diffInSeconds(now()) > 60;
                } catch (\Throwable) {
                    $isStale = true;
                }
            }

            return [
                'worker_id' => $data['worker_id'] ?? 'N/A',
                'hostname' => $data['hostname'] ?? 'N/A',
                'active_cameras' => (int) ($data['active_count'] ?? 0),
                'max_cameras' => (int) ($data['max_cameras'] ?? 10),
                'updated_at' => $updatedAt,
                'status' => $isStale ? 'stale' : 'healthy',
                'usage_percent' => ($data['max_cameras'] ?? 10) > 0
                    ? round(($data['active_count'] ?? 0) / ($data['max_cameras'] ?? 10) * 100, 1)
                    : 0,
            ];
        }, $workers);
    }

    private function getTranscodingCameras(): array
    {
        $cameras = $this->getCacheValues('transcoding:cameras:*');
        $activeCameras = array_filter($cameras, fn ($d) => in_array($d['status'] ?? '', ['pending', 'running']));

        return array_values(array_map(function ($data) {
            $camera = Camera::find($data['camera_id'] ?? '');
            $startedAt = $data['requested_at'] ?? $data['updated_at'] ?? null;
            $uptime = null;

            if ($startedAt) {
                try {
                    $uptime = \Carbon\Carbon::parse($startedAt)->diffForHumans(null, true);
                } catch (\Throwable) {
                    // ignore
                }
            }

            return [
                'camera_id' => $data['camera_id'] ?? 'N/A',
                'camera_name' => $camera?->name ?? 'Desconhecida',
                'quality' => $data['quality'] ?? 'N/A',
                'status' => $data['status'] ?? 'N/A',
                'worker_id' => $data['worker_id'] ?? 'N/A',
                'pid' => $data['pid'] ?? null,
                'started_at' => $startedAt,
                'uptime' => $uptime,
            ];
        }, $activeCameras));
    }

    // ─── Queue Workers (Horizon) ─────────────────────────────

    private function getQueueStatus(): array
    {
        $horizon = $this->getHorizonMetrics();

        return [
            'status' => $horizon['status'],
            'supervisors' => $horizon['supervisors'],
            'total_processes' => $horizon['total_processes'],
            'jobs_per_minute' => $horizon['jobs_per_minute'],
            'recent_failed' => $horizon['recent_failed'],
            'queues' => $horizon['queues'],
        ];
    }

    private function getHorizonMetrics(): array
    {
        $horizonPrefix = config('horizon.prefix', 'horizon:');

        try {
            // Horizon writes keys WITHOUT the Redis connection prefix.
            // So we need a raw Redis client to query Horizon data directly.
            $redisConfig = config('database.redis.default');
            /** @var \Redis $rawRedis */
            $rawRedis = new \Redis();
            $rawRedis->connect(
                $redisConfig['host'] ?? '127.0.0.1',
                (int) ($redisConfig['port'] ?? 6379)
            );
            if (!empty($redisConfig['password']) && $redisConfig['password'] !== 'null') {
                $rawRedis->auth($redisConfig['password']);
            }
            $rawRedis->select((int) ($redisConfig['database'] ?? 0));

            // Horizon master status (zset)
            $masterStatus = 'inactive';
            $masters = $rawRedis->zRange($horizonPrefix . 'masters', 0, -1);
            if (!empty($masters)) {
                $masterStatus = 'running';
            }

            // Horizon supervisors (zset)
            $supervisors = [];
            $totalProcesses = 0;
            $supervisorNames = $rawRedis->zRange($horizonPrefix . 'supervisors', 0, -1);

            foreach ($supervisorNames as $name) {
                $infoKey = $horizonPrefix . 'supervisor:' . $name;
                $info = $rawRedis->hGetAll($infoKey);

                if (!empty($info)) {
                    // processes is a JSON string like {"redis:default":1}
                    $processesData = json_decode($info['processes'] ?? '{}', true);
                    $processCount = is_array($processesData) ? array_sum($processesData) : 0;
                    $totalProcesses += $processCount;

                    // Extract supervisor short name
                    $parts = explode(':', $name);
                    $shortName = end($parts);

                    // Extract queue from options JSON
                    $options = json_decode($info['options'] ?? '{}', true);
                    $queue = $options['queue'] ?? 'default';

                    $supervisors[] = [
                        'name' => $shortName,
                        'full_name' => $name,
                        'status' => $info['status'] ?? 'unknown',
                        'processes' => $processCount,
                        'queue' => $queue,
                    ];
                }
            }

            // Queue sizes (these use the Laravel Redis prefix)
            $redis = Redis::connection('default');
            $queueNames = ['default', 'recordings', 'streaming', 'transcoding', 'notifications'];
            $queues = [];
            foreach ($queueNames as $queueName) {
                $queueKey = 'queues:' . $queueName;
                $size = (int) $redis->llen($queueKey);
                $queues[] = [
                    'name' => $queueName,
                    'size' => $size,
                    'status' => $size > 50 ? 'overloaded' : ($size > 10 ? 'busy' : 'healthy'),
                ];
            }

            // Jobs per minute (from Horizon metrics)
            $jobsPerMinute = 0;
            try {
                $throughputData = $rawRedis->hGetAll($horizonPrefix . 'measured_jobs');
                if (!empty($throughputData)) {
                    $jobsPerMinute = array_sum(array_map('floatval', $throughputData));
                }
            } catch (\Throwable) {
                // ignore
            }

            // Recent failed jobs count
            $recentFailed = 0;
            try {
                $recentFailed = (int) $rawRedis->zCard($horizonPrefix . 'recent_failed_jobs');
            } catch (\Throwable) {
                // ignore
            }

            $rawRedis->close();

            return [
                'status' => $masterStatus,
                'supervisors' => $supervisors,
                'total_processes' => $totalProcesses,
                'jobs_per_minute' => round($jobsPerMinute, 1),
                'recent_failed' => $recentFailed,
                'queues' => $queues,
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'error',
                'supervisors' => [],
                'total_processes' => 0,
                'jobs_per_minute' => 0,
                'recent_failed' => 0,
                'queues' => [],
                'error' => $e->getMessage(),
            ];
        }
    }

    // ─── System Metrics ──────────────────────────────────────

    private function getSystemMetrics(): array
    {
        $diskTotalBytes = (float) @disk_total_space('/');
        $diskFreeBytes = (float) @disk_free_space('/');

        // Recording volume
        $recordingTotalBytes = (float) @disk_total_space('/recordings');
        $recordingFreeBytes = (float) @disk_free_space('/recordings');

        return [
            'disk' => [
                'total_gb' => $diskTotalBytes > 0 ? round($diskTotalBytes / 1073741824, 2) : 0,
                'free_gb' => $diskFreeBytes > 0 ? round($diskFreeBytes / 1073741824, 2) : 0,
                'used_percent' => $diskTotalBytes > 0
                    ? round((1 - $diskFreeBytes / $diskTotalBytes) * 100, 1)
                    : 0,
            ],
            'recordings' => [
                'total_gb' => $recordingTotalBytes > 0 ? round($recordingTotalBytes / 1073741824, 2) : 0,
                'free_gb' => $recordingFreeBytes > 0 ? round($recordingFreeBytes / 1073741824, 2) : 0,
                'used_percent' => $recordingTotalBytes > 0
                    ? round((1 - $recordingFreeBytes / $recordingTotalBytes) * 100, 1)
                    : 0,
            ],
            'memory' => [
                'usage_mb' => round(memory_get_usage(true) / 1048576, 1),
                'peak_mb' => round(memory_get_peak_usage(true) / 1048576, 1),
            ],
            'cpu_load' => function_exists('sys_getloadavg') ? sys_getloadavg() : [0, 0, 0],
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'uptime' => $this->getSystemUptime(),
            'redis' => $this->getRedisInfo(),
        ];
    }

    private function getRedisInfo(): array
    {
        try {
            $info = Redis::connection('default')->info();
            $section = $info['Server'] ?? $info ?? [];

            return [
                'connected_clients' => (int) ($info['Clients']['connected_clients'] ?? $info['connected_clients'] ?? 0),
                'used_memory_human' => $info['Memory']['used_memory_human'] ?? $info['used_memory_human'] ?? 'N/A',
                'uptime_in_days' => (int) ($section['uptime_in_days'] ?? $info['uptime_in_days'] ?? 0),
                'status' => 'connected',
            ];
        } catch (\Throwable) {
            return [
                'connected_clients' => 0,
                'used_memory_human' => 'N/A',
                'uptime_in_days' => 0,
                'status' => 'disconnected',
            ];
        }
    }

    // ─── Alerts ──────────────────────────────────────────────

    private function getAlerts(): array
    {
        $alerts = [];

        // Check stale transcoder workers
        $workers = $this->getCacheValues('transcoding:workers:*');
        foreach ($workers as $data) {
            if (isset($data['updated_at'])) {
                try {
                    $lastUpdate = \Carbon\Carbon::parse($data['updated_at']);
                    if ($lastUpdate->diffInSeconds(now()) > 120) {
                        $workerId = $data['worker_id'] ?? 'desconhecido';
                        $alerts[] = [
                            'type' => 'danger',
                            'title' => 'Worker Transcoder Inativo',
                            'message' => "Worker {$workerId} nao responde ha mais de 2 minutos.",
                            'category' => 'transcoder',
                        ];
                    }
                } catch (\Throwable) {
                    // ignore
                }
            }
        }

        // Check overloaded workers
        foreach ($workers as $data) {
            $active = (int) ($data['active_count'] ?? 0);
            $max = (int) ($data['max_cameras'] ?? 10);
            if ($max > 0 && ($active / $max) >= 0.9) {
                $workerId = $data['worker_id'] ?? 'desconhecido';
                $alerts[] = [
                    'type' => 'warning',
                    'title' => 'Worker Sobrecarregado',
                    'message' => "Worker {$workerId} esta com {$active}/{$max} cameras ({$this->formatPercent($active, $max)}). Considere escalar.",
                    'category' => 'transcoder',
                ];
            }
        }

        // Check cameras with error status
        $cameras = $this->getCacheValues('transcoding:cameras:*');
        $errorCameras = array_filter($cameras, fn ($d) => ($d['status'] ?? '') === 'error');
        if (!empty($errorCameras)) {
            $count = count($errorCameras);
            $alerts[] = [
                'type' => 'danger',
                'title' => 'Transcodificacao com Erro',
                'message' => "{$count} camera(s) com erro de transcodificacao. Verifique os logs.",
                'category' => 'transcoder',
            ];
        }

        // Check queue accumulation
        try {
            $redis = Redis::connection('default');
            $queueNames = ['default', 'recordings', 'transcoding', 'notifications'];
            foreach ($queueNames as $queueName) {
                $size = (int) $redis->llen('queues:' . $queueName);
                if ($size > 50) {
                    $alerts[] = [
                        'type' => 'warning',
                        'title' => 'Fila Acumulando',
                        'message' => "Fila '{$queueName}' com {$size} jobs pendentes. Considere aumentar QUEUE_REPLICAS.",
                        'category' => 'queue',
                    ];
                }
            }
        } catch (\Throwable) {
            // ignore
        }

        // Check Horizon status
        try {
            $horizonPrefix = config('horizon.prefix', 'horizon:');
            $redisConfig = config('database.redis.default');
            /** @var \Redis $rawRedis */
            $rawRedis = new \Redis();
            $rawRedis->connect(
                $redisConfig['host'] ?? '127.0.0.1',
                (int) ($redisConfig['port'] ?? 6379)
            );
            if (!empty($redisConfig['password']) && $redisConfig['password'] !== 'null') {
                $rawRedis->auth($redisConfig['password']);
            }
            $rawRedis->select((int) ($redisConfig['database'] ?? 0));
            $masters = $rawRedis->zRange($horizonPrefix . 'masters', 0, -1);
            $rawRedis->close();

            if (empty($masters)) {
                $alerts[] = [
                    'type' => 'danger',
                    'title' => 'Horizon Inativo',
                    'message' => 'O Laravel Horizon nao esta rodando. As filas nao estao sendo processadas.',
                    'category' => 'queue',
                ];
            }
        } catch (\Throwable) {
            // ignore
        }

        return $alerts;
    }

    // ─── Helpers ──────────────────────────────────────────────

    /**
     * Get cached values by scanning Redis cache DB with a pattern.
     */
    private function getCacheValues(string $cachePattern): array
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
                $results[] = $data;
            }
        }

        return $results;
    }

    private function getSystemUptime(): string
    {
        try {
            if (file_exists('/proc/uptime')) {
                $uptime = (float) explode(' ', file_get_contents('/proc/uptime'))[0];
                $days = floor($uptime / 86400);
                $hours = floor(($uptime % 86400) / 3600);
                $minutes = floor(($uptime % 3600) / 60);

                if ($days > 0) {
                    return "{$days}d {$hours}h {$minutes}m";
                }

                return "{$hours}h {$minutes}m";
            }
        } catch (\Throwable) {
            // ignore
        }

        return 'N/A';
    }

    private function formatPercent(int $value, int $total): string
    {
        if ($total === 0) {
            return '0%';
        }

        return round($value / $total * 100, 0) . '%';
    }
}
