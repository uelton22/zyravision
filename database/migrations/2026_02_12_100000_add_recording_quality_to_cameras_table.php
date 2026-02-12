<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cameras', function (Blueprint $table) {
            $table->string('recording_quality', 20)->nullable()->after('continuous_recording');
            $table->string('recording_resolution', 20)->nullable()->after('recording_quality');
            $table->string('recording_codec', 20)->nullable()->after('recording_resolution');
            $table->integer('recording_fps')->nullable()->after('recording_codec');
            $table->string('recording_bitrate', 20)->nullable()->after('recording_fps');
        });
    }

    public function down(): void
    {
        Schema::table('cameras', function (Blueprint $table) {
            $table->dropColumn([
                'recording_quality',
                'recording_resolution',
                'recording_codec',
                'recording_fps',
                'recording_bitrate',
            ]);
        });
    }
};
