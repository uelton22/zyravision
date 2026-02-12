<?php

namespace App\Http\Controllers;

use App\Jobs\StartStreamJob;
use App\Models\Camera;
use App\Models\Device;
use App\Models\Recording;
use App\Models\RecordingSegment;
use App\Models\Tenant;
use App\Services\Camera\CameraCleanupService;
use App\Services\Camera\StreamUrlResolver;
use App\Services\Manufacturers\ManufacturerResolver;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class CameraController extends Controller
{
    public function __construct(
        private ManufacturerResolver $manufacturerResolver
    ) {}

    public function index(Request $request): Response
    {
        $currentUser = $request->user();
        $isSuperAdmin = $currentUser->isSuperAdmin();

        $query = Camera::query()->with('device:id,name,type,manufacturer');

        if ($isSuperAdmin) {
            $query->with('tenant:id,name');
            if ($tenantId = $request->input('tenant_id')) {
                $query->where('tenant_id', $tenantId);
            }
        } else {
            $query->forTenant($currentUser->tenant_id);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('address', 'ilike', "%{$search}%")
                  ->orWhere('location', 'ilike', "%{$search}%");
            });
        }

        if ($request->has('is_active') && $request->input('is_active') !== '') {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($deviceId = $request->input('device_id')) {
            $query->where('device_id', $deviceId);
        }

        if ($channelType = $request->input('channel_type')) {
            $query->where('channel_type', $channelType);
        }

        $cameras = $query->orderBy('name')
            ->paginate($request->input('per_page', 15))
            ->withQueryString();

        $tenants = $isSuperAdmin
            ? Tenant::orderBy('name')->get(['id', 'name'])
            : [];

        return Inertia::render('Cameras/Index', [
            'cameras' => $cameras,
            'filters' => $request->only(['search', 'is_active', 'device_id', 'channel_type', 'tenant_id']),
            'tenants' => $tenants,
            'isSuperAdmin' => $isSuperAdmin,
            'canManage' => $currentUser->hasRole(['admin', 'super-admin']),
        ]);
    }

    public function create(Request $request): Response
    {
        $currentUser = $request->user();
        $isSuperAdmin = $currentUser->isSuperAdmin();

        $tenantId = $isSuperAdmin
            ? $request->input('tenant_id')
            : $currentUser->tenant_id;

        $tenant = $tenantId ? Tenant::find($tenantId) : null;
        $quotaReached = $tenant ? $tenant->hasReachedCameraLimit() : false;

        $devices = $tenantId
            ? Device::where('tenant_id', $tenantId)
                ->active()
                ->orderBy('name')
                ->get(['id', 'name', 'type', 'manufacturer', 'total_channels', 'address', 'port'])
            : collect([]);

        $tenants = $isSuperAdmin
            ? Tenant::active()->orderBy('name')->get(['id', 'name'])
            : [];

        return Inertia::render('Cameras/Create', [
            'manufacturers' => $this->manufacturerResolver->getManufacturerList(),
            'devices' => $devices,
            'tenants' => $tenants,
            'isSuperAdmin' => $isSuperAdmin,
            'defaultTenantId' => $tenantId,
            'defaultDeviceId' => $request->input('device_id'),
            'quotaReached' => $quotaReached,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $currentUser = $request->user();
        $isSuperAdmin = $currentUser->isSuperAdmin();

        $rules = [
            'name' => 'required|string|max:255',
            'device_id' => 'nullable|uuid|exists:devices,id',
            'channel_number' => 'nullable|integer|min:1|max:128',
            'channel_type' => 'required|string|in:analog,ip,direct',
            'manufacturer' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:255',
            'address_type' => 'required_without:device_id|nullable|string|in:ipv4,ipv6,ddns,hostname',
            'address' => 'required_without:device_id|nullable|string|max:255',
            'port' => 'integer|min:1|max:65535',
            'secondary_address' => 'nullable|string|max:255',
            'transport' => 'string|in:tcp,udp,auto',
            'username' => 'nullable|string|max:255',
            'password' => 'nullable|string|max:255',
            'stream_type' => 'string|in:main,sub,mobile',
            'rtsp_path' => 'nullable|string|max:500',
            'has_ptz' => 'boolean',
            'has_audio' => 'boolean',
            'is_active' => 'boolean',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'ddns_provider' => 'nullable|string|max:100',
            'ddns_hostname' => 'nullable|string|max:255',
            'ipv6_preferred' => 'boolean',
            'recording_quality' => 'nullable|string|in:original,high,medium,low,custom',
            'recording_resolution' => 'nullable|required_if:recording_quality,custom|string|in:3840x2160,2560x1440,1920x1080,1280x720,640x480,480x360',
            'recording_codec' => 'nullable|required_if:recording_quality,custom|string|in:copy,h264,h265',
            'recording_fps' => 'nullable|required_if:recording_quality,custom|integer|min:5|max:60',
            'recording_bitrate' => 'nullable|required_if:recording_quality,custom|string|in:8M,6M,4M,2M,1M,512K',
        ];

        if ($isSuperAdmin) {
            $rules['tenant_id'] = 'required|uuid|exists:tenants,id';
        }

        $validated = $request->validate($rules);

        // Normalize rtsp_path: ensure starts with /
        if (! empty($validated['rtsp_path'])) {
            $path = trim($validated['rtsp_path']);
            if (! str_starts_with($path, '/')) {
                $path = '/' . $path;
            }
            $validated['rtsp_path'] = $path;
        } else {
            $validated['rtsp_path'] = null;
        }

        $tenantId = $isSuperAdmin
            ? $validated['tenant_id']
            : $currentUser->tenant_id;

        // Enforce quota
        $tenant = Tenant::find($tenantId);
        if ($tenant && $tenant->hasReachedCameraLimit()) {
            return redirect()->back()
                ->with('error', "Limite de cameras atingido para \"{$tenant->name}\". Upgrade o plano para adicionar mais cameras.");
        }

        $camera = Camera::create([
            ...$validated,
            'tenant_id' => $tenantId,
            'slug' => Camera::generateSlug($validated['name'], $tenantId),
            'is_active' => $validated['is_active'] ?? true,
            'port' => $validated['port'] ?? 554,
            'transport' => $validated['transport'] ?? 'tcp',
            'stream_type' => $validated['stream_type'] ?? 'main',
            'has_ptz' => $validated['has_ptz'] ?? false,
            'has_audio' => $validated['has_audio'] ?? false,
            'ipv6_preferred' => $validated['ipv6_preferred'] ?? false,
            // Cameras gravam por padrao ao serem criadas.
            // O usuario pode desativar manualmente depois.
            'continuous_recording' => $validated['continuous_recording'] ?? true,
        ]);

        // Update quota usage
        $this->updateCameraQuota($tenantId);

        // Update device active_channels count if applicable
        if ($camera->device_id) {
            $camera->device->update([
                'active_channels' => $camera->device->cameras()->where('is_active', true)->count(),
            ]);
        }

        // Auto-start stream if camera is active — the camera should come online
        // automatically without requiring manual intervention from the user.
        if ($camera->is_active) {
            try {
                StartStreamJob::dispatch($camera->id);
                Log::info("Auto-starting stream for newly created camera {$camera->id} ({$camera->name}).");
            } catch (\Throwable $e) {
                Log::warning("Failed to auto-start stream for camera {$camera->id}: {$e->getMessage()}");
            }
        }

        return redirect()->route('cameras.index')
            ->with('success', 'Camera cadastrada com sucesso. O stream sera iniciado automaticamente.');
    }

    public function show(Request $request, Camera $camera): Response
    {
        $this->authorizeForTenant($camera);

        try {
            $streamUrls = app(StreamUrlResolver::class)->resolve($camera);
        } catch (\Throwable) {
            $streamUrls = [
                'rtsp_url' => null,
                'rtmp_url' => null,
                'hls_url' => null,
                'hls_shift_url' => null,
                'mp4_export_url' => null,
            ];
        }

        // Recent segments (last 10 from recording_segments)
        $recentSegments = RecordingSegment::forCamera($camera->id)
            ->available()
            ->orderByDesc('starts_at')
            ->limit(10)
            ->get()
            ->map(fn (RecordingSegment $s) => [
                'id' => $s->id,
                'starts_at' => $s->starts_at->toIso8601String(),
                'ends_at' => $s->ends_at?->toIso8601String(),
                'duration_seconds' => $s->duration_seconds,
                'file_size_bytes' => $s->file_size_bytes,
                'status' => $s->status,
            ]);

        $segmentStats = [
            'total_count' => RecordingSegment::forCamera($camera->id)->available()->count(),
            'total_size' => (int) RecordingSegment::forCamera($camera->id)->available()->sum('file_size_bytes'),
        ];

        return Inertia::render('Cameras/Show', [
            'camera' => $camera->load('device', 'tenant:id,name', 'activeStream'),
            'streamUrls' => $streamUrls,
            'recentSegments' => $recentSegments,
            'segmentStats' => $segmentStats,
            'canManage' => $request->user()->hasRole(['admin', 'super-admin']),
        ]);
    }

    /**
     * GET /cameras/{camera}/recordings
     *
     * Page to view all recording segments for a camera with timeline player.
     */
    public function recordings(Request $request, Camera $camera): Response
    {
        $this->authorizeForTenant($camera);

        // Date filter: default to today
        $date = $request->input('date', now()->format('Y-m-d'));

        $startOfDay = Carbon::parse($date)->startOfDay();
        $endOfDay = Carbon::parse($date)->endOfDay();

        // Fetch segments for the selected day (cada segmento = uma barra clicavel na timeline)
        $segments = RecordingSegment::forCamera($camera->id)
            ->available()
            ->inTimeRange($startOfDay, $endOfDay)
            ->orderBy('starts_at')
            ->get()
            ->map(function (RecordingSegment $s) {
                $streamUrl = route('segments.stream', $s);
                return [
                    'id' => $s->id,
                    'starts_at' => $s->starts_at->toIso8601String(),
                    'ends_at' => $s->ends_at?->toIso8601String(),
                    'duration_seconds' => $s->duration_seconds,
                    'file_size_bytes' => $s->file_size_bytes,
                    'status' => $s->status,
                    'stream_url' => $streamUrl,
                    'has_local' => (bool) $s->local_path,
                    'has_remote' => (bool) $s->remote_path,
                ];
            });

        // Available dates for the calendar picker
        $availableDates = RecordingSegment::forCamera($camera->id)
            ->available()
            ->selectRaw("DATE(starts_at) as date, COUNT(*) as segment_count, SUM(file_size_bytes) as total_size")
            ->groupByRaw('DATE(starts_at)')
            ->orderByDesc('date')
            ->limit(90)
            ->get()
            ->map(fn ($row) => [
                'date' => $row->date,
                'segment_count' => (int) $row->segment_count,
                'total_size' => (int) $row->total_size,
            ]);

        // Summary stats
        $totalSegments = RecordingSegment::forCamera($camera->id)->available()->count();
        $totalSize = RecordingSegment::forCamera($camera->id)->available()->sum('file_size_bytes');

        return Inertia::render('Cameras/Recordings', [
            'camera' => $camera->load('tenant:id,name'),
            'segments' => $segments,
            'selectedDate' => $date,
            'availableDates' => $availableDates,
            'stats' => [
                'total_segments' => $totalSegments,
                'total_size' => (int) $totalSize,
                'today_segments' => $segments->count(),
                'today_duration' => $segments->sum('duration_seconds'),
                'today_size' => $segments->sum('file_size_bytes'),
            ],
        ]);
    }

    public function edit(Camera $camera): Response
    {
        $this->authorizeForTenant($camera);

        $devices = Device::where('tenant_id', $camera->tenant_id)
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'manufacturer', 'total_channels']);

        return Inertia::render('Cameras/Edit', [
            'camera' => $camera->load('device:id,name'),
            'devices' => $devices,
            'manufacturers' => $this->manufacturerResolver->getManufacturerList(),
        ]);
    }

    public function update(Request $request, Camera $camera): RedirectResponse
    {
        $this->authorizeForTenant($camera);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'device_id' => 'nullable|uuid|exists:devices,id',
            'channel_number' => 'nullable|integer|min:1|max:128',
            'channel_type' => 'required|string|in:analog,ip,direct',
            'manufacturer' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:255',
            'address_type' => 'nullable|string|in:ipv4,ipv6,ddns,hostname',
            'address' => 'nullable|string|max:255',
            'port' => 'integer|min:1|max:65535',
            'secondary_address' => 'nullable|string|max:255',
            'transport' => 'string|in:tcp,udp,auto',
            'username' => 'nullable|string|max:255',
            'password' => 'nullable|string|max:255',
            'stream_type' => 'string|in:main,sub,mobile',
            'rtsp_path' => 'nullable|string|max:500',
            'has_ptz' => 'boolean',
            'has_audio' => 'boolean',
            'is_active' => 'boolean',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'ddns_provider' => 'nullable|string|max:100',
            'ddns_hostname' => 'nullable|string|max:255',
            'ipv6_preferred' => 'boolean',
            'recording_quality' => 'nullable|string|in:original,high,medium,low,custom',
            'recording_resolution' => 'nullable|required_if:recording_quality,custom|string|in:3840x2160,2560x1440,1920x1080,1280x720,640x480,480x360',
            'recording_codec' => 'nullable|required_if:recording_quality,custom|string|in:copy,h264,h265',
            'recording_fps' => 'nullable|required_if:recording_quality,custom|integer|min:5|max:60',
            'recording_bitrate' => 'nullable|required_if:recording_quality,custom|string|in:8M,6M,4M,2M,1M,512K',
        ]);

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        // Normalize rtsp_path
        if (! empty($validated['rtsp_path'])) {
            $path = trim($validated['rtsp_path']);
            if (! str_starts_with($path, '/')) {
                $path = '/' . $path;
            }
            $validated['rtsp_path'] = $path;
        } else {
            $validated['rtsp_path'] = null;
        }

        // Detect if connection details changed (requires stream restart)
        $connectionFields = ['address', 'port', 'username', 'password', 'rtsp_path', 'manufacturer', 'stream_type', 'channel_number', 'transport'];
        $connectionChanged = false;
        foreach ($connectionFields as $field) {
            if (array_key_exists($field, $validated) && $camera->$field != $validated[$field]) {
                $connectionChanged = true;
                break;
            }
        }

        $camera->update($validated);

        // If connection details changed and stream is active, restart it
        if ($connectionChanged) {
            $this->restartStreamIfActive($camera);
        }

        // Update quota if active status changed
        $this->updateCameraQuota($camera->tenant_id);

        return redirect()->route('cameras.show', $camera)
            ->with('success', 'Camera atualizada com sucesso.');
    }

    /**
     * Restart the camera stream if it's currently active.
     * This ensures MediaMTX picks up new connection details.
     */
    private function restartStreamIfActive(Camera $camera): void
    {
        $camera->load('activeStream');

        if (! $camera->activeStream) {
            return;
        }

        try {
            $streamManager = app(\App\Services\Streaming\StreamManager::class);

            // Stop current stream (expects a Stream model)
            $streamManager->stopStream($camera->activeStream);

            // Brief pause to let MediaMTX clean up
            usleep(500_000); // 500ms

            // Start new stream with updated connection details
            $streamManager->startStream($camera->fresh());
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Failed to restart stream after camera edit: {$e->getMessage()}", [
                'camera_id' => $camera->id,
            ]);
        }
    }

    public function destroy(Camera $camera): RedirectResponse
    {
        $this->authorizeForTenant($camera);

        $tenantId = $camera->tenant_id;
        $deviceId = $camera->device_id;
        $cameraName = $camera->name;

        // ── Full cleanup: stop streams, delete files, purge cache, etc. ──
        try {
            $cleanupService = app(CameraCleanupService::class);
            $cleanupLog = $cleanupService->cleanup($camera);

            Log::info("Camera '{$cameraName}' cleanup completed.", $cleanupLog);
        } catch (\Throwable $e) {
            Log::error("Camera '{$cameraName}' cleanup failed: {$e->getMessage()}");
            // Continue with deletion even if cleanup partially fails
        }

        // Force delete the camera record (triggers DB cascades for any remaining references)
        $camera->forceDelete();

        // Update quota
        $this->updateCameraQuota($tenantId);

        // Update device active_channels
        if ($deviceId) {
            $device = Device::find($deviceId);
            if ($device) {
                $device->update([
                    'active_channels' => $device->cameras()->where('is_active', true)->count(),
                ]);
            }
        }

        return redirect()->route('cameras.index')
            ->with('success', "Camera '{$cameraName}' removida com sucesso. Todos os dados relacionados foram limpos.");
    }

    private function authorizeForTenant(Camera $camera): void
    {
        $user = auth()->user();
        if (! $user->isSuperAdmin() && $camera->tenant_id !== $user->tenant_id) {
            abort(403, 'Acesso negado.');
        }
    }

    private function updateCameraQuota(string $tenantId): void
    {
        $count = Camera::where('tenant_id', $tenantId)->where('is_active', true)->count();
        $tenant = Tenant::find($tenantId);

        if (! $tenant) {
            return;
        }

        $quota = $tenant->getQuota('cameras');

        if ($quota) {
            $quota->update(['current_usage' => $count, 'last_calculated_at' => now()]);
        } else {
            $plan = $tenant->currentPlan();
            $tenant->quotaUsages()->create([
                'resource' => 'cameras',
                'current_usage' => $count,
                'limit_value' => $plan?->max_cameras ?? $tenant->max_cameras,
                'last_calculated_at' => now(),
            ]);
        }
    }
}
