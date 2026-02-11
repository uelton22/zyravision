<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Set tenant_id = NULL for all users with super-admin role
        DB::table('users')
            ->whereIn('id', function ($query) {
                $query->select('model_id')
                    ->from('model_has_roles')
                    ->where('model_type', 'App\\Models\\User')
                    ->whereIn('role_id', function ($q) {
                        $q->select('id')
                            ->from('roles')
                            ->where('name', 'super-admin');
                    });
            })
            ->update(['tenant_id' => null]);
    }

    public function down(): void
    {
        // Re-attach super-admins to the platform tenant
        $platformTenantId = DB::table('tenants')
            ->where('slug', 'zyravision-platform')
            ->value('id');

        if ($platformTenantId) {
            DB::table('users')
                ->whereIn('id', function ($query) {
                    $query->select('model_id')
                        ->from('model_has_roles')
                        ->where('model_type', 'App\\Models\\User')
                        ->whereIn('role_id', function ($q) {
                            $q->select('id')
                                ->from('roles')
                                ->where('name', 'super-admin');
                        });
                })
                ->update(['tenant_id' => $platformTenantId]);
        }
    }
};
