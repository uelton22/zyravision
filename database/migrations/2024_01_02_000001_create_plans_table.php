<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');                          // Starter, Professional, Enterprise
            $table->string('slug')->unique();                // starter, professional, enterprise
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0);     // Preço mensal (BRL)
            $table->string('billing_period')->default('monthly'); // monthly, yearly
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);   // Plano destacado

            // ─── Quotas / Limites ─────────────────────────
            $table->integer('max_cameras')->default(16);
            $table->integer('max_users')->default(5);
            $table->integer('max_storage_gb')->default(50);   // GB total
            $table->integer('retention_days')->default(7);     // Dias de retenção
            $table->integer('max_concurrent_streams')->default(4);
            $table->string('max_resolution')->default('1080p'); // 720p, 1080p, 4k
            $table->integer('max_fps')->default(15);

            // ─── Features (flags) ─────────────────────────
            $table->boolean('has_ai_detection')->default(false);
            $table->boolean('has_ai_recognition')->default(false);
            $table->boolean('has_analytics')->default(false);
            $table->boolean('has_mobile_app')->default(false);
            $table->boolean('has_api_access')->default(false);
            $table->boolean('has_webrtc')->default(false);
            $table->boolean('has_ptz_control')->default(false);
            $table->boolean('has_custom_alerts')->default(false);
            $table->boolean('has_export')->default(false);
            $table->boolean('has_multi_tenant')->default(false);
            $table->boolean('has_sla')->default(false);
            $table->boolean('has_onpremise')->default(false);
            $table->boolean('has_priority_support')->default(false);
            $table->boolean('has_dedicated_support')->default(false);

            $table->jsonb('metadata')->default('{}');        // Campos extras
            $table->integer('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();
        });

        // ─── Subscriptions ────────────────────────────────
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('plan_id');
            $table->string('status')->default('active');      // active, trial, past_due, cancelled, expired
            $table->timestamp('started_at');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->jsonb('metadata')->default('{}');

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('plan_id')->references('id')->on('plans')->onDelete('restrict');
            $table->index(['tenant_id', 'status']);
        });

        // ─── Quota Usage Tracking ─────────────────────────
        Schema::create('quota_usages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('resource');                       // cameras, users, storage_gb, streams
            $table->integer('current_usage')->default(0);
            $table->integer('limit_value')->default(0);
            $table->timestamp('last_calculated_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->unique(['tenant_id', 'resource']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quota_usages');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('plans');
    }
};
