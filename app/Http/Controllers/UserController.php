<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     * Super-admin can filter by tenant. Admin sees only their tenant.
     */
    public function index(Request $request): Response
    {
        $currentUser = $request->user();
        $isSuperAdmin = $currentUser->isSuperAdmin();

        $query = User::query()->with('roles', 'tenant');

        if ($isSuperAdmin) {
            // Super-admin can filter by tenant
            if ($tenantId = $request->input('tenant_id')) {
                $query->where('tenant_id', $tenantId);
            }
        } else {
            // Regular admin only sees their tenant
            $query->forTenant($currentUser->tenant_id);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('email', 'ilike', "%{$search}%");
            });
        }

        if ($role = $request->input('role')) {
            $query->role($role);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $users = $query->orderBy('name')
            ->paginate($request->input('per_page', 15))
            ->withQueryString();

        // For super-admin: provide tenant list for filter
        $tenants = $isSuperAdmin
            ? Tenant::orderBy('name')->get(['id', 'name', 'slug'])
            : [];

        return Inertia::render('Users/Index', [
            'users' => $users,
            'filters' => $request->only(['search', 'role', 'is_active', 'tenant_id']),
            'tenants' => $tenants,
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(Request $request): Response
    {
        $currentUser = $request->user();
        $isSuperAdmin = $currentUser->isSuperAdmin();

        // Check quota before allowing creation
        $tenant = $isSuperAdmin && $request->input('tenant_id')
            ? Tenant::find($request->input('tenant_id'))
            : $currentUser->tenant;

        $quotaReached = $tenant && $tenant->hasReachedUserLimit();

        $tenants = $isSuperAdmin
            ? Tenant::active()->orderBy('name')->get(['id', 'name'])
            : [];

        $roles = $isSuperAdmin
            ? ['super-admin', 'admin', 'operator', 'viewer']
            : ['admin', 'operator', 'viewer'];

        return Inertia::render('Users/Create', [
            'roles' => $roles,
            'tenants' => $tenants,
            'isSuperAdmin' => $isSuperAdmin,
            'defaultTenantId' => $request->input('tenant_id') ?? $currentUser->tenant_id,
            'quotaReached' => $quotaReached,
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request): RedirectResponse
    {
        $currentUser = $request->user();
        $isSuperAdmin = $currentUser->isSuperAdmin();

        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', Password::defaults()],
            'phone' => 'nullable|string|max:20',
            'role' => 'required|string|in:' . ($isSuperAdmin ? 'super-admin,admin,operator,viewer' : 'admin,operator,viewer'),
            'is_active' => 'boolean',
        ];

        if ($isSuperAdmin) {
            $rules['tenant_id'] = 'required|uuid|exists:tenants,id';
        }

        $validated = $request->validate($rules);

        $tenantId = $isSuperAdmin
            ? $validated['tenant_id']
            : $currentUser->tenant_id;

        // Enforce quota
        $tenant = Tenant::find($tenantId);
        if ($tenant && $tenant->hasReachedUserLimit()) {
            return redirect()->back()
                ->with('error', "Limite de usuários atingido para o tenant \"{$tenant->name}\". Upgrade o plano para adicionar mais usuários.");
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'tenant_id' => $tenantId,
        ]);

        $user->assignRole($validated['role']);

        $redirect = $request->input('redirect_to') ?: 'users.index';

        return redirect()->route($redirect, $redirect === 'tenants.show' ? $tenantId : [])
            ->with('success', 'Usuário criado com sucesso.');
    }

    /**
     * Display the specified user.
     */
    public function show(User $user): Response
    {
        $this->authorizeForTenant($user);

        return Inertia::render('Users/Show', [
            'user' => $user->load('roles', 'tenant'),
        ]);
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user): Response
    {
        $this->authorizeForTenant($user);
        $isSuperAdmin = auth()->user()->isSuperAdmin();

        $roles = $isSuperAdmin
            ? ['super-admin', 'admin', 'operator', 'viewer']
            : ['admin', 'operator', 'viewer'];

        return Inertia::render('Users/Edit', [
            'user' => $user->load('roles'),
            'roles' => $roles,
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorizeForTenant($user);
        $isSuperAdmin = auth()->user()->isSuperAdmin();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => "required|string|email|max:255|unique:users,email,{$user->id}",
            'password' => ['nullable', Password::defaults()],
            'phone' => 'nullable|string|max:20',
            'role' => 'required|string|in:' . ($isSuperAdmin ? 'super-admin,admin,operator,viewer' : 'admin,operator,viewer'),
            'is_active' => 'boolean',
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        if (!empty($validated['password'])) {
            $user->update(['password' => Hash::make($validated['password'])]);
        }

        $user->syncRoles([$validated['role']]);

        return redirect()->route('users.index')
            ->with('success', 'Usuário atualizado com sucesso.');
    }

    /**
     * Remove the specified user.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorizeForTenant($user);

        if ($user->id === auth()->id()) {
            return redirect()->back()
                ->with('error', 'Você não pode excluir sua própria conta.');
        }

        $tenantId = $user->tenant_id;
        $user->delete();

        // If coming from tenant show page, redirect back there
        if ($request->input('redirect_to') === 'tenants.show') {
            return redirect()->route('tenants.show', $tenantId)
                ->with('success', 'Usuário removido com sucesso.');
        }

        return redirect()->route('users.index')
            ->with('success', 'Usuário removido com sucesso.');
    }

    /**
     * Ensure the user belongs to the same tenant (or is super-admin).
     */
    private function authorizeForTenant(User $user): void
    {
        if (!auth()->user()->isSuperAdmin() && $user->tenant_id !== auth()->user()->tenant_id) {
            abort(403, 'Acesso negado.');
        }
    }
}
