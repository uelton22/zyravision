<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('streams', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('camera_id');
            $table->uuid('tenant_id');

            $table->string('status', 20)->default('starting');
            $table->string('mediamtx_path')->unique();
            $table->string('hls_url')->nullable();
            $table->string('webrtc_url')->nullable();
            $table->string('rtsp_input_url')->nullable();
            $table->integer('pid')->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('stopped_at')->nullable();
            $table->timestamp('last_health_check')->nullable();

            $table->text('error_message')->nullable();
            $table->jsonb('metadata')->nullable();

            $table->timestamps();

            $table->foreign('camera_id')->references('id')->on('cameras')->cascadeOnDelete();
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();

            $table->index('status');
            $table->index('camera_id');
            $table->index('tenant_id');
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('streams');
    }
};
