<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TenantController extends Controller
{
    /**
     * Display a listing of tenants (super-admin only).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Tenant::query()->withCount('users');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('slug', 'ilike', "%{$search}%");
            });
        }

        if ($plan = $request->input('plan')) {
            $query->where('plan', $plan);
        }

        $tenants = $query->orderBy('name')
            ->paginate($request->input('per_page', 15));

        return response()->json($tenants);
    }

    /**
     * Store a newly created tenant.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'domain' => 'nullable|string|max:255|unique:tenants',
            'max_cameras' => 'required|integer|min:1',
            'max_users' => 'required|integer|min:1',
            'storage_retention_days' => 'required|integer|min:1',
            'plan' => 'required|string|in:starter,professional,enterprise',
        ]);

        $tenant = Tenant::create([
            ...$validated,
            'slug' => Str::slug($validated['name']) . '-' . Str::random(4),
            'is_active' => true,
        ]);

        return response()->json($tenant, 201);
    }

    /**
     * Display the specified tenant.
     */
    public function show(Tenant $tenant): JsonResponse
    {
        return response()->json($tenant->loadCount('users'));
    }

    /**
     * Update the specified tenant.
     */
    public function update(Request $request, Tenant $tenant): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'domain' => "sometimes|nullable|string|max:255|unique:tenants,domain,{$tenant->id}",
            'max_cameras' => 'sometimes|integer|min:1',
            'max_users' => 'sometimes|integer|min:1',
            'storage_retention_days' => 'sometimes|integer|min:1',
            'plan' => 'sometimes|string|in:starter,professional,enterprise',
            'is_active' => 'sometimes|boolean',
        ]);

        $tenant->update($validated);

        return response()->json($tenant->fresh());
    }

    /**
     * Remove the specified tenant.
     */
    public function destroy(Tenant $tenant): JsonResponse
    {
        if ($tenant->users()->count() > 0) {
            return response()->json([
                'message' => 'Não é possível excluir um tenant com usuários ativos.',
            ], 422);
        }

        $tenant->delete();

        return response()->json(null, 204);
    }
}
