<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');

            // ─── Identification ─────────────────────────────
            $table->string('name');
            $table->string('slug');
            $table->string('type');                          // dvr, nvr, encoder, camera, hybrid

            // ─── Manufacturer ───────────────────────────────
            $table->string('manufacturer');                  // intelbras, hikvision, dahua, etc.
            $table->string('model')->nullable();
            $table->string('firmware_version')->nullable();

            // ─── Connection ─────────────────────────────────
            $table->string('address_type')->default('ipv4'); // ipv4, ipv6, ddns, hostname
            $table->string('address');                       // IP or hostname
            $table->integer('port')->default(554);           // RTSP port
            $table->integer('http_port')->default(80);       // Web/HTTP port
            $table->string('secondary_address')->nullable(); // Fallback address
            $table->string('transport')->default('tcp');     // tcp, udp, auto

            // ─── Authentication ─────────────────────────────
            $table->string('username')->nullable();
            $table->text('password')->nullable();            // Encrypted via cast

            // ─── Capacity ───────────────────────────────────
            $table->integer('total_channels')->default(1);
            $table->integer('active_channels')->default(0);

            // ─── ONVIF ──────────────────────────────────────
            $table->boolean('onvif_supported')->default(false);
            $table->string('onvif_profile')->nullable();     // Profile S, T, G

            // ─── Status ─────────────────────────────────────
            $table->boolean('is_active')->default(true);
            $table->boolean('is_online')->default(false);
            $table->timestamp('last_seen_at')->nullable();
            $table->integer('uptime_seconds')->default(0);

            // ─── DDNS ───────────────────────────────────────
            $table->string('ddns_provider')->nullable();
            $table->string('ddns_hostname')->nullable();
            $table->boolean('ipv6_preferred')->default(false);
            $table->string('last_resolved_ip')->nullable();
            $table->timestamp('last_resolved_at')->nullable();

            // ─── Metadata ───────────────────────────────────
            $table->string('location')->nullable();          // "Predio A - Sala TI"
            $table->string('serial_number')->nullable();
            $table->jsonb('metadata')->default('{}');

            $table->timestamps();
            $table->softDeletes();

            // ─── Foreign Keys ───────────────────────────────
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');

            // ─── Indexes ────────────────────────────────────
            $table->unique(['tenant_id', 'slug']);
            $table->index(['tenant_id', 'type']);
            $table->index(['tenant_id', 'is_active']);
            $table->index('manufacturer');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
