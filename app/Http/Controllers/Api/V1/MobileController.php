<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CameraResource;
use App\Models\Camera;
use App\Models\Device;
use App\Models\Recording;
use App\Models\RecordingSegment;
use App\Models\Stream;
use App\Models\Tenant;
use App\Models\User;
use App\Traits\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class MobileController extends Controller
{
    use ApiResponder;

    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->isSuperAdmin()) {
            return $this->adminDashboard($user);
        }

        return $this->tenantDashboard($user);
    }

    /**
     * Dashboard for super-admin: platform-wide stats, system health, workers.
     */
    private function adminDashboard(User $user): JsonResponse
    {
        // Platform-wide camera stats
        $totalCameras = Camera::count();
        $onlineCameras = Camera::where('is_active', true)->where('is_online', true)->count();
        $offlineCameras = Camera::where('is_active', true)->where('is_online', false)->count();
        $recordingCameras = Camera::where('is_recording', true)->count();
        $totalDevices = Device::count();
        $devicesOnline = Device::where('is_online', true)->count();
        $totalTenants = Tenant::count();
        $tenantsActive = Tenant::where('is_active', true)->count();
        $totalUsers = User::count();
        $activeStreams = Stream::running()->count();

        // System health
        $systemHealth = $this->getSystemHealth();

        // Workers summary
        $workers = $this->getWorkersSummary();

        // Cameras for live grid
        $cameras = Camera::where('is_active', true)
            ->with(['activeStream', 'device:id,name,manufacturer', 'tenant:id,name'])
            ->orderBy('name')
            ->get();

        // Recent events
        $recentEvents = Recording::with('camera:id,name,slug')
            ->orderByDesc('started_at')
            ->limit(20)
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'type' => $r->status === 'recording' ? 'recording_started' : 'recording_completed',
                'camera_id' => $r->camera_id,
                'camera_name' => $r->camera?->name,
                'started_at' => $r->started_at?->toISOString(),
                'ended_at' => $r->ended_at?->toISOString(),
                'duration_seconds' => $r->duration_seconds,
            ]);

        return $this->success([
            'role' => 'super-admin',
            'stats' => [
                'total_cameras' => $totalCameras,
                'online_cameras' => $onlineCameras,
                'offline_cameras' => $offlineCameras,
                'recording_cameras' => $recordingCameras,
                'active_streams' => $activeStreams,
                'total_devices' => $totalDevices,
                'devices_online' => $devicesOnline,
                'total_tenants' => $totalTenants,
                'tenants_active' => $tenantsActive,
                'total_users' => $totalUsers,
            ],
            'system_health' => $systemHealth,
            'workers' => $workers,
            'cameras' => CameraResource::collection($cameras),
            'recent_events' => $recentEvents,
            'synced_at' => now()->toISOString(),
        ]);
    }

    /**
     * Dashboard for regular tenant users: tenant-scoped stats, quotas, plan.
     */
    private function tenantDashboard(User $user): JsonResponse
    {
        $tenantId = $user->tenant_id;
        $tenant = $user->tenant;

        // Camera stats (tenant-scoped)
        $totalCameras = Camera::where('tenant_id', $tenantId)->count();
        $onlineCameras = Camera::where('tenant_id', $tenantId)
            ->where('is_active', true)->where('is_online', true)->count();
        $offlineCameras = Camera::where('tenant_id', $tenantId)
            ->where('is_active', true)->where('is_online', false)->count();
        $recordingCameras = Camera::where('tenant_id', $tenantId)
            ->where('is_recording', true)->count();
        $activeStreams = Stream::where('tenant_id', $tenantId)->running()->count();

        // Storage (from recordings + segments)
        $usedBytesRec = Recording::where('tenant_id', $tenantId)
            ->whereIn('status', ['recording', 'completed'])
            ->sum('file_size_bytes');
        $usedBytesSeg = RecordingSegment::where('tenant_id', $tenantId)
            ->where('status', '!=', 'deleted')
            ->sum('file_size_bytes');
        $usedBytes = max($usedBytesRec, $usedBytesSeg);
        $storageUsedGb = round($usedBytes / (1024 * 1024 * 1024), 2);

        // Quotas from tenant
        $quotas = $tenant ? $tenant->getQuotaSummary() : [];

        // Plan/Subscription info
        $subscription = $tenant?->activeSubscription?->load('plan');
        $plan = $subscription?->plan ?? $tenant?->currentPlan();

        $planInfo = null;
        if ($plan) {
            $planInfo = [
                'name' => $plan->name,
                'price' => $plan->price,
                'billing_cycle' => $plan->billing_cycle ?? 'monthly',
                'max_cameras' => $plan->max_cameras,
                'max_users' => $plan->max_users,
                'max_storage_gb' => $plan->max_storage_gb,
                'retention_days' => $plan->retention_days,
                'max_concurrent_streams' => $plan->max_concurrent_streams,
                'max_resolution' => $plan->max_resolution,
                'max_fps' => $plan->max_fps,
                'has_ai_detection' => (bool) $plan->has_ai_detection,
                'has_mobile_app' => (bool) $plan->has_mobile_app,
                'has_api_access' => (bool) $plan->has_api_access,
            ];
        }

        $storageTotalGb = (float) ($quotas['storage_gb']['limit'] ?? 0);

        // Resource quotas formatted for mobile
        $resourceQuotas = [
            'cameras' => [
                'used' => $quotas['cameras']['used'] ?? $totalCameras,
                'limit' => $quotas['cameras']['limit'] ?? 0,
            ],
            'users' => [
                'used' => $quotas['users']['used'] ?? 0,
                'limit' => $quotas['users']['limit'] ?? 0,
            ],
            'storage_gb' => [
                'used' => $storageUsedGb,
                'limit' => $storageTotalGb,
            ],
            'streams' => [
                'used' => $activeStreams,
                'limit' => $quotas['streams']['limit'] ?? 0,
            ],
        ];

        // Cameras with active streams (for live view)
        $cameras = Camera::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with(['activeStream', 'device:id,name,manufacturer'])
            ->orderBy('name')
            ->get();

        // Recent events
        $recentEvents = Recording::where('tenant_id', $tenantId)
            ->with('camera:id,name,slug')
            ->orderByDesc('started_at')
            ->limit(20)
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'type' => $r->status === 'recording' ? 'recording_started' : 'recording_completed',
                'camera_id' => $r->camera_id,
                'camera_name' => $r->camera?->name,
                'started_at' => $r->started_at?->toISOString(),
                'ended_at' => $r->ended_at?->toISOString(),
                'duration_seconds' => $r->duration_seconds,
            ]);

        return $this->success([
            'role' => 'tenant',
            'stats' => [
                'total_cameras' => $totalCameras,
                'online_cameras' => $onlineCameras,
                'offline_cameras' => $offlineCameras,
                'recording_cameras' => $recordingCameras,
                'active_streams' => $activeStreams,
                'storage_used_gb' => $storageUsedGb,
                'storage_total_gb' => $storageTotalGb,
            ],
            'quotas' => $resourceQuotas,
            'plan' => $planInfo,
            'cameras' => CameraResource::collection($cameras),
            'recent_events' => $recentEvents,
            'synced_at' => now()->toISOString(),
        ]);
    }

    /**
     * System health metrics (CPU, memory, disk, uptime).
     */
    private function getSystemHealth(): array
    {
        $diskTotalBytes = (float) @disk_total_space('/');
        $diskFreeBytes = (float) @disk_free_space('/');

        return [
            'cpu_load' => function_exists('sys_getloadavg') ? sys_getloadavg() : [0, 0, 0],
            'memory_usage_mb' => round(memory_get_usage(true) / 1048576, 1),
            'memory_peak_mb' => round(memory_get_peak_usage(true) / 1048576, 1),
            'disk_total_gb' => $diskTotalBytes ? round($diskTotalBytes / 1073741824, 2) : 0,
            'disk_free_gb' => $diskFreeBytes ? round($diskFreeBytes / 1073741824, 2) : 0,
            'disk_used_percent' => ($diskTotalBytes > 0)
                ? round((1 - $diskFreeBytes / $diskTotalBytes) * 100, 1)
                : 0,
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'uptime' => $this->getSystemUptime(),
        ];
    }

    /**
     * Workers summary (transcoders, queues, alerts).
     */
    private function getWorkersSummary(): array
    {
        try {
            $cachePrefix = config('cache.prefix', '');

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

            // Queue status via Horizon
            $horizonPrefix = config('horizon.prefix', 'horizon:');
            $redisConfig = config('database.redis.default');
            $rawRedis = new \Redis();
            $rawRedis->connect(
                $redisConfig['host'] ?? '127.0.0.1',
                (int) ($redisConfig['port'] ?? 6379)
            );
            if (! empty($redisConfig['password']) && $redisConfig['password'] !== 'null') {
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

            // Pending jobs
            $redis = Redis::connection('default');
            $queueNames = ['default', 'recordings', 'streaming', 'transcoding', 'notifications'];
            $totalPending = 0;
            foreach ($queueNames as $queueName) {
                $totalPending += (int) $redis->llen('queues:' . $queueName);
            }

            // Alerts
            $alertsCount = 0;
            if (empty($masters)) {
                $alertsCount++;
            }
            if ($totalPending > 50) {
                $alertsCount++;
            }

            return [
                'transcoder_count' => $transcoderCount,
                'transcoder_healthy' => $healthyCount,
                'transcoder_capacity_percent' => $totalMax > 0 ? round($totalActive / $totalMax * 100, 0) : 0,
                'queue_status' => $queueStatus,
                'queue_processes' => $totalProcesses,
                'queue_jobs_pending' => $totalPending,
                'queue_recent_failed' => $recentFailed,
                'alerts_count' => $alertsCount,
            ];
        } catch (\Throwable $e) {
            return [
                'transcoder_count' => 0,
                'transcoder_healthy' => 0,
                'transcoder_capacity_percent' => 0,
                'queue_status' => 'unknown',
                'queue_processes' => 0,
                'queue_jobs_pending' => 0,
                'queue_recent_failed' => 0,
                'alerts_count' => 0,
            ];
        }
    }

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

    public function syncStatus(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenantId = $user->tenant_id;

        $lastSync = $request->query('since');

        $camerasUpdated = Camera::where('tenant_id', $tenantId)
            ->when($lastSync, fn ($q) => $q->where('updated_at', '>', $lastSync))
            ->count();

        $recordingsUpdated = Recording::where('tenant_id', $tenantId)
            ->when($lastSync, fn ($q) => $q->where('updated_at', '>', $lastSync))
            ->count();

        return $this->success([
            'has_changes' => ($camerasUpdated + $recordingsUpdated) > 0,
            'cameras_updated' => $camerasUpdated,
            'recordings_updated' => $recordingsUpdated,
            'server_time' => now()->toISOString(),
        ]);
    }

    public function syncPull(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenantId = $user->tenant_id;

        $since = $request->query('since');

        $cameras = Camera::where('tenant_id', $tenantId)
            ->when($since, fn ($q) => $q->where('updated_at', '>', $since))
            ->with(['activeStream', 'device:id,name'])
            ->get();

        $recordings = Recording::where('tenant_id', $tenantId)
            ->when($since, fn ($q) => $q->where('updated_at', '>', $since))
            ->with('camera:id,name')
            ->orderByDesc('started_at')
            ->limit(100)
            ->get();

        return $this->success([
            'cameras' => CameraResource::collection($cameras),
            'recordings' => $recordings->map(fn ($r) => [
                'id' => $r->id,
                'camera_id' => $r->camera_id,
                'camera_name' => $r->camera?->name,
                'status' => $r->status,
                'started_at' => $r->started_at?->toISOString(),
                'ended_at' => $r->ended_at?->toISOString(),
                'duration_seconds' => $r->duration_seconds,
                'file_size_bytes' => $r->file_size_bytes,
            ]),
            'synced_at' => now()->toISOString(),
        ]);
    }

    public function syncPush(Request $request): JsonResponse
    {
        $request->validate([
            'device_info' => 'nullable|array',
            'preferences' => 'nullable|array',
        ]);

        return $this->success([
            'message' => 'Sync data received.',
            'synced_at' => now()->toISOString(),
        ]);
    }
}
