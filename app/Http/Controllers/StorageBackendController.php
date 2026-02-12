<?php

namespace App\Http\Controllers;

use App\Models\StorageBackend;
use App\Models\Tenant;
use App\Services\Storage\StorageBackendManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StorageBackendController extends Controller
{
    public function __construct(
        private StorageBackendManager $backendManager,
    ) {}

    /**
     * GET /settings/storage — List all storage backends.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $tenantId = $user->tenant_id;
        $isSuperAdmin = $user->isSuperAdmin();

        $query = StorageBackend::query()->with('tenant:id,name');

        if (! $isSuperAdmin) {
            $query->forTenant($tenantId);
        }

        $backends = $query
            ->orderByDesc('is_default')
            ->orderBy('priority')
            ->orderBy('name')
            ->get()
            ->map(fn (StorageBackend $b) => [
                'id' => $b->id,
                'tenant_id' => $b->tenant_id,
                'tenant_name' => $b->tenant?->name ?? 'Global (Sistema)',
                'name' => $b->name,
                'driver' => $b->driver,
                'driver_label' => $b->getDriverLabel(),
                'driver_category' => $b->getDriverCategory(),
                'is_active' => $b->is_active,
                'is_default' => $b->is_default,
                'priority' => $b->priority,
                'health_status' => $b->health_status,
                'last_health_check_at' => $b->last_health_check_at?->toIso8601String(),
                'health_check_latency_ms' => $b->health_check_latency_ms,
                'health_check_message' => $b->health_check_message,
                'total_capacity_gb' => $b->total_capacity_gb,
                'used_capacity_gb' => $b->used_capacity_gb,
                'free_capacity_gb' => $b->freeCapacityGb(),
                'usage_percent' => $b->usagePercent(),
                'total_files' => $b->total_files,
                'safe_config' => $b->getSafeConfig(),
                'created_at' => $b->created_at?->toIso8601String(),
            ]);

        // Super admin can assign backends to tenants
        $tenants = $isSuperAdmin
            ? Tenant::orderBy('name')->get(['id', 'name'])
            : [];

        return Inertia::render('Settings/Storage/Index', [
            'backends' => $backends,
            'drivers' => $this->backendManager->getAvailableDrivers(),
            'driverLabels' => StorageBackend::getDriverLabels(),
            'tenants' => $tenants,
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }

    /**
     * POST /settings/storage — Create new storage backend.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $isSuperAdmin = $user->isSuperAdmin();

        $rules = [
            'name' => 'required|string|max:255',
            'driver' => 'required|string|in:' . implode(',', StorageBackend::DRIVERS),
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'priority' => 'integer|min:0|max:999',
            'config' => 'required|array',
        ];

        // Super admin can optionally assign to a tenant
        if ($isSuperAdmin) {
            $rules['tenant_id'] = 'nullable|uuid|exists:tenants,id';
        }

        $validated = $request->validate($rules);

        // Resolve tenant_id: super admin can pick or leave null (global)
        $tenantId = $isSuperAdmin
            ? ($validated['tenant_id'] ?? null)
            : $user->tenant_id;

        $backend = StorageBackend::create([
            'tenant_id' => $tenantId,
            'name' => $validated['name'],
            'driver' => $validated['driver'],
            'is_active' => $validated['is_active'] ?? true,
            'is_default' => false, // Will be set via markAsDefault if needed
            'priority' => $validated['priority'] ?? 0,
            'config' => $validated['config'],
            'health_status' => StorageBackend::HEALTH_UNKNOWN,
        ]);

        // If user wants it as default, or if it's the first backend for this scope → mark as default
        $isFirstForScope = StorageBackend::query()
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId), fn ($q) => $q->whereNull('tenant_id'))
            ->where('id', '!=', $backend->id)
            ->count() === 0;

        if (($validated['is_default'] ?? false) || $isFirstForScope) {
            $backend->markAsDefault();
        }

        return redirect()->route('settings.storage.index')
            ->with('success', "Backend \"{$backend->name}\" criado com sucesso.");
    }

    /**
     * PUT /settings/storage/{backend} — Update a storage backend.
     */
    public function update(Request $request, StorageBackend $backend): RedirectResponse
    {
        $this->authorizeBackend($request, $backend);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'driver' => 'required|string|in:' . implode(',', StorageBackend::DRIVERS),
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'priority' => 'integer|min:0|max:999',
            'config' => 'required|array',
        ]);

        // Merge config: keep existing sensitive values if placeholders sent
        $existingConfig = $backend->config ?? [];
        $newConfig = $validated['config'];
        $sensitiveKeys = ['password', 'secret_key', 'secret_access_key', 'account_key', 'private_key', 'key_passphrase', 'api_key', 'session_token', 'connection_string'];

        foreach ($sensitiveKeys as $key) {
            // If frontend sent masked value or empty, keep existing
            if (isset($newConfig[$key]) && ($newConfig[$key] === '••••••••' || $newConfig[$key] === '')) {
                if (isset($existingConfig[$key])) {
                    $newConfig[$key] = $existingConfig[$key];
                } else {
                    unset($newConfig[$key]);
                }
            }
        }

        $wantsDefault = (bool) ($validated['is_default'] ?? false);

        $backend->update([
            'name' => $validated['name'],
            'driver' => $validated['driver'],
            'is_active' => $validated['is_active'] ?? true,
            'priority' => $validated['priority'] ?? 0,
            'config' => $newConfig,
        ]);

        // Use markAsDefault() to guarantee only one default per tenant scope
        if ($wantsDefault) {
            $backend->markAsDefault();
        } elseif ($backend->is_default) {
            // User explicitly unchecked default — remove flag
            $backend->update(['is_default' => false]);
        }

        return redirect()->route('settings.storage.index')
            ->with('success', "Backend \"{$backend->name}\" atualizado com sucesso.");
    }

    /**
     * DELETE /settings/storage/{backend} — Remove a storage backend.
     */
    public function destroy(Request $request, StorageBackend $backend): RedirectResponse
    {
        $this->authorizeBackend($request, $backend);

        $name = $backend->name;

        // Check if it has recordings
        $recordingCount = $backend->recordings()->count();
        if ($recordingCount > 0) {
            return redirect()->back()
                ->with('error', "Nao e possivel excluir \"{$name}\": existem {$recordingCount} gravacoes associadas.");
        }

        $backend->delete();

        return redirect()->route('settings.storage.index')
            ->with('success', "Backend \"{$name}\" removido com sucesso.");
    }

    /**
     * POST /settings/storage/{backend}/test — Test connection.
     */
    public function test(Request $request, StorageBackend $backend): JsonResponse
    {
        $this->authorizeBackend($request, $backend);

        $result = $this->backendManager->testConnection($backend);

        return response()->json($result);
    }

    /**
     * POST /settings/storage/{backend}/set-default — Mark as default.
     */
    public function setDefault(Request $request, StorageBackend $backend): RedirectResponse
    {
        $this->authorizeBackend($request, $backend);

        $backend->markAsDefault();

        return redirect()->route('settings.storage.index')
            ->with('success', "\"{$backend->name}\" definido como backend padrao.");
    }

    /**
     * Authorize access based on tenant.
     */
    private function authorizeBackend(Request $request, StorageBackend $backend): void
    {
        $user = $request->user();
        if (! $user->isSuperAdmin() && $backend->tenant_id !== $user->tenant_id) {
            abort(403, 'Acesso negado.');
        }
    }
}
