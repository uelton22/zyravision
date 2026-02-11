<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Daily aggregated analytics per camera
        Schema::create('camera_analytics_daily', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('camera_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->date('date');

            // Availability metrics
            $table->integer('uptime_seconds')->default(0);
            $table->integer('downtime_seconds')->default(0);
            $table->float('availability_percent')->default(0);

            // Recording metrics
            $table->integer('segments_count')->default(0);
            $table->integer('recording_seconds')->default(0);
            $table->bigInteger('recording_bytes')->default(0);
            $table->integer('recording_gaps')->default(0);

            // Stream metrics
            $table->integer('stream_starts')->default(0);
            $table->integer('stream_errors')->default(0);
            $table->integer('stream_reconnections')->default(0);

            // Health
            $table->integer('offline_events')->default(0);
            $table->integer('error_events')->default(0);
            $table->timestamp('longest_offline_start')->nullable();
            $table->integer('longest_offline_seconds')->default(0);

            $table->timestamps();
            $table->unique(['camera_id', 'date']);
            $table->index(['tenant_id', 'date']);
        });

        // System-wide analytics events (motion, alerts, anomalies, etc.)
        Schema::create('analytics_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('camera_id')->nullable()->constrained()->nullOnDelete();

            $table->string('event_type', 50);     // motion, offline, online, recording_gap, storage_warning, stream_error
            $table->string('severity', 20);        // info, warning, critical
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();   // extra context

            $table->timestamp('occurred_at');
            $table->timestamp('resolved_at')->nullable();
            $table->boolean('is_resolved')->default(false);

            $table->timestamps();
            $table->index(['tenant_id', 'event_type', 'occurred_at']);
            $table->index(['camera_id', 'occurred_at']);
            $table->index(['severity', 'is_resolved']);
        });

        // Storage analytics snapshots (daily)
        Schema::create('storage_analytics_daily', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('storage_backend_id')->nullable()->constrained()->nullOnDelete();
            $table->date('date');

            $table->bigInteger('total_bytes')->default(0);
            $table->integer('total_segments')->default(0);
            $table->integer('segments_added')->default(0);
            $table->integer('segments_deleted')->default(0);
            $table->bigInteger('bytes_added')->default(0);
            $table->bigInteger('bytes_deleted')->default(0);
            $table->float('growth_rate_percent')->default(0);

            $table->timestamps();
            $table->unique(['tenant_id', 'storage_backend_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storage_analytics_daily');
        Schema::dropIfExists('analytics_events');
        Schema::dropIfExists('camera_analytics_daily');
    }
};
