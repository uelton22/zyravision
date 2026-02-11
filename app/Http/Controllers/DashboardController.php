<?php

namespace App\Http\Controllers;

use App\Models\Camera;
use App\Models\Device;
use App\Models\Plan;
use App\Models\Stream;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
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
        $diskTotalBytes = @disk_total_space('/');
        $diskFreeBytes = @disk_free_space('/');
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
