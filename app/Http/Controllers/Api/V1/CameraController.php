<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreCameraRequest;
use App\Http\Requests\Api\V1\UpdateCameraRequest;
use App\Http\Resources\Api\V1\CameraCollection;
use App\Http\Resources\Api\V1\CameraResource;
use App\Models\Camera;
use App\Traits\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CameraController extends Controller
{
    use ApiResponder;

    public function index(Request $request): CameraCollection
    {
        $user = $request->user();
        $query = Camera::query();

        if (! $user->isSuperAdmin()) {
            $query->where('tenant_id', $user->tenant_id);
        }

        // Filters
        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('location', 'ilike', "%{$search}%")
                  ->orWhere('model', 'ilike', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            match ($request->query('status')) {
                'online' => $query->where('is_online', true),
                'offline' => $query->where('is_online', false),
                'recording' => $query->where('is_recording', true),
                'active' => $query->where('is_active', true),
                'inactive' => $query->where('is_active', false),
                'error' => $query->whereHas('latestStream', fn ($q) => $q->where('status', 'error')),
                default => null,
            };
        }

        if ($request->filled('device_id')) {
            $query->where('device_id', $request->query('device_id'));
        }

        // Includes
        $includes = $this->parseIncludes($request, ['device', 'activeStream', 'tenant']);
        $query->with($includes);

        $perPage = min((int) $request->query('per_page', 15), 100);

        return new CameraCollection($query->orderBy('name')->paginate($perPage));
    }

    public function store(StoreCameraRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $data['tenant_id'] = $user->isSuperAdmin() && isset($data['tenant_id'])
            ? $data['tenant_id']
            : $user->tenant_id;
        $data['slug'] = Str::slug($data['name']) . '-' . Str::random(4);

        $camera = Camera::create($data);

        return $this->resource(new CameraResource($camera->load('device')), status: 201);
    }

    public function show(Request $request, Camera $camera): JsonResponse
    {
        $user = $request->user();

        if (! $user->isSuperAdmin() && $camera->tenant_id !== $user->tenant_id) {
            return $this->forbidden();
        }

        $includes = $this->parseIncludes($request, ['device', 'activeStream', 'tenant', 'recordings']);
        $camera->load($includes);

        return $this->resource(new CameraResource($camera));
    }

    public function update(UpdateCameraRequest $request, Camera $camera): JsonResponse
    {
        $user = $request->user();

        if (! $user->isSuperAdmin() && $camera->tenant_id !== $user->tenant_id) {
            return $this->forbidden();
        }

        $camera->update($request->validated());

        return $this->resource(new CameraResource($camera->fresh('device')));
    }

    public function destroy(Request $request, Camera $camera): JsonResponse
    {
        $user = $request->user();

        if (! $user->isSuperAdmin() && $camera->tenant_id !== $user->tenant_id) {
            return $this->forbidden();
        }

        $camera->delete();

        return $this->success(['message' => 'Camera deleted successfully.']);
    }

    public function thumbnail(Request $request, Camera $camera): JsonResponse
    {
        $user = $request->user();

        if (! $user->isSuperAdmin() && $camera->tenant_id !== $user->tenant_id) {
            return $this->forbidden();
        }

        return $this->success([
            'snapshot_url' => $camera->snapshot_url,
            'updated_at' => $camera->updated_at->toISOString(),
        ]);
    }

    protected function parseIncludes(Request $request, array $allowed): array
    {
        if (! $request->has('include')) {
            return [];
        }

        $requested = explode(',', $request->query('include'));

        return array_values(array_intersect($requested, $allowed));
    }
}
