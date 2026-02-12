<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\StorageBackendResource;
use App\Models\Camera;
use App\Models\Recording;
use App\Models\RecordingSegment;
use App\Models\StorageBackend;
use App\Models\Tenant;
use App\Traits\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StorageController extends Controller
{
    use ApiResponder;

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenantId = $user->tenant_id;

        // Super-admin: show global stats or specific tenant
        if ($user->isSuperAdmin()) {
            $tenantId = $request->query('tenant_id', $tenantId);
        }

        $tenant = $tenantId ? Tenant::find($tenantId) : null;

        // Storage usage
        $usedBytes = Recording::query()
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->whereIn('status', ['recording', 'completed'])
            ->sum('file_size_bytes');

        $segmentBytes = RecordingSegment::query()
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->whereIn('status', ['ready', 'uploaded', 'writing'])
            ->sum('file_size_bytes');

        $totalBytes = $usedBytes + $segmentBytes;
        $usedGb = round($totalBytes / (1024 * 1024 * 1024), 2);

        $limitGb = $tenant ? ($tenant->activeSubscription?->plan?->max_storage_gb ?? 100) : 0;
        $percentage = $limitGb > 0 ? round(($usedGb / $limitGb) * 100, 1) : 0;

        $recordingsCount = Recording::query()
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->whereIn('status', ['recording', 'completed'])
            ->count();

        $segmentsCount = RecordingSegment::query()
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->whereIn('status', ['ready', 'uploaded'])
            ->count();

        // Per-camera storage
        $cameras = Camera::query()
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->withCount(['recordings' => fn ($q) => $q->whereIn('status', ['recording', 'completed'])])
            ->get()
            ->map(fn ($cam) => [
                'id' => $cam->id,
                'name' => $cam->name,
                'is_recording' => $cam->is_recording,
                'recording_count' => $cam->recordings_count,
            ]);

        // Storage backends
        $backends = StorageBackend::query()
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId)->orWhereNull('tenant_id'))
            ->orderBy('priority')
            ->get();

        return $this->success([
            'used_gb' => $usedGb,
            'limit_gb' => $limitGb,
            'percentage' => $percentage,
            'recordings_count' => $recordingsCount,
            'segments_count' => $segmentsCount,
            'retention_days' => $tenant?->storage_retention_days ?? config('streaming.recording.default_retention_days', 30),
            'cameras' => $cameras,
            'backends' => StorageBackendResource::collection($backends),
        ]);
    }

    public function updateRetention(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'retention_days' => 'required|integer|min:1|max:365',
        ]);

        $tenant = $user->isSuperAdmin() && $request->filled('tenant_id')
            ? Tenant::findOrFail($request->tenant_id)
            : Tenant::find($user->tenant_id);

        if (! $tenant) {
            return $this->notFound('Tenant not found.');
        }

        $tenant->update(['storage_retention_days' => $validated['retention_days']]);

        return $this->success([
            'message' => 'Retention policy updated.',
            'retention_days' => $validated['retention_days'],
        ]);
    }
}
