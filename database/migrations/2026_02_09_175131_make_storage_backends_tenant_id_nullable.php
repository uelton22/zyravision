<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('storage_backends', function (Blueprint $table) {
            // Drop the existing foreign key constraint
            $table->dropForeign(['tenant_id']);

            // Make tenant_id nullable (global backends for super-admin)
            $table->uuid('tenant_id')->nullable()->change();

            // Re-add foreign key with nullable support
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('storage_backends', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);

            $table->uuid('tenant_id')->nullable(false)->change();

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();
        });
    }
};
