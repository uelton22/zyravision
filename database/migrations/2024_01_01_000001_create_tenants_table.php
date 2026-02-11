<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('domain')->nullable()->unique();
            $table->string('logo_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->jsonb('settings')->default('{}');
            $table->integer('max_cameras')->default(16);
            $table->integer('max_users')->default(5);
            $table->integer('storage_retention_days')->default(7);
            $table->string('plan')->default('starter');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('slug');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
