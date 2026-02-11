<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Tenant;
use App\Services\Manufacturers\ManufacturerResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DeviceController extends Controller
{
    public function __construct(
        private ManufacturerResolver $manufacturerResolver
    ) {}

    public function index(Request $request): Response
    {
        $currentUser = $request->user();
        $isSuperAdmin = $currentUser->isSuperAdmin();

        $query = Device::query()->withCount('cameras');

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
                  ->orWhere('model', 'ilike', "%{$search}%")
                  ->orWhere('location', 'ilike', "%{$search}%");
            });
        }

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        if ($manufacturer = $request->input('manufacturer')) {
            $query->where('manufacturer', $manufacturer);
        }

        if ($request->has('is_active') && $request->input('is_active') !== '') {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $devices = $query->orderBy('name')
            ->paginate($request->input('per_page', 15))
            ->withQueryString();

        $tenants = $isSuperAdmin
            ? Tenant::orderBy('name')->get(['id', 'name'])
            : [];

        return Inertia::render('Devices/Index', [
            'devices' => $devices,
            'filters' => $request->only(['search', 'type', 'manufacturer', 'is_active', 'tenant_id']),
            'tenants' => $tenants,
            'manufacturers' => $this->manufacturerResolver->getManufacturerList(),
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }

    public function create(Request $request): Response
    {
        $currentUser = $request->user();
        $isSuperAdmin = $currentUser->isSuperAdmin();

        $tenants = $isSuperAdmin
            ? Tenant::active()->orderBy('name')->get(['id', 'name'])
            : [];

        return Inertia::render('Devices/Create', [
            'manufacturers' => $this->manufacturerResolver->getManufacturerList(),
            'tenants' => $tenants,
            'isSuperAdmin' => $isSuperAdmin,
            'defaultTenantId' => $request->input('tenant_id') ?? ($isSuperAdmin ? null : $currentUser->tenant_id),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $currentUser = $request->user();
        $isSuperAdmin = $currentUser->isSuperAdmin();

        $rules = [
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:dvr,nvr,encoder,camera,hybrid',
            'manufacturer' => 'required|string|max:100',
            'model' => 'nullable|string|max:255',
            'firmware_version' => 'nullable|string|max:100',
            'address_type' => 'required|string|in:ipv4,ipv6,ddns,hostname',
            'address' => 'required|string|max:255',
            'port' => 'required|integer|min:1|max:65535',
            'http_port' => 'integer|min:1|max:65535',
            'rtmp_port' => 'integer|min:1|max:65535',
            'secondary_address' => 'nullable|string|max:255',
            'transport' => 'required|string|in:tcp,udp,auto',
            'username' => 'nullable|string|max:255',
            'password' => 'nullable|string|max:255',
            'total_channels' => 'required|integer|min:1|max:128',
            'onvif_supported' => 'boolean',
            'onvif_profile' => 'nullable|string|max:50',
            'is_active' => 'boolean',
            'location' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'ddns_provider' => 'nullable|string|max:100',
            'ddns_hostname' => 'nullable|string|max:255',
            'ipv6_preferred' => 'boolean',
        ];

        if ($isSuperAdmin) {
            $rules['tenant_id'] = 'required|uuid|exists:tenants,id';
        }

        $validated = $request->validate($rules);

        $tenantId = $isSuperAdmin
            ? $validated['tenant_id']
            : $currentUser->tenant_id;

        Device::create([
            ...$validated,
            'tenant_id' => $tenantId,
            'slug' => Device::generateSlug($validated['name'], $tenantId),
            'is_active' => $validated['is_active'] ?? true,
            'http_port' => $validated['http_port'] ?? 80,
            'rtmp_port' => $validated['rtmp_port'] ?? 1935,
            'onvif_supported' => $validated['onvif_supported'] ?? false,
            'ipv6_preferred' => $validated['ipv6_preferred'] ?? false,
        ]);

        return redirect()->route('devices.index')
            ->with('success', 'Dispositivo cadastrado com sucesso.');
    }

    public function show(Device $device): Response
    {
        $this->authorizeForTenant($device);

        $device->loadCount('cameras');

        $cameras = $device->cameras()
            ->orderBy('channel_number')
            ->orderBy('name')
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'channel_number' => $c->channel_number,
                'channel_type' => $c->channel_type,
                'is_active' => $c->is_active,
                'is_online' => $c->is_online,
                'location' => $c->location,
            ]);

        return Inertia::render('Devices/Show', [
            'device' => $device->load('tenant:id,name'),
            'cameras' => $cameras,
        ]);
    }

    public function edit(Device $device): Response
    {
        $this->authorizeForTenant($device);

        return Inertia::render('Devices/Edit', [
            'device' => $device,
            'manufacturers' => $this->manufacturerResolver->getManufacturerList(),
        ]);
    }

    public function update(Request $request, Device $device): RedirectResponse
    {
        $this->authorizeForTenant($device);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:dvr,nvr,encoder,camera,hybrid',
            'manufacturer' => 'required|string|max:100',
            'model' => 'nullable|string|max:255',
            'firmware_version' => 'nullable|string|max:100',
            'address_type' => 'required|string|in:ipv4,ipv6,ddns,hostname',
            'address' => 'required|string|max:255',
            'port' => 'required|integer|min:1|max:65535',
            'http_port' => 'integer|min:1|max:65535',
            'rtmp_port' => 'integer|min:1|max:65535',
            'secondary_address' => 'nullable|string|max:255',
            'transport' => 'required|string|in:tcp,udp,auto',
            'username' => 'nullable|string|max:255',
            'password' => 'nullable|string|max:255',
            'total_channels' => 'required|integer|min:1|max:128',
            'onvif_supported' => 'boolean',
            'onvif_profile' => 'nullable|string|max:50',
            'is_active' => 'boolean',
            'location' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'ddns_provider' => 'nullable|string|max:100',
            'ddns_hostname' => 'nullable|string|max:255',
            'ipv6_preferred' => 'boolean',
        ]);

        // Don't update password if not provided (keep existing)
        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $device->update($validated);

        return redirect()->route('devices.index')
            ->with('success', 'Dispositivo atualizado com sucesso.');
    }

    public function destroy(Device $device): RedirectResponse
    {
        $this->authorizeForTenant($device);

        $cameraCount = $device->cameras()->count();
        if ($cameraCount > 0) {
            return redirect()->back()
                ->with('error', "Nao e possivel excluir. Existem {$cameraCount} camera(s) vinculada(s) a este dispositivo.");
        }

        $device->delete();

        return redirect()->route('devices.index')
            ->with('success', 'Dispositivo removido com sucesso.');
    }

    private function authorizeForTenant(Device $device): void
    {
        $user = auth()->user();
        if (! $user->isSuperAdmin() && $device->tenant_id !== $user->tenant_id) {
            abort(403, 'Acesso negado.');
        }
    }
}
