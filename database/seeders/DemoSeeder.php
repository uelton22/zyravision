<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // ─── Platform Tenant (for super-admin) ───────────
        $platformTenant = Tenant::firstOrCreate(
            ['slug' => 'zyravision-platform'],
            [
                'name' => 'ZyraVision Platform',
                'slug' => 'zyravision-platform',
                'is_active' => true,
                'max_cameras' => 99999,
                'max_users' => 99999,
                'storage_retention_days' => 365,
                'plan' => 'enterprise',
                'settings' => [
                    'timezone' => 'America/Sao_Paulo',
                    'language' => 'pt_BR',
                    'internal' => true,
                ],
            ]
        );

        // ─── Demo Tenant ──────────────────────────────────
        $demoTenant = Tenant::firstOrCreate(
            ['slug' => 'demo'],
            [
                'name' => 'Empresa Demonstração',
                'slug' => 'demo',
                'is_active' => true,
                'max_cameras' => 64,
                'max_users' => 50,
                'storage_retention_days' => 30,
                'plan' => 'professional',
                'settings' => [
                    'timezone' => 'America/Sao_Paulo',
                    'language' => 'pt_BR',
                ],
            ]
        );

        // ─── Demo Subscription ────────────────────────────
        $professionalPlan = Plan::where('slug', 'professional')->first();
        if ($professionalPlan && !$demoTenant->activeSubscription) {
            Subscription::firstOrCreate(
                ['tenant_id' => $demoTenant->id, 'status' => 'active'],
                [
                    'plan_id' => $professionalPlan->id,
                    'status' => 'active',
                    'started_at' => now(),
                    'current_period_start' => now(),
                    'current_period_end' => now()->addYear(),
                ]
            );
        }

        // ─── Demo Users ──────────────────────────────────
        $admin = User::firstOrCreate(
            ['email' => 'admin@demo.zyravision.com.br'],
            [
                'name' => 'Admin Demonstração',
                'password' => Hash::make('demo2024'),
                'tenant_id' => $demoTenant->id,
                'is_active' => true,
            ]
        );
        if (!$admin->hasAnyRole(['admin'])) {
            $admin->assignRole('admin');
        }

        $operator = User::firstOrCreate(
            ['email' => 'operador@demo.zyravision.com.br'],
            [
                'name' => 'Operador Demonstração',
                'password' => Hash::make('demo2024'),
                'tenant_id' => $demoTenant->id,
                'is_active' => true,
            ]
        );
        if (!$operator->hasAnyRole(['operator'])) {
            $operator->assignRole('operator');
        }

        $viewer = User::firstOrCreate(
            ['email' => 'viewer@demo.zyravision.com.br'],
            [
                'name' => 'Visualizador Demonstração',
                'password' => Hash::make('demo2024'),
                'tenant_id' => $demoTenant->id,
                'is_active' => true,
            ]
        );
        if (!$viewer->hasAnyRole(['viewer'])) {
            $viewer->assignRole('viewer');
        }

        // ─── Super Admin (sem tenant — acesso global) ────
        $superAdmin = User::firstOrCreate(
            ['email' => 'super@zyravision.com.br'],
            [
                'name' => 'Super Administrador',
                'password' => Hash::make('ZyraVision@2024!'),
                'tenant_id' => null,
                'is_active' => true,
            ]
        );
        if (!$superAdmin->hasAnyRole(['super-admin'])) {
            $superAdmin->assignRole('super-admin');
        }

        // Ensure super-admin has no tenant (global access)
        User::where('email', 'super@zyravision.com.br')
            ->update(['tenant_id' => null]);
    }
}
