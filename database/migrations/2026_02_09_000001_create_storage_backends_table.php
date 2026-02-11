<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('storage_backends', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();

            $table->string('name'); // Ex: "NAS Principal", "AWS S3 Backup"
            $table->string('driver', 30); // local, lvm, nas, nfs, cifs, sftp, cdntv, minio, s3, azure_blob, gcs, ftp, webdav, iscsi, custom
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->integer('priority')->default(0); // Failover order: lower = higher priority

            // Encrypted JSON config — credentials and driver-specific parameters
            $table->text('config'); // Encrypted via cast

            // Health monitoring
            $table->string('health_status', 20)->default('unknown'); // healthy, degraded, offline, unknown
            $table->timestamp('last_health_check_at')->nullable();
            $table->integer('health_check_latency_ms')->nullable();
            $table->text('health_check_message')->nullable();

            // Capacity tracking
            $table->decimal('total_capacity_gb', 12, 2)->nullable();
            $table->decimal('used_capacity_gb', 12, 2)->nullable();

            // Stats
            $table->unsignedBigInteger('total_files')->default(0);
            $table->unsignedBigInteger('total_bytes_written')->default(0);

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['tenant_id', 'is_active']);
            $table->index(['tenant_id', 'is_default']);
            $table->index(['tenant_id', 'driver']);
            $table->index(['health_status']);
        });

        // Add storage_backend_id to recordings table
        Schema::table('recordings', function (Blueprint $table) {
            $table->foreignUuid('storage_backend_id')
                ->nullable()
                ->after('stream_id')
                ->constrained('storage_backends')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('recordings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('storage_backend_id');
        });

        Schema::dropIfExists('storage_backends');
    }
};
