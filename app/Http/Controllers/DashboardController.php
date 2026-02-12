<?php

namespace App\Http\Controllers;

use App\Models\Camera;
use App\Models\Device;
use App\Models\Plan;
use App\Models\Stream;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        if ($user->isSuperAdmin()) {
            return $this->adminDashboard();
        }

        return $this->tenantDashboard($user);
    }

    private function adminDashboard(): Response
    {
        // Platform-wide stats
        $totalCameras = Camera::count();
        $camerasOnline = Camera::where('is_active', true)->where('is_online', true)->count();
        $camerasOffline = Camera::where('is_active', true)->where('is_online', false)->count();
        $camerasRecording = Camera::where('is_recording', true)->count();
        $totalDevices = Device::count();
        $devicesOnline = Device::where('is_online', true)->count();
        $totalTenants = Tenant::count();
        $tenantsActive = Tenant::where('is_active', true)->count();
        $totalUsers = User::count();
        $totalPlans = Plan::count();

        // System health
        $diskTotalBytes = (float) @disk_total_space('/');
        $diskFreeBytes = (float) @disk_free_space('/');
        $systemHealth = [
            'disk_total_gb' => $diskTotalBytes ? round($diskTotalBytes / 1073741824, 2) : 0,
            'disk_free_gb' => $diskFreeBytes ? round($diskFreeBytes / 1073741824, 2) : 0,
            'disk_used_percent' => ($diskTotalBytes && $diskTotalBytes > 0)
                ? round((1 - $diskFreeBytes / $diskTotalBytes) * 100, 1)
                : 0,
            'memory_usage_mb' => round(memory_get_usage(true) / 1048576, 1),
            'memory_peak_mb' => round(memory_get_peak_usage(true) / 1048576, 1),
            'cpu_load' => function_exists('sys_getloadavg') ? sys_getloadavg() : [0, 0, 0],
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'uptime' => $this->getSystemUptime(),
        ];

        // Recent activity logs
        $recentLogs = Activity::latest()
            ->take(15)
            ->with('causer:id,name,email')
            ->get()
            ->map(fn ($log) => [
                'id' => $log->id,
                'description' => $log->description,
                'event' => $log->event,
                'subject_type' => class_basename($log->subject_type ?? ''),
                'causer_name' => $log->causer?->name ?? 'Sistema',
                'created_at' => $log->created_at?->format('d/m/Y H:i'),
                'created_at_diff' => $log->created_at?->diffForHumans(),
            ]);

        // Top tenants by camera count
        $topTenants = Tenant::withCount('cameras')
            ->orderByDesc('cameras_count')
            ->take(5)
            ->get(['id', 'name', 'is_active']);

        // Active streams count
        $activeStreams = Stream::running()->count();

        // All active cameras for live grid (with latest stream info including error)
        $liveCameras = Camera::where('is_active', true)
            ->with(['tenant:id,name', 'latestStream:id,camera_id,status,hls_url,error_message'])
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'is_online', 'is_recording', 'snapshot_url', 'hls_path', 'location', 'tenant_id']);

        return Inertia::render('Dashboard/Admin', [
            'stats' => [
                'total_cameras' => $totalCameras,
                'cameras_online' => $camerasOnline,
                'cameras_offline' => $camerasOffline,
                'cameras_recording' => $camerasRecording,
                'total_devices' => $totalDevices,
                'devices_online' => $devicesOnline,
                'total_tenants' => $totalTenants,
                'tenants_active' => $tenantsActive,
                'total_users' => $totalUsers,
                'total_plans' => $totalPlans,
                'active_streams' => $activeStreams,
            ],
            'systemHealth' => $systemHealth,
            'recentLogs' => $recentLogs,
            'topTenants' => $topTenants,
            'liveCameras' => $liveCameras,
            'workersSummary' => $this->getWorkersSummary(),
        ]);
    }

    private function tenantDashboard(User $user): Response
    {
        $tenant = $user->tenant;
        $tenantId = $user->tenant_id;

        $quotas = $tenant ? $tenant->getQuotaSummary() : [];
        $subscription = $tenant?->activeSubscription?->load('plan');

        $cameraQuery = Camera::where('tenant_id', $tenantId);
        $camerasOnline = (clone $cameraQuery)->where('is_active', true)->where('is_online', true)->count();
        $camerasOffline = (clone $cameraQuery)->where('is_active', true)->where('is_online', false)->count();
        $camerasRecording = (clone $cameraQuery)->where('is_recording', true)->count();

        $liveCameras = Camera::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with('latestStream:id,camera_id,status,hls_url,error_message')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'is_online', 'is_recording', 'snapshot_url', 'hls_path', 'location']);

        return Inertia::render('Dashboard/Index', [
            'stats' => [
                'cameras_online' => $camerasOnline,
                'cameras_offline' => $camerasOffline,
                'cameras_recording' => $camerasRecording,
                'alerts_today' => 0,
                'storage_used_gb' => $quotas['storage_gb']['used'] ?? 0,
                'storage_limit_gb' => $quotas['storage_gb']['limit'] ?? 0,
                'users_active' => $quotas['users']['used'] ?? 0,
                'users_limit' => $quotas['users']['limit'] ?? 0,
            ],
            'quotas' => $quotas,
            'subscription' => $subscription,
            'plan' => $tenant?->plan,
            'liveCameras' => $liveCameras,
        ]);
    }

    /**
     * Resumo dos workers para o widget do Dashboard.
     */
    private function getWorkersSummary(): array
    {
        try {
            // Transcoder workers
            $workers = $this->getCacheValues('transcoding:workers:*');
            $transcoderCount = count($workers);
            $healthyCount = 0;
            $totalActive = 0;
            $totalMax = 0;

            foreach ($workers as $data) {
                $isStale = false;
                if (isset($data['updated_at'])) {
                    try {
                        $lastUpdate = \Carbon\Carbon::parse($data['updated_at']);
                        $isStale = $lastUpdate->diffInSeconds(now()) > 60;
                    } catch (\Throwable) {
                        $isStale = true;
                    }
                }
                if (! $isStale) {
                    $healthyCount++;
                }
                $totalActive += (int) ($data['active_count'] ?? 0);
                $totalMax += (int) ($data['max_cameras'] ?? 10);
            }

            // Cameras in transcoding
            $cameras = $this->getCacheValues('transcoding:cameras:*');
            $transcodingCameras = count(array_filter($cameras, fn ($d) => in_array($d['status'] ?? '', ['pending', 'running'])));

            // Queue status (Horizon) — Horizon uses keys without Redis connection prefix
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
            $queueStatus = ! empty($masters) ? 'running' : 'inactive';

            $supervisorNames = $rawRedis->zRange($horizonPrefix . 'supervisors', 0, -1);
            $totalProcesses = 0;
            foreach ($supervisorNames as $name) {
                $info = $rawRedis->hGetAll($horizonPrefix . 'supervisor:' . $name);
                $processesData = json_decode($info['processes'] ?? '{}', true);
                $totalProcesses += is_array($processesData) ? array_sum($processesData) : 0;
            }

            $recentFailed = (int) $rawRedis->zCard($horizonPrefix . 'recent_failed_jobs');
            $rawRedis->close();

            // Pending jobs (use Laravel Redis — queue keys DO have the connection prefix)
            $redis = Redis::connection('default');
            $queueNames = ['default', 'recordings', 'streaming', 'transcoding', 'notifications'];
            $totalPending = 0;
            foreach ($queueNames as $queueName) {
                $totalPending += (int) $redis->llen('queues:' . $queueName);
            }

            // Alerts
            $alertsCount = 0;
            $alertsCritical = 0;

            // Check stale workers
            foreach ($workers as $data) {
                if (isset($data['updated_at'])) {
                    try {
                        $lastUpdate = \Carbon\Carbon::parse($data['updated_at']);
                        if ($lastUpdate->diffInSeconds(now()) > 120) {
                            $alertsCount++;
                            $alertsCritical++;
                        }
                    } catch (\Throwable) {
                        // ignore
                    }
                }
            }

            // Check queue accumulation
            if ($totalPending > 50) {
                $alertsCount++;
            }
            if (empty($masters)) {
                $alertsCount++;
                $alertsCritical++;
            }

            // Check error cameras
            $errorCameras = count(array_filter($cameras, fn ($d) => ($d['status'] ?? '') === 'error'));
            if ($errorCameras > 0) {
                $alertsCount++;
                $alertsCritical++;
            }

            return [
                'transcoder_count' => $transcoderCount,
                'transcoder_healthy' => $healthyCount,
                'transcoding_cameras' => $transcodingCameras,
                'transcoder_capacity_percent' => $totalMax > 0 ? round($totalActive / $totalMax * 100, 0) : 0,
                'queue_status' => $queueStatus,
                'queue_processes' => $totalProcesses,
                'queue_jobs_pending' => $totalPending,
                'queue_failed' => $recentFailed,
                'alerts_count' => $alertsCount,
                'alerts_critical' => $alertsCritical,
            ];
        } catch (\Throwable) {
            return [
                'transcoder_count' => 0,
                'transcoder_healthy' => 0,
                'transcoding_cameras' => 0,
                'transcoder_capacity_percent' => 0,
                'queue_status' => 'error',
                'queue_processes' => 0,
                'queue_jobs_pending' => 0,
                'queue_failed' => 0,
                'alerts_count' => 1,
                'alerts_critical' => 1,
            ];
        }
    }

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
}
