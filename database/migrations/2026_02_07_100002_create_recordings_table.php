<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recordings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('camera_id');
            $table->uuid('tenant_id');
            $table->uuid('stream_id')->nullable();

            $table->string('status', 20)->default('recording');
            $table->string('storage_path');
            $table->bigInteger('file_size_bytes')->default(0);
            $table->integer('duration_seconds')->default(0);

            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();

            $table->string('codec', 20)->nullable();
            $table->string('resolution', 20)->nullable();
            $table->integer('fps')->nullable();
            $table->jsonb('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('camera_id')->references('id')->on('cameras')->cascadeOnDelete();
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('stream_id')->references('id')->on('streams')->nullOnDelete();

            $table->index('camera_id');
            $table->index('tenant_id');
            $table->index('status');
            $table->index(['tenant_id', 'camera_id']);
            $table->index(['started_at', 'ended_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recordings');
    }
};
