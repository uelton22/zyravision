<?php

namespace App\Http\Controllers;

use App\Models\Camera;
use App\Models\Device;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user();
        $user->load('roles', 'tenant');
        $tenant = $user->tenant;

        $subscription = null;
        $quotas = [];

        if ($tenant) {
            $sub = $tenant->activeSubscription;
            if ($sub) {
                $sub->load('plan');
                $subscription = [
                    'id' => $sub->id,
                    'status' => $sub->status,
                    'started_at' => $sub->started_at?->toDateTimeString(),
                    'trial_ends_at' => $sub->trial_ends_at?->toDateTimeString(),
                    'current_period_end' => $sub->current_period_end?->toDateTimeString(),
                    'days_remaining' => $sub->daysRemaining(),
                    'plan' => $sub->plan ? [
                        'id' => $sub->plan->id,
                        'name' => $sub->plan->name,
                        'price' => $sub->plan->price,
                    ] : null,
                ];
            }
            $quotas = $tenant->getQuotaSummary();
        }

        $isSuperAdmin = $user->isSuperAdmin();

        $platformStats = null;
        if ($isSuperAdmin) {
            $platformStats = [
                'total_cameras' => Camera::count(),
                'total_devices' => Device::count(),
                'total_tenants' => Tenant::count(),
                'total_users' => User::count(),
            ];
        }

        return Inertia::render('Profile/Edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'avatar_url' => $user->avatar_url,
                'is_active' => $user->is_active,
                'roles' => $user->roles->map(fn ($r) => ['id' => $r->id, 'name' => $r->name]),
            ],
            'tenant' => $tenant ? [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'plan' => $tenant->plan,
                'storage_retention_days' => $tenant->storage_retention_days,
            ] : null,
            'subscription' => $subscription,
            'quotas' => $quotas,
            'isSuperAdmin' => $isSuperAdmin,
            'platformStats' => $platformStats,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => "required|email|max:255|unique:users,email,{$request->user()->id}",
            'phone' => 'nullable|string|max:20',
        ]);

        $request->user()->update($validated);

        return redirect()->back()->with('success', 'Perfil atualizado com sucesso.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()->back()->with('success', 'Senha alterada com sucesso.');
    }
}
