<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->integer('rtmp_port')->default(1935)->after('http_port');
        });

        Schema::table('cameras', function (Blueprint $table) {
            $table->string('rtmp_path')->nullable()->after('rtsp_path');
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn('rtmp_port');
        });

        Schema::table('cameras', function (Blueprint $table) {
            $table->dropColumn('rtmp_path');
        });
    }
};
