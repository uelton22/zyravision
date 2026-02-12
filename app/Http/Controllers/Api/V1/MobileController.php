<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CameraResource;
use App\Models\Camera;
use App\Models\Recording;
use App\Models\RecordingSegment;
use App\Models\Stream;
use App\Traits\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileController extends Controller
{
    use ApiResponder;

    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenantId = $user->tenant_id;

        // Stats
        $totalCameras = Camera::where('tenant_id', $tenantId)->count();
        $onlineCameras = Camera::where('tenant_id', $tenantId)->where('is_online', true)->count();
        $recordingCameras = Camera::where('tenant_id', $tenantId)->where('is_recording', true)->count();
        $activeStreams = Stream::where('tenant_id', $tenantId)->running()->count();

        // Storage
        $usedBytes = Recording::where('tenant_id', $tenantId)
            ->whereIn('status', ['recording', 'completed'])
            ->sum('file_size_bytes');
        $usedGb = round($usedBytes / (1024 * 1024 * 1024), 2);

        // Cameras with active streams (for live view)
        $cameras = Camera::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with(['activeStream', 'device:id,name,manufacturer'])
            ->orderBy('name')
            ->get();

        // Recent events (last 20 recordings that started/stopped)
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
            'stats' => [
                'total_cameras' => $totalCameras,
                'online_cameras' => $onlineCameras,
                'offline_cameras' => $totalCameras - $onlineCameras,
                'recording_cameras' => $recordingCameras,
                'active_streams' => $activeStreams,
                'storage_used_gb' => $usedGb,
            ],
            'cameras' => CameraResource::collection($cameras),
            'recent_events' => $recentEvents,
            'synced_at' => now()->toISOString(),
        ]);
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
        // Mobile can push device info, preferences, etc.
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
