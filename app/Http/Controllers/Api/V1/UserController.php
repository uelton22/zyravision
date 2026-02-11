<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    /**
     * Display a listing of users for the tenant.
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::query()
            ->with('roles:id,name')
            ->forTenant($request->user()->tenant_id);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('email', 'ilike', "%{$search}%");
            });
        }

        if ($role = $request->input('role')) {
            $query->role($role);
        }

        $users = $query->orderBy('name')
            ->paginate($request->input('per_page', 15));

        return response()->json($users);
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', Password::defaults()],
            'phone' => 'nullable|string|max:20',
            'role' => 'required|string|in:admin,operator,viewer',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'tenant_id' => $request->user()->tenant_id,
            'is_active' => true,
        ]);

        $user->assignRole($validated['role']);

        return response()->json($user->load('roles'), 201);
    }

    /**
     * Display the specified user.
     */
    public function show(Request $request, User $user): JsonResponse
    {
        if ($user->tenant_id !== $request->user()->tenant_id && ! $request->user()->isSuperAdmin()) {
            abort(403);
        }

        return response()->json($user->load('roles', 'tenant'));
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user): JsonResponse
    {
        if ($user->tenant_id !== $request->user()->tenant_id && ! $request->user()->isSuperAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => "sometimes|string|email|max:255|unique:users,email,{$user->id}",
            'password' => ['sometimes', Password::defaults()],
            'phone' => 'nullable|string|max:20',
            'role' => 'sometimes|string|in:admin,operator,viewer',
            'is_active' => 'sometimes|boolean',
        ]);

        $user->update(collect($validated)->except(['password', 'role'])->toArray());

        if (isset($validated['password'])) {
            $user->update(['password' => Hash::make($validated['password'])]);
        }

        if (isset($validated['role'])) {
            $user->syncRoles([$validated['role']]);
        }

        return response()->json($user->fresh()->load('roles'));
    }

    /**
     * Remove the specified user.
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($user->tenant_id !== $request->user()->tenant_id && ! $request->user()->isSuperAdmin()) {
            abort(403);
        }

        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'Você não pode excluir sua própria conta.'], 422);
        }

        $user->delete();

        return response()->json(null, 204);
    }
}
