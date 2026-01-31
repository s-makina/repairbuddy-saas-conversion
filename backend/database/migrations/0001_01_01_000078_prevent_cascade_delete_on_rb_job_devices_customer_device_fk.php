<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rb_job_devices', function (Blueprint $table) {
            $table->dropForeign(['customer_device_id']);
        });

        Schema::table('rb_job_devices', function (Blueprint $table) {
            $table->foreign('customer_device_id')
                ->references('id')
                ->on('rb_customer_devices')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('rb_job_devices', function (Blueprint $table) {
            $table->dropForeign(['customer_device_id']);
        });

        Schema::table('rb_job_devices', function (Blueprint $table) {
            $table->foreign('customer_device_id')
                ->references('id')
                ->on('rb_customer_devices')
                ->cascadeOnDelete();
        });
    }
};
