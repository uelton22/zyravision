<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = config('activitylog.table_name', 'activity_log');
        $connection = config('activitylog.database_connection');

        Schema::connection($connection)->table($tableName, function (Blueprint $table) {
            $table->dropIndex('subject');
            $table->dropIndex('causer');
        });

        Schema::connection($connection)->table($tableName, function (Blueprint $table) {
            $table->string('subject_id')->nullable()->change();
            $table->string('causer_id')->nullable()->change();
        });

        Schema::connection($connection)->table($tableName, function (Blueprint $table) {
            $table->index(['subject_type', 'subject_id'], 'subject');
            $table->index(['causer_type', 'causer_id'], 'causer');
        });
    }

    public function down(): void
    {
        $tableName = config('activitylog.table_name', 'activity_log');
        $connection = config('activitylog.database_connection');

        Schema::connection($connection)->table($tableName, function (Blueprint $table) {
            $table->dropIndex('subject');
            $table->dropIndex('causer');
        });

        Schema::connection($connection)->table($tableName, function (Blueprint $table) {
            $table->unsignedBigInteger('subject_id')->nullable()->change();
            $table->unsignedBigInteger('causer_id')->nullable()->change();
        });

        Schema::connection($connection)->table($tableName, function (Blueprint $table) {
            $table->index(['subject_type', 'subject_id'], 'subject');
            $table->index(['causer_type', 'causer_id'], 'causer');
        });
    }
};
