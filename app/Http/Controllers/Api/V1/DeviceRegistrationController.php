<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserDeviceResource;
use App\Models\UserDevice;
use App\Traits\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceRegistrationController extends Controller
{
    use ApiResponder;

    public function index(Request $request): JsonResponse
    {
        $devices = UserDevice::forUser($request->user()->id)
            ->active()
            ->orderByDesc('last_active_at')
            ->get();

        return $this->success(UserDeviceResource::collection($devices));
    }

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_id' => 'required|string|max:255',
            'device_name' => 'required|string|max:255',
            'platform' => 'required|string|in:ios,android,web',
            'app_version' => 'nullable|string|max:50',
            'os_version' => 'nullable|string|max:50',
            'push_token' => 'nullable|string|max:500',
            'push_provider' => 'nullable|string|in:fcm,apns',
        ]);

        $user = $request->user();

        $device = UserDevice::updateOrCreate(
            [
                'user_id' => $user->id,
                'device_id' => $validated['device_id'],
            ],
            [
                'tenant_id' => $user->tenant_id,
                'device_name' => $validated['device_name'],
                'platform' => $validated['platform'],
                'app_version' => $validated['app_version'] ?? null,
                'os_version' => $validated['os_version'] ?? null,
                'push_token' => $validated['push_token'] ?? null,
                'push_provider' => $validated['push_provider'] ?? null,
                'is_active' => true,
                'revoked_at' => null,
                'last_active_at' => now(),
            ]
        );

        return $this->resource(new UserDeviceResource($device), status: 201);
    }

    public function updatePushToken(Request $request, string $deviceId): JsonResponse
    {
        $validated = $request->validate([
            'push_token' => 'required|string|max:500',
            'push_provider' => 'required|string|in:fcm,apns',
        ]);

        $device = UserDevice::where('id', $deviceId)
            ->forUser($request->user()->id)
            ->active()
            ->first();

        if (! $device) {
            return $this->notFound('Device not found.');
        }

        $device->update([
            'push_token' => $validated['push_token'],
            'push_provider' => $validated['push_provider'],
            'last_active_at' => now(),
        ]);

        return $this->resource(new UserDeviceResource($device));
    }

    public function revoke(Request $request, string $deviceId): JsonResponse
    {
        $device = UserDevice::where('id', $deviceId)
            ->forUser($request->user()->id)
            ->first();

        if (! $device) {
            return $this->notFound('Device not found.');
        }

        $device->revoke();

        return $this->success(['message' => 'Device revoked successfully.']);
    }
}
