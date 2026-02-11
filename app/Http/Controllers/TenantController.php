<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TenantController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Tenant::query()->withCount('users');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('slug', 'ilike', "%{$search}%")
                  ->orWhere('domain', 'ilike', "%{$search}%");
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($plan = $request->input('plan')) {
            $query->where('plan', $plan);
        }

        $tenants = $query->orderBy('name')
            ->paginate($request->input('per_page', 15))
            ->withQueryString();

        return Inertia::render('Tenants/Index', [
            'tenants' => $tenants,
            'filters' => $request->only(['search', 'is_active', 'plan']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Tenants/Create', [
            'plans' => Plan::active()->ordered()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'domain' => 'nullable|string|max:255|unique:tenants',
            'max_cameras' => 'required|integer|min:1|max:10000',
            'max_users' => 'required|integer|min:1|max:1000',
            'storage_retention_days' => 'required|integer|min:1|max:365',
            'plan' => 'required|string|in:starter,professional,enterprise',
            'is_active' => 'boolean',
        ]);

        $tenant = Tenant::create([
            ...$validated,
            'slug' => Str::slug($validated['name']) . '-' . Str::random(4),
            'is_active' => $validated['is_active'] ?? true,
        ]);

        // Create subscription
        $plan = Plan::where('slug', $validated['plan'])->first();
        if ($plan) {
            Subscription::create([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'status' => 'active',
                'started_at' => now(),
                'current_period_start' => now(),
                'current_period_end' => now()->addMonth(),
            ]);
        }

        return redirect()->route('tenants.index')
            ->with('success', 'Tenant criado com sucesso.');
    }

    public function show(Tenant $tenant): Response
    {
        // Load users of this tenant
        $users = $tenant->users()
            ->with('roles')
            ->orderBy('name')
            ->get()
            ->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'avatar_url' => $user->avatar_url,
                'is_active' => $user->is_active,
                'last_login_at' => $user->last_login_at?->format('d/m/Y H:i'),
                'role' => $user->roles->first()?->name ?? 'Sem função',
            ]);

        $subscription = $tenant->activeSubscription?->load('plan');

        return Inertia::render('Tenants/Show', [
            'tenant' => $tenant->loadCount('users'),
            'users' => $users,
            'subscription' => $subscription,
            'plan' => $subscription?->plan,
            'quotas' => $tenant->getQuotaSummary(),
            'plans' => Plan::active()->ordered()->get(),
        ]);
    }

    public function edit(Tenant $tenant): Response
    {
        return Inertia::render('Tenants/Edit', [
            'tenant' => $tenant,
            'plans' => Plan::active()->ordered()->get(),
        ]);
    }

    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'domain' => "nullable|string|max:255|unique:tenants,domain,{$tenant->id}",
            'max_cameras' => 'required|integer|min:1|max:10000',
            'max_users' => 'required|integer|min:1|max:1000',
            'storage_retention_days' => 'required|integer|min:1|max:365',
            'plan' => 'required|string|in:starter,professional,enterprise',
            'is_active' => 'boolean',
        ]);

        $oldPlan = $tenant->plan;
        $tenant->update($validated);

        if ($oldPlan !== $validated['plan']) {
            $plan = Plan::where('slug', $validated['plan'])->first();
            if ($plan) {
                $sub = $tenant->activeSubscription;
                if ($sub) {
                    $sub->update(['plan_id' => $plan->id]);
                } else {
                    Subscription::create([
                        'tenant_id' => $tenant->id,
                        'plan_id' => $plan->id,
                        'status' => 'active',
                        'started_at' => now(),
                        'current_period_start' => now(),
                        'current_period_end' => now()->addMonth(),
                    ]);
                }
            }
        }

        return redirect()->route('tenants.index')
            ->with('success', 'Tenant atualizado com sucesso.');
    }

    /**
     * Change the plan/subscription of a tenant (inline from show page).
     */
    public function changePlan(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'plan_slug' => 'required|string|exists:plans,slug',
        ]);

        $plan = Plan::where('slug', $validated['plan_slug'])->firstOrFail();

        // Update tenant plan field
        $tenant->update([
            'plan' => $plan->slug,
            'max_cameras' => $plan->max_cameras,
            'max_users' => $plan->max_users,
            'storage_retention_days' => $plan->retention_days,
        ]);

        // Update or create subscription
        $sub = $tenant->activeSubscription;
        if ($sub) {
            $sub->update([
                'plan_id' => $plan->id,
                'current_period_start' => now(),
                'current_period_end' => now()->addMonth(),
            ]);
        } else {
            Subscription::create([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'status' => 'active',
                'started_at' => now(),
                'current_period_start' => now(),
                'current_period_end' => now()->addMonth(),
            ]);
        }

        return redirect()->route('tenants.show', $tenant)
            ->with('success', "Plano alterado para {$plan->name} com sucesso.");
    }

    public function destroy(Tenant $tenant): RedirectResponse
    {
        if ($tenant->users()->count() > 0) {
            return redirect()->back()
                ->with('error', 'Não é possível excluir um tenant com usuários ativos.');
        }

        $tenant->delete();

        return redirect()->route('tenants.index')
            ->with('success', 'Tenant removido com sucesso.');
    }
}
