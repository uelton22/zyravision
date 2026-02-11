<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── recording_segments: cada segmento MP4 individual ───
        Schema::create('recording_segments', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('camera_id');
            $table->uuid('tenant_id')->nullable();
            $table->uuid('storage_backend_id')->nullable();

            // Timing
            $table->timestamp('starts_at');          // Quando o segmento comeca
            $table->timestamp('ends_at')->nullable(); // Quando o segmento termina (null = gravando)
            $table->integer('duration_seconds')->default(0);

            // File info
            $table->string('local_path')->nullable();    // /recordings/cam_xxx/2026-02-10_13-27-37.mp4
            $table->string('remote_path')->nullable();   // recordings/tenant_xxx/cam/2026/02/10/file.mp4
            $table->string('filename');                   // 2026-02-10_13-27-37-371731.mp4
            $table->bigInteger('file_size_bytes')->default(0);

            // Status: writing (sendo gravado), ready (completo local), uploaded (no storage), failed
            $table->string('status', 20)->default('writing');

            // Video metadata
            $table->string('codec', 20)->nullable();      // h264, h265
            $table->string('resolution', 20)->nullable();  // 1920x1080
            $table->unsignedSmallInteger('fps')->nullable();

            // Thumbnail (gerada para timeline)
            $table->string('thumbnail_path')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes para busca rapida na timeline
            $table->index(['camera_id', 'starts_at']);
            $table->index(['camera_id', 'status']);
            $table->index(['tenant_id', 'starts_at']);
            $table->index(['status']);

            // Foreign keys
            $table->foreign('camera_id')->references('id')->on('cameras')->cascadeOnDelete();
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('storage_backend_id')->references('id')->on('storage_backends')->nullOnDelete();
        });

        // ─── Adicionar campo de gravacao continua nas cameras ───
        Schema::table('cameras', function (Blueprint $table) {
            $table->boolean('continuous_recording')->default(false)->after('is_recording');
        });

        // ─── Tabela de configuracoes de gravacao do sistema ───
        Schema::create('recording_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->nullable();

            // Duração do segmento em segundos (default: 60s = 1 minuto)
            $table->integer('segment_duration_seconds')->default(60);

            // Gravacao continua habilitada por padrao para novas cameras
            $table->boolean('continuous_recording_default')->default(true);

            // Retencao em dias (0 = sem limite)
            $table->integer('retention_days')->default(30);

            // Limpeza automatica de segmentos locais apos upload
            $table->boolean('cleanup_local_after_upload')->default(true);

            // Qualidade de gravacao (original, high, medium, low)
            $table->string('recording_quality', 20)->default('original');

            // Storage backend padrao para gravacoes (null = usar padrao do tenant)
            $table->uuid('default_storage_backend_id')->nullable();

            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('default_storage_backend_id')->references('id')->on('storage_backends')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recording_settings');

        Schema::table('cameras', function (Blueprint $table) {
            $table->dropColumn('continuous_recording');
        });

        Schema::dropIfExists('recording_segments');
    }
};
