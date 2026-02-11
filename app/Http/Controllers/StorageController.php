<?php

namespace App\Http\Controllers;

use App\Models\Camera;
use App\Models\RecordingSegment;
use App\Models\RecordingSetting;
use App\Models\StorageBackend;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class StorageController extends Controller
{
    /**
     * Storage dashboard page.
     * Shows real storage usage from recording_segments + active backends.
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

        // ─── Real storage usage from recording_segments ───
        $segmentStats = RecordingSegment::where('tenant_id', $tenant->id)
            ->whereIn('status', [
                RecordingSegment::STATUS_READY,
                RecordingSegment::STATUS_UPLOADED,
                RecordingSegment::STATUS_UPLOADING,
            ])
            ->selectRaw('
                COUNT(*) as total_segments,
                COALESCE(SUM(file_size_bytes), 0) as total_bytes,
                COALESCE(SUM(duration_seconds), 0) as total_duration,
                MIN(starts_at) as oldest_segment,
                MAX(starts_at) as newest_segment
            ')
            ->first();

        $totalBytes = (int) ($segmentStats->total_bytes ?? 0);
        $totalUsedGb = round($totalBytes / (1024 ** 3), 2);

        // Plan limits
        $plan = $tenant->currentPlan();
        $storageLimitGb = $plan?->max_storage_gb ?? 50;

        // ─── Storage per backend (driver) ───
        $backendUsage = RecordingSegment::where('recording_segments.tenant_id', $tenant->id)
            ->whereIn('recording_segments.status', [
                RecordingSegment::STATUS_READY,
                RecordingSegment::STATUS_UPLOADED,
                RecordingSegment::STATUS_UPLOADING,
            ])
            ->leftJoin('storage_backends', 'recording_segments.storage_backend_id', '=', 'storage_backends.id')
            ->selectRaw('
                recording_segments.storage_backend_id,
                storage_backends.name as backend_name,
                storage_backends.driver as backend_driver,
                storage_backends.is_active as backend_active,
                COUNT(*) as segment_count,
                COALESCE(SUM(recording_segments.file_size_bytes), 0) as total_bytes
            ')
            ->groupBy('recording_segments.storage_backend_id', 'storage_backends.name', 'storage_backends.driver', 'storage_backends.is_active')
            ->get()
            ->map(fn ($row) => [
                'backend_id' => $row->storage_backend_id,
                'backend_name' => $row->backend_name ?? 'Local (sem backend)',
                'backend_driver' => $row->backend_driver ?? 'local',
                'backend_active' => (bool) $row->backend_active,
                'segment_count' => (int) $row->segment_count,
                'total_bytes' => (int) $row->total_bytes,
                'total_gb' => round((int) $row->total_bytes / (1024 ** 3), 2),
            ]);

        // ─── Active storage backends (filtrado por tenant) ───
        $backendsQuery = StorageBackend::where('is_active', true);

        if (! $isSuperAdmin) {
            // Mostra apenas backends do tenant + backends globais (tenant_id = null)
            $backendsQuery->where(function ($q) use ($tenant) {
                $q->where('tenant_id', $tenant->id)
                  ->orWhereNull('tenant_id');
            });
        }

        $backends = $backendsQuery
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get(['id', 'name', 'driver', 'is_active', 'is_default', 'priority', 'tenant_id'])
            ->map(fn (StorageBackend $b) => [
                'id' => $b->id,
                'name' => $b->name,
                'driver' => $b->driver,
                'is_default' => $b->is_default,
                'priority' => $b->priority,
            ]);

        // ─── Per-camera storage from segments ───
        $cameraStorage = Camera::where('cameras.tenant_id', $tenant->id)
            ->select('cameras.id', 'cameras.name', 'cameras.is_recording', 'cameras.continuous_recording')
            ->leftJoin('recording_segments', function ($join) {
                $join->on('cameras.id', '=', 'recording_segments.camera_id')
                    ->whereIn('recording_segments.status', [
                        RecordingSegment::STATUS_READY,
                        RecordingSegment::STATUS_UPLOADED,
                        RecordingSegment::STATUS_UPLOADING,
                    ]);
            })
            ->selectRaw('
                COALESCE(SUM(recording_segments.file_size_bytes), 0) as storage_bytes,
                COUNT(recording_segments.id) as segment_count,
                COALESCE(SUM(recording_segments.duration_seconds), 0) as total_duration
            ')
            ->groupBy('cameras.id', 'cameras.name', 'cameras.is_recording', 'cameras.continuous_recording')
            ->orderByDesc('storage_bytes')
            ->get()
            ->map(fn ($cam) => [
                'id' => $cam->id,
                'name' => $cam->name,
                'is_recording' => (bool) $cam->is_recording,
                'continuous_recording' => (bool) $cam->continuous_recording,
                'storage_bytes' => (int) $cam->storage_bytes,
                'storage_gb' => round((int) $cam->storage_bytes / (1024 ** 3), 2),
                'segment_count' => (int) $cam->segment_count,
                'total_duration' => (int) $cam->total_duration,
            ]);

        // ─── Retention settings ───
        $recordingSettings = RecordingSetting::forTenant($tenant->id);
        $retentionDays = $recordingSettings->retention_days;
        $planMaxRetention = $plan?->retention_days ?? 365;

        return Inertia::render('Storage/Index', [
            'storage' => [
                'total_bytes' => $totalBytes,
                'total_gb' => $totalUsedGb,
                'limit_gb' => $storageLimitGb,
                'percentage' => $storageLimitGb > 0
                    ? round(($totalUsedGb / $storageLimitGb) * 100, 1)
                    : 0,
                'total_segments' => (int) ($segmentStats->total_segments ?? 0),
                'total_duration' => (int) ($segmentStats->total_duration ?? 0),
                'oldest_segment' => $segmentStats->oldest_segment,
                'newest_segment' => $segmentStats->newest_segment,
            ],
            'backendUsage' => $backendUsage,
            'backends' => $backends,
            'cameraStorage' => $cameraStorage,
            'retentionDays' => $retentionDays,
            'planLimits' => [
                'max_storage_gb' => $storageLimitGb,
                'max_retention_days' => $planMaxRetention,
            ],
            'tenants' => $tenants,
            'isSuperAdmin' => $isSuperAdmin,
            'currentTenantId' => $tenant->id,
            'currentTenantName' => $tenant->name,
        ]);
    }

    /**
     * Update retention settings for a tenant.
     */
    public function updateRetention(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'retention_days' => 'required|integer|min:1|max:365',
            'tenant_id' => 'nullable|uuid|exists:tenants,id',
        ]);

        $user = $request->user();

        if ($user->isSuperAdmin() && ! empty($validated['tenant_id'])) {
            $tenant = Tenant::find($validated['tenant_id']);
        } else {
            $tenant = $user->tenant;
        }

        if (! $tenant) {
            abort(403);
        }

        $plan = $tenant->currentPlan();
        $maxRetention = $plan?->retention_days ?? 365;

        if ($validated['retention_days'] > $maxRetention) {
            return redirect()->back()->with('error', "Retencao maxima do plano: {$maxRetention} dias.");
        }

        // Update both the tenant field and the recording_settings
        $tenant->update(['storage_retention_days' => $validated['retention_days']]);

        RecordingSetting::updateOrCreate(
            ['tenant_id' => $tenant->id],
            ['retention_days' => $validated['retention_days']],
        );

        return redirect()->back()->with('success', 'Politica de retencao atualizada com sucesso.');
    }
}
