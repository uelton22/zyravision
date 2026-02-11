<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Change id to UUID
            // Note: This requires a fresh database or manual migration
            // For new projects, we'll handle this in the seeder

            $table->uuid('tenant_id')->nullable()->after('id');
            $table->string('avatar_url')->nullable()->after('email');
            $table->string('phone')->nullable()->after('avatar_url');
            $table->boolean('is_active')->default(true)->after('phone');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
            $table->string('last_login_ip')->nullable()->after('last_login_at');
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index('tenant_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropColumn([
                'tenant_id', 'avatar_url', 'phone', 'is_active',
                'last_login_at', 'last_login_ip',
            ]);
            $table->dropSoftDeletes();
        });
    }
};
