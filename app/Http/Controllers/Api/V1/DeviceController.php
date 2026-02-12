<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreDeviceRequest;
use App\Http\Requests\Api\V1\UpdateDeviceRequest;
use App\Http\Resources\Api\V1\DeviceResource;
use App\Models\Device;
use App\Traits\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DeviceController extends Controller
{
    use ApiResponder;

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Device::query();

        if (! $user->isSuperAdmin()) {
            $query->where('tenant_id', $user->tenant_id);
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('address', 'ilike', "%{$search}%")
                  ->orWhere('manufacturer', 'ilike', "%{$search}%")
                  ->orWhere('serial_number', 'ilike', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->query('type'));
        }

        if ($request->filled('status')) {
            match ($request->query('status')) {
                'online' => $query->where('is_online', true),
                'offline' => $query->where('is_online', false),
                'active' => $query->where('is_active', true),
                default => null,
            };
        }

        $query->withCount('cameras');

        $perPage = min((int) $request->query('per_page', 15), 100);

        $devices = $query->orderBy('name')->paginate($perPage);

        return response()->json([
            'data' => DeviceResource::collection($devices),
            'meta' => [
                'api_version' => 'v1',
                'current_page' => $devices->currentPage(),
                'per_page' => $devices->perPage(),
                'total' => $devices->total(),
                'last_page' => $devices->lastPage(),
            ],
            'links' => [
                'first' => $devices->url(1),
                'last' => $devices->url($devices->lastPage()),
                'prev' => $devices->previousPageUrl(),
                'next' => $devices->nextPageUrl(),
            ],
        ]);
    }

    public function store(StoreDeviceRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $data['tenant_id'] = $user->isSuperAdmin() && isset($data['tenant_id'])
            ? $data['tenant_id']
            : $user->tenant_id;
        $data['slug'] = Str::slug($data['name']) . '-' . Str::random(4);

        $device = Device::create($data);

        return $this->resource(new DeviceResource($device), status: 201);
    }

    public function show(Request $request, Device $device): JsonResponse
    {
        $user = $request->user();

        if (! $user->isSuperAdmin() && $device->tenant_id !== $user->tenant_id) {
            return $this->forbidden();
        }

        if ($request->has('include') && str_contains($request->query('include'), 'cameras')) {
            $device->load('cameras');
        }

        return $this->resource(new DeviceResource($device));
    }

    public function update(UpdateDeviceRequest $request, Device $device): JsonResponse
    {
        $user = $request->user();

        if (! $user->isSuperAdmin() && $device->tenant_id !== $user->tenant_id) {
            return $this->forbidden();
        }

        $device->update($request->validated());

        return $this->resource(new DeviceResource($device->fresh()));
    }

    public function destroy(Request $request, Device $device): JsonResponse
    {
        $user = $request->user();

        if (! $user->isSuperAdmin() && $device->tenant_id !== $user->tenant_id) {
            return $this->forbidden();
        }

        $device->delete();

        return $this->success(['message' => 'Device deleted successfully.']);
    }
}
