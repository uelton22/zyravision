<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cameras', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('device_id')->nullable();

            // ─── Identification ─────────────────────────────
            $table->string('name');
            $table->string('slug');

            // ─── Channel (when connected to DVR/NVR) ────────
            $table->integer('channel_number')->nullable();       // Channel on DVR (1-128)
            $table->string('channel_type')->default('direct');   // analog, ip, direct

            // ─── Manufacturer (for direct cameras) ──────────
            $table->string('manufacturer')->nullable();
            $table->string('model')->nullable();

            // ─── Connection ─────────────────────────────────
            $table->string('address_type')->default('ipv4');     // ipv4, ipv6, ddns, hostname
            $table->string('address')->nullable();               // Nullable: inherits from device
            $table->integer('port')->default(554);
            $table->string('secondary_address')->nullable();
            $table->string('transport')->default('tcp');         // tcp, udp, auto

            // ─── Authentication ─────────────────────────────
            $table->string('username')->nullable();
            $table->text('password')->nullable();                // Encrypted via cast

            // ─── Stream ─────────────────────────────────────
            $table->string('rtsp_path')->nullable();             // Custom RTSP path override
            $table->string('stream_type')->default('main');      // main, sub, mobile
            $table->string('codec')->nullable();                 // h264, h265
            $table->string('resolution')->nullable();            // 1920x1080
            $table->integer('fps')->nullable();

            // ─── DDNS ───────────────────────────────────────
            $table->string('ddns_provider')->nullable();
            $table->string('ddns_hostname')->nullable();
            $table->boolean('ipv6_preferred')->default(false);
            $table->string('last_resolved_ip')->nullable();
            $table->timestamp('last_resolved_at')->nullable();

            // ─── PTZ & Audio ────────────────────────────────
            $table->boolean('has_ptz')->default(false);
            $table->boolean('has_audio')->default(false);

            // ─── Status ─────────────────────────────────────
            $table->boolean('is_active')->default(true);
            $table->boolean('is_online')->default(false);
            $table->boolean('is_recording')->default(false);
            $table->timestamp('last_seen_at')->nullable();

            // ─── Metadata ───────────────────────────────────
            $table->string('location')->nullable();              // "Portaria - Entrada"
            $table->text('description')->nullable();
            $table->string('snapshot_url')->nullable();
            $table->jsonb('metadata')->default('{}');

            $table->timestamps();
            $table->softDeletes();

            // ─── Foreign Keys ───────────────────────────────
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('device_id')->references('id')->on('devices')->onDelete('set null');

            // ─── Indexes ────────────────────────────────────
            $table->unique(['tenant_id', 'slug']);
            $table->index(['tenant_id', 'is_active']);
            $table->index(['device_id', 'channel_number']);
            $table->index(['tenant_id', 'device_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cameras');
    }
};
