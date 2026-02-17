<?php

namespace App\Http\Controllers;

use App\Models\AnalyticsEvent;
use App\Models\Camera;
use App\Models\RecordingSegment;
use App\Models\StorageBackend;
use App\Models\Stream;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

class AnalyticsController extends Controller
{
    /**
     * Analytics dashboard.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $isSuperAdmin = $user->isSuperAdmin();

        // Resolve tenant
        if ($isSuperAdmin) {
            $tenantId = $request->input('tenant_id');
            $tenant = $tenantId
                ? Tenant::find($tenantId)
                : ($user->tenant ?? Tenant::active()->first());
        } else {
            $tenant = $user->tenant;
        }

        if (! $tenant) {
            abort(403, 'Nenhum tenant associado.');
        }

        $tenants = $isSuperAdmin
            ? Tenant::active()->orderBy('name')->get(['id', 'name'])
            : [];

        // Date range
        $period = $request->input('period', '7d');
        $dateRange = $this->getDateRange($period);

        // Gather all analytics data
        $overview = $this->getOverview($tenant, $dateRange);
        $cameraStats = $this->getCameraStats($tenant, $dateRange);
        $recordingTrends = $this->getRecordingTrends($tenant, $dateRange);
        $storageTrends = $this->getStorageTrends($tenant, $dateRange);
        $recentEvents = $this->getRecentEvents($tenant);
        $systemHealth = $this->getSystemHealth($tenant);
        $activityTimeline = $this->getActivityTimeline($tenant, $dateRange);

        return Inertia::render('Analytics/Index', [
            'overview' => $overview,
            'cameraStats' => $cameraStats,
            'recordingTrends' => $recordingTrends,
            'storageTrends' => $storageTrends,
            'recentEvents' => $recentEvents,
            'systemHealth' => $systemHealth,
            'activityTimeline' => $activityTimeline,
            'tenants' => $tenants,
            'isSuperAdmin' => $isSuperAdmin,
            'currentTenantId' => $tenant->id,
            'currentTenantName' => $tenant->name,
            'period' => $period,
        ]);
    }

    // ─── Data Aggregation Methods ─────────────

    private function getDateRange(string $period): array
    {
        return match ($period) {
            '24h' => [now()->subDay(), now()],
            '7d' => [now()->subDays(7), now()],
            '30d' => [now()->subDays(30), now()],
            '90d' => [now()->subDays(90), now()],
            default => [now()->subDays(7), now()],
        };
    }

    private function getOverview(Tenant $tenant, array $dateRange): array
    {
        $cameras = Camera::where('tenant_id', $tenant->id)->get();
        $totalCameras = $cameras->count();
        $onlineCameras = $cameras->where('is_online', true)->count();
        $recordingCameras = $cameras->where('is_recording', true)->count();

        // Segments in period
        $segmentStats = RecordingSegment::where('tenant_id', $tenant->id)
            ->whereBetween('starts_at', $dateRange)
            ->whereIn('status', [RecordingSegment::STATUS_READY, RecordingSegment::STATUS_UPLOADED])
            ->selectRaw('
                COUNT(*) as total_segments,
                COALESCE(SUM(file_size_bytes), 0) as total_bytes,
                COALESCE(SUM(duration_seconds), 0) as total_duration
            ')
            ->first();

        // Active streams
        $activeStreams = Stream::where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->count();

        // Availability = % cameras online
        $availability = $totalCameras > 0
            ? round(($onlineCameras / $totalCameras) * 100, 1)
            : 0;

        // Storage backends health
        $backendsHealthy = StorageBackend::where('is_active', true)
            ->where('health_status', 'healthy')
            ->count();
        $backendsTotal = StorageBackend::where('is_active', true)->count();

        // Events in period
        $unresolvedEvents = AnalyticsEvent::where('tenant_id', $tenant->id)
            ->unresolved()
            ->count();

        return [
            'total_cameras' => $totalCameras,
            'online_cameras' => $onlineCameras,
            'offline_cameras' => $totalCameras - $onlineCameras,
            'recording_cameras' => $recordingCameras,
            'active_streams' => $activeStreams,
            'availability_percent' => $availability,
            'total_segments' => (int) ($segmentStats->total_segments ?? 0),
            'total_recording_bytes' => (int) ($segmentStats->total_bytes ?? 0),
            'total_recording_hours' => round((int) ($segmentStats->total_duration ?? 0) / 3600, 1),
            'backends_healthy' => $backendsHealthy,
            'backends_total' => $backendsTotal,
            'unresolved_events' => $unresolvedEvents,
        ];
    }

    private function getCameraStats(Tenant $tenant, array $dateRange): array
    {
        $cameras = Camera::where('tenant_id', $tenant->id)
            ->select('id', 'name', 'slug', 'is_online', 'is_recording', 'continuous_recording', 'last_seen_at')
            ->get();

        return $cameras->map(function (Camera $cam) use ($dateRange) {
            $segments = RecordingSegment::where('camera_id', $cam->id)
                ->whereBetween('starts_at', $dateRange)
                ->whereIn('status', [RecordingSegment::STATUS_READY, RecordingSegment::STATUS_UPLOADED])
                ->selectRaw('
                    COUNT(*) as segment_count,
                    COALESCE(SUM(file_size_bytes), 0) as total_bytes,
                    COALESCE(SUM(duration_seconds), 0) as total_duration
                ')
                ->first();

            $streamErrors = Stream::where('camera_id', $cam->id)
                ->whereBetween('started_at', $dateRange)
                ->where('status', 'error')
                ->count();

            // Calculate expected vs actual recording time
            $expectedSeconds = Carbon::parse($dateRange[0])->diffInSeconds(Carbon::parse($dateRange[1]));
            $actualDuration = (int) ($segments->total_duration ?? 0);
            $recordingPercent = $expectedSeconds > 0
                ? round(min(100, ($actualDuration / $expectedSeconds) * 100), 1)
                : 0;

            return [
                'id' => $cam->id,
                'name' => $cam->name,
                'is_online' => $cam->is_online,
                'is_recording' => $cam->is_recording,
                'continuous_recording' => $cam->continuous_recording,
                'last_seen_at' => $cam->last_seen_at?->toIso8601String(),
                'segment_count' => (int) ($segments->segment_count ?? 0),
                'total_bytes' => (int) ($segments->total_bytes ?? 0),
                'total_duration' => (int) ($segments->total_duration ?? 0),
                'recording_percent' => $recordingPercent,
                'stream_errors' => $streamErrors,
            ];
        })->sortByDesc('total_bytes')->values()->toArray();
    }

    private function getRecordingTrends(Tenant $tenant, array $dateRange): array
    {
        $data = RecordingSegment::where('tenant_id', $tenant->id)
            ->whereBetween('starts_at', $dateRange)
            ->whereIn('status', [RecordingSegment::STATUS_READY, RecordingSegment::STATUS_UPLOADED])
            ->selectRaw("
                DATE(starts_at) as date,
                COUNT(*) as segments,
                COALESCE(SUM(file_size_bytes), 0) as bytes,
                COALESCE(SUM(duration_seconds), 0) as duration
            ")
            ->groupByRaw('DATE(starts_at)')
            ->orderBy('date')
            ->get();

        return $data->map(fn ($row) => [
            'date' => $row->date,
            'segments' => (int) $row->segments,
            'bytes' => (int) $row->bytes,
            'duration_hours' => round((int) $row->duration / 3600, 1),
            'gb' => round((int) $row->bytes / (1024 ** 3), 2),
        ])->toArray();
    }

    private function getStorageTrends(Tenant $tenant, array $dateRange): array
    {
        // Cumulative storage by day
        $data = RecordingSegment::where('tenant_id', $tenant->id)
            ->whereIn('status', [RecordingSegment::STATUS_READY, RecordingSegment::STATUS_UPLOADED])
            ->where('starts_at', '<=', $dateRange[1])
            ->selectRaw("
                DATE(starts_at) as date,
                COALESCE(SUM(file_size_bytes), 0) as bytes_added
            ")
            ->groupByRaw('DATE(starts_at)')
            ->orderBy('date')
            ->get();

        $cumulative = 0;
        $result = [];
        foreach ($data as $row) {
            $cumulative += (int) $row->bytes_added;
            if ($row->date >= Carbon::parse($dateRange[0])->format('Y-m-d')) {
                $result[] = [
                    'date' => $row->date,
                    'cumulative_gb' => round($cumulative / (1024 ** 3), 2),
                    'daily_gb' => round((int) $row->bytes_added / (1024 ** 3), 2),
                ];
            }
        }

        return $result;
    }

    private function getRecentEvents(Tenant $tenant): array
    {
        // Generate events from real-time data if table is empty
        $events = AnalyticsEvent::where('tenant_id', $tenant->id)
            ->orderByDesc('occurred_at')
            ->limit(20)
            ->get();

        if ($events->isEmpty()) {
            // Generate synthetic events from current state
            return $this->generateLiveEvents($tenant);
        }

        return $events->map(fn (AnalyticsEvent $e) => [
            'id' => $e->id,
            'event_type' => $e->event_type,
            'severity' => $e->severity,
            'description' => $e->description,
            'camera_name' => $e->camera?->name,
            'occurred_at' => $e->occurred_at->toIso8601String(),
            'is_resolved' => $e->is_resolved,
        ])->toArray();
    }

    private function generateLiveEvents(Tenant $tenant): array
    {
        $events = [];

        // Camera status events
        $cameras = Camera::where('tenant_id', $tenant->id)->get();
        foreach ($cameras as $cam) {
            if ($cam->is_online && $cam->is_recording) {
                $events[] = [
                    'id' => $cam->id . '-online',
                    'event_type' => 'camera_online',
                    'severity' => 'info',
                    'description' => "{$cam->name} esta online e gravando.",
                    'camera_name' => $cam->name,
                    'occurred_at' => ($cam->last_seen_at ?? now())->toIso8601String(),
                    'is_resolved' => true,
                ];
            } elseif (! $cam->is_online) {
                $events[] = [
                    'id' => $cam->id . '-offline',
                    'event_type' => 'camera_offline',
                    'severity' => 'critical',
                    'description' => "{$cam->name} esta offline.",
                    'camera_name' => $cam->name,
                    'occurred_at' => ($cam->last_seen_at ?? now())->toIso8601String(),
                    'is_resolved' => false,
                ];
            }
        }

        // Storage warnings
        $plan = $tenant->currentPlan();
        $limitGb = $plan?->max_storage_gb ?? 50;
        $usedBytes = RecordingSegment::where('tenant_id', $tenant->id)
            ->whereIn('status', [RecordingSegment::STATUS_READY, RecordingSegment::STATUS_UPLOADED])
            ->sum('file_size_bytes');
        $usedGb = $usedBytes / (1024 ** 3);
        $usedPercent = $limitGb > 0 ? ($usedGb / $limitGb) * 100 : 0;

        if ($usedPercent > 80) {
            $events[] = [
                'id' => 'storage-warning',
                'event_type' => $usedPercent > 95 ? 'storage_critical' : 'storage_warning',
                'severity' => $usedPercent > 95 ? 'critical' : 'warning',
                'description' => "Armazenamento em " . round($usedPercent, 1) . "% da capacidade ({$limitGb} GB).",
                'camera_name' => null,
                'occurred_at' => now()->toIso8601String(),
                'is_resolved' => false,
            ];
        }

        // Stream error events
        $errorStreams = Stream::where('tenant_id', $tenant->id)
            ->where('status', 'error')
            ->with('camera:id,name')
            ->latest()
            ->limit(5)
            ->get();

        foreach ($errorStreams as $stream) {
            $cameraName = $stream->camera?->name ?? 'Camera removida';
            $events[] = [
                'id' => $stream->id . '-error',
                'event_type' => 'stream_error',
                'severity' => 'warning',
                'description' => "Stream de {$cameraName} com erro: " . ($stream->error_message ?? 'desconhecido'),
                'camera_name' => $cameraName,
                'occurred_at' => ($stream->updated_at ?? now())->toIso8601String(),
                'is_resolved' => false,
            ];
        }

        return collect($events)->sortByDesc('occurred_at')->values()->take(20)->toArray();
    }

    private function getSystemHealth(Tenant $tenant): array
    {
        // Storage backends
        $backends = StorageBackend::where('is_active', true)->get();

        $backendHealth = $backends->map(fn ($b) => [
            'name' => $b->name,
            'driver' => $b->driver,
            'health_status' => $b->health_status ?? 'unknown',
            'is_default' => $b->is_default,
        ])->toArray();

        // Recent segment activity (last hour)
        $recentSegments = RecordingSegment::where('tenant_id', $tenant->id)
            ->where('starts_at', '>=', now()->subHour())
            ->whereIn('status', [RecordingSegment::STATUS_READY, RecordingSegment::STATUS_UPLOADED])
            ->count();

        // Total streams
        $totalStreams = Stream::where('tenant_id', $tenant->id)->count();
        $activeStreams = Stream::where('tenant_id', $tenant->id)->where('status', 'active')->count();
        $errorStreams = Stream::where('tenant_id', $tenant->id)->where('status', 'error')->count();

        return [
            'backends' => $backendHealth,
            'recent_segments_1h' => $recentSegments,
            'total_streams' => $totalStreams,
            'active_streams' => $activeStreams,
            'error_streams' => $errorStreams,
        ];
    }

    private function getActivityTimeline(Tenant $tenant, array $dateRange): array
    {
        try {
            // Use raw query to handle UUID/bigint type mismatch
            $userIds = \App\Models\User::where('tenant_id', $tenant->id)->pluck('id')->toArray();

            $activities = Activity::whereBetween('created_at', $dateRange)
                ->where(function ($query) use ($tenant, $userIds) {
                    $query->whereRaw("properties::text LIKE ?", ["%{$tenant->id}%"]);
                    if (! empty($userIds)) {
                        $query->orWhereRaw(
                            "causer_id::text IN (" . implode(',', array_fill(0, count($userIds), '?')) . ")",
                            $userIds
                        );
                    }
                })
                ->orderByDesc('created_at')
                ->limit(30)
                ->get();

            return $activities->map(fn (Activity $a) => [
                'id' => $a->id,
                'description' => $a->description,
                'event' => $a->event,
                'subject_type' => class_basename($a->subject_type ?? ''),
                'causer_name' => $a->causer?->name ?? 'Sistema',
                'created_at' => $a->created_at->toIso8601String(),
            ])->toArray();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Recording heatmap — hours of day with recording data.
     */
    public function heatmap(Request $request): Response
    {
        $user = $request->user();
        $tenant = $user->tenant;

        if (! $tenant) {
            abort(403);
        }

        $cameraId = $request->input('camera_id');
        $days = (int) $request->input('days', 7);

        $query = RecordingSegment::where('tenant_id', $tenant->id)
            ->where('starts_at', '>=', now()->subDays($days))
            ->whereIn('status', [RecordingSegment::STATUS_READY, RecordingSegment::STATUS_UPLOADED]);

        if ($cameraId) {
            $query->where('camera_id', $cameraId);
        }

        $data = $query
            ->selectRaw("
                DATE(starts_at) as day,
                EXTRACT(HOUR FROM starts_at) as hour,
                COUNT(*) as segments,
                COALESCE(SUM(duration_seconds), 0) as duration
            ")
            ->groupByRaw('DATE(starts_at), EXTRACT(HOUR FROM starts_at)')
            ->orderBy('day')
            ->get();

        return Inertia::render('Analytics/Heatmap', [
            'heatmapData' => $data->toArray(),
            'days' => $days,
        ]);
    }
}
