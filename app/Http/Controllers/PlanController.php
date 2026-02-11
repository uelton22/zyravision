<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PlanController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Plan::query()->withCount('subscriptions');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('slug', 'ilike', "%{$search}%")
                  ->orWhere('description', 'ilike', "%{$search}%");
            });
        }

        if ($request->has('is_active') && $request->input('is_active') !== '') {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $plans = $query->ordered()
            ->paginate($request->input('per_page', 15))
            ->withQueryString();

        return Inertia::render('Plans/Index', [
            'plans' => $plans,
            'filters' => $request->only(['search', 'is_active']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Plans/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->validationRules());

        $validated['slug'] = Str::slug($validated['name']);

        // Ensure unique slug
        $originalSlug = $validated['slug'];
        $counter = 1;
        while (Plan::withTrashed()->where('slug', $validated['slug'])->exists()) {
            $validated['slug'] = $originalSlug . '-' . $counter++;
        }

        Plan::create($validated);

        return redirect()->route('plans.index')
            ->with('success', 'Plano criado com sucesso.');
    }

    public function show(Plan $plan): Response
    {
        $plan->loadCount('subscriptions');

        $activeSubscriptions = $plan->subscriptions()
            ->whereIn('status', ['active', 'trial'])
            ->with('tenant')
            ->get()
            ->map(fn ($sub) => [
                'id' => $sub->id,
                'tenant_name' => $sub->tenant?->name ?? 'N/A',
                'tenant_id' => $sub->tenant_id,
                'status' => $sub->status,
                'started_at' => $sub->started_at?->format('d/m/Y'),
                'current_period_end' => $sub->current_period_end?->format('d/m/Y'),
            ]);

        return Inertia::render('Plans/Show', [
            'plan' => $plan,
            'features' => $plan->getFeatures(),
            'formattedPrice' => $plan->getFormattedPrice(),
            'activeSubscriptions' => $activeSubscriptions,
        ]);
    }

    public function edit(Plan $plan): Response
    {
        return Inertia::render('Plans/Edit', [
            'plan' => $plan,
        ]);
    }

    public function update(Request $request, Plan $plan): RedirectResponse
    {
        $validated = $request->validate($this->validationRules($plan->id));

        $newSlug = Str::slug($validated['name']);
        if ($newSlug !== $plan->slug) {
            $originalSlug = $newSlug;
            $counter = 1;
            while (Plan::withTrashed()->where('slug', $newSlug)->where('id', '!=', $plan->id)->exists()) {
                $newSlug = $originalSlug . '-' . $counter++;
            }
            $validated['slug'] = $newSlug;
        }

        $plan->update($validated);

        return redirect()->route('plans.index')
            ->with('success', 'Plano atualizado com sucesso.');
    }

    public function destroy(Plan $plan): RedirectResponse
    {
        $activeCount = $plan->subscriptions()
            ->whereIn('status', ['active', 'trial'])
            ->count();

        if ($activeCount > 0) {
            return redirect()->back()
                ->with('error', "Não é possível excluir este plano. Existem {$activeCount} assinatura(s) ativa(s) vinculada(s).");
        }

        $plan->delete();

        return redirect()->route('plans.index')
            ->with('success', 'Plano removido com sucesso.');
    }

    private function validationRules(?string $planId = null): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'price' => 'required|numeric|min:0',
            'billing_period' => 'required|string|in:monthly,yearly,custom',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'integer|min:0',
            // Quotas
            'max_cameras' => 'required|integer|min:1|max:100000',
            'max_users' => 'required|integer|min:1|max:10000',
            'max_storage_gb' => 'required|integer|min:1|max:100000',
            'retention_days' => 'required|integer|min:1|max:3650',
            'max_concurrent_streams' => 'required|integer|min:1|max:10000',
            'max_resolution' => 'required|string|in:720p,1080p,4k',
            'max_fps' => 'required|integer|min:1|max:120',
            // Features
            'has_ai_detection' => 'boolean',
            'has_ai_recognition' => 'boolean',
            'has_analytics' => 'boolean',
            'has_mobile_app' => 'boolean',
            'has_api_access' => 'boolean',
            'has_webrtc' => 'boolean',
            'has_ptz_control' => 'boolean',
            'has_custom_alerts' => 'boolean',
            'has_export' => 'boolean',
            'has_multi_tenant' => 'boolean',
            'has_sla' => 'boolean',
            'has_onpremise' => 'boolean',
            'has_priority_support' => 'boolean',
            'has_dedicated_support' => 'boolean',
        ];
    }
}
