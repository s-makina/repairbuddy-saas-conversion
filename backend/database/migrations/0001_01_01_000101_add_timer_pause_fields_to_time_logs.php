<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rb_time_logs', function (Blueprint $table) {
            $table->dateTime('paused_at')->nullable()->after('end_time');
            $table->integer('accumulated_seconds')->default(0)->after('total_minutes');
            
            // Also add device columns for timer persistence
            $table->string('device_id', 100)->nullable()->after('device_data_json');
            $table->string('device_serial', 100)->nullable()->after('device_id');
            $table->integer('device_index')->default(0)->after('device_serial');
        });
    }

    public function down(): void
    {
        Schema::table('rb_time_logs', function (Blueprint $table) {
            $table->dropColumn(['paused_at', 'accumulated_seconds', 'device_id', 'device_serial', 'device_index']);
        });
    }
};
