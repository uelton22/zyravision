<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Seed roles and permissions for the RBAC system.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ─── Permissions ──────────────────────────────────
        $permissions = [
            // Users
            'users.view', 'users.create', 'users.update', 'users.delete',
            // Tenants
            'tenants.view', 'tenants.create', 'tenants.update', 'tenants.delete',
            // Cameras
            'cameras.view', 'cameras.create', 'cameras.update', 'cameras.delete',
            'cameras.stream', 'cameras.ptz',
            // Recordings
            'recordings.view', 'recordings.download', 'recordings.delete',
            // Analytics
            'analytics.view', 'analytics.export',
            // Alerts
            'alerts.view', 'alerts.manage', 'alerts.acknowledge',
            // Settings
            'settings.view', 'settings.update',
            // Audit
            'audit.view',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // ─── Roles ────────────────────────────────────────

        // Super Admin — platform-wide access
        $superAdmin = Role::firstOrCreate(['name' => 'super-admin']);
        $superAdmin->givePermissionTo(Permission::all());

        // Admin — full tenant access
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->givePermissionTo([
            'users.view', 'users.create', 'users.update', 'users.delete',
            'cameras.view', 'cameras.create', 'cameras.update', 'cameras.delete',
            'cameras.stream', 'cameras.ptz',
            'recordings.view', 'recordings.download', 'recordings.delete',
            'analytics.view', 'analytics.export',
            'alerts.view', 'alerts.manage', 'alerts.acknowledge',
            'settings.view', 'settings.update',
            'audit.view',
        ]);

        // Operator — daily operations
        $operator = Role::firstOrCreate(['name' => 'operator']);
        $operator->givePermissionTo([
            'cameras.view', 'cameras.stream', 'cameras.ptz',
            'recordings.view', 'recordings.download',
            'analytics.view',
            'alerts.view', 'alerts.acknowledge',
        ]);

        // Viewer — read-only
        $viewer = Role::firstOrCreate(['name' => 'viewer']);
        $viewer->givePermissionTo([
            'cameras.view', 'cameras.stream',
            'recordings.view',
            'alerts.view',
        ]);
    }
}
