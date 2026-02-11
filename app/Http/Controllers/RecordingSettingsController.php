<?php

namespace App\Http\Controllers;

use App\Models\RecordingSetting;
use App\Models\StorageBackend;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RecordingSettingsController extends Controller
{
    /**
     * GET /settings/recording
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $tenantId = $user->isSuperAdmin() ? null : $user->tenant_id;

        $settings = RecordingSetting::forTenant($tenantId);

        // Available storage backends
        $backends = StorageBackend::query()
            ->when(
                ! $user->isSuperAdmin(),
                fn ($q) => $q->forTenant($user->tenant_id),
                fn ($q) => $q // Super admin sees all
            )
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'driver', 'is_default']);

        return Inertia::render('Settings/Recording/Index', [
            'settings' => [
                'id' => $settings->id ?? null,
                'segment_duration_seconds' => $settings->segment_duration_seconds,
                'continuous_recording_default' => $settings->continuous_recording_default,
                'retention_days' => $settings->retention_days,
                'cleanup_local_after_upload' => $settings->cleanup_local_after_upload,
                'recording_quality' => $settings->recording_quality,
                'default_storage_backend_id' => $settings->default_storage_backend_id,
            ],
            'backends' => $backends,
            'qualityOptions' => [
                ['value' => 'original', 'label' => 'Original (sem transcodificacao)'],
                ['value' => 'high', 'label' => 'Alta (1080p)'],
                ['value' => 'medium', 'label' => 'Media (720p)'],
                ['value' => 'low', 'label' => 'Baixa (480p)'],
            ],
            'segmentOptions' => [
                ['value' => 30, 'label' => '30 segundos'],
                ['value' => 60, 'label' => '1 minuto (recomendado)'],
                ['value' => 120, 'label' => '2 minutos'],
                ['value' => 300, 'label' => '5 minutos'],
                ['value' => 600, 'label' => '10 minutos'],
            ],
        ]);
    }

    /**
     * PUT /settings/recording
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $tenantId = $user->isSuperAdmin() ? null : $user->tenant_id;

        $validated = $request->validate([
            'segment_duration_seconds' => 'required|integer|in:30,60,120,300,600',
            'continuous_recording_default' => 'required|boolean',
            'retention_days' => 'required|integer|min:1|max:365',
            'cleanup_local_after_upload' => 'required|boolean',
            'recording_quality' => 'required|string|in:original,high,medium,low',
            'default_storage_backend_id' => 'nullable|uuid|exists:storage_backends,id',
        ]);

        // Ensure null for empty storage backend
        if (empty($validated['default_storage_backend_id'])) {
            $validated['default_storage_backend_id'] = null;
        }

        RecordingSetting::updateOrCreate(
            ['tenant_id' => $tenantId],
            $validated
        );

        return redirect()->route('settings.recording.index')
            ->with('success', 'Configuracoes de gravacao atualizadas com sucesso.');
    }
}
