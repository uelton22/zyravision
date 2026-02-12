<?php

namespace App\Http\Controllers;

use App\Models\UserDevice;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserDeviceController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $query = UserDevice::query()->with('user:id,name,email');

        // Super-admin without tenant sees all devices; otherwise filter by tenant
        if ($user->tenant_id) {
            $query->forTenant($user->tenant_id);
        }

        $devices = $query
            ->orderByDesc('last_active_at')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (UserDevice $device) => [
                'id' => $device->id,
                'device_id' => $device->device_id,
                'device_name' => $device->device_name,
                'platform' => $device->platform,
                'app_version' => $device->app_version,
                'os_version' => $device->os_version,
                'is_active' => $device->is_active,
                'has_push_token' => (bool) $device->push_token,
                'push_provider' => $device->push_provider,
                'last_active_at' => $device->last_active_at?->toISOString(),
                'revoked_at' => $device->revoked_at?->toISOString(),
                'created_at' => $device->created_at->toISOString(),
                'user' => $device->user ? [
                    'name' => $device->user->name,
                    'email' => $device->user->email,
                ] : null,
            ]);

        $stats = [
            'total' => $devices->count(),
            'active' => $devices->where('is_active', true)->count(),
            'revoked' => $devices->whereNotNull('revoked_at')->count(),
            'ios' => $devices->where('platform', 'ios')->where('is_active', true)->count(),
            'android' => $devices->where('platform', 'android')->where('is_active', true)->count(),
            'web' => $devices->where('platform', 'web')->where('is_active', true)->count(),
        ];

        return Inertia::render('Settings/Devices/Index', [
            'devices' => $devices->values(),
            'stats' => $stats,
        ]);
    }

    public function revoke(Request $request, string $deviceId)
    {
        $user = $request->user();

        $query = UserDevice::where('id', $deviceId);
        if ($user->tenant_id) {
            $query->forTenant($user->tenant_id);
        }
        $device = $query->firstOrFail();

        $device->revoke();

        return back()->with('success', 'Dispositivo desconectado com sucesso.');
    }

    public function destroy(Request $request, string $deviceId)
    {
        $user = $request->user();

        $query = UserDevice::where('id', $deviceId);
        if ($user->tenant_id) {
            $query->forTenant($user->tenant_id);
        }
        $device = $query->firstOrFail();

        $device->delete();

        return back()->with('success', 'Dispositivo removido com sucesso.');
    }
}
