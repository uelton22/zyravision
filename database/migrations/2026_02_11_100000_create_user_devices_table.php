<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_devices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('device_id')->comment('Unique device identifier (UUID from mobile app)');
            $table->string('device_name');
            $table->enum('platform', ['ios', 'android', 'web'])->default('android');
            $table->string('app_version')->nullable();
            $table->string('os_version')->nullable();
            $table->string('push_token')->nullable();
            $table->enum('push_provider', ['fcm', 'apns'])->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_active_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'device_id']);
            $table->index(['tenant_id', 'is_active']);
            $table->index('push_token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_devices');
    }
};
