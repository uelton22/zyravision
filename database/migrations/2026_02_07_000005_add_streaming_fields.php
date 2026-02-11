<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->decimal('storage_used_gb', 10, 2)->nullable()->after('metadata');
            $table->timestamp('storage_updated_at')->nullable()->after('storage_used_gb');
            $table->string('pack_model', 100)->nullable()->after('storage_updated_at');
            $table->boolean('hls_enabled')->default(false)->after('pack_model');
            $table->boolean('hls_fmp4_enabled')->default(false)->after('hls_enabled');
            $table->boolean('low_latency_hls_enabled')->default(false)->after('hls_fmp4_enabled');
        });

        Schema::table('cameras', function (Blueprint $table) {
            $table->string('hls_path', 500)->nullable()->after('rtmp_path');
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn([
                'storage_used_gb',
                'storage_updated_at',
                'pack_model',
                'hls_enabled',
                'hls_fmp4_enabled',
                'low_latency_hls_enabled',
            ]);
        });

        Schema::table('cameras', function (Blueprint $table) {
            $table->dropColumn('hls_path');
        });
    }
};
