<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rb_jobs', function (Blueprint $table) {
            $table->unsignedBigInteger('plugin_device_post_id')->nullable()->after('assigned_technician_id');
            $table->string('plugin_device_id_text', 255)->nullable()->after('plugin_device_post_id');

            $table->index(['tenant_id', 'branch_id', 'plugin_device_post_id'], 'rb_jobs_plugin_device_post_idx');
        });
    }

    public function down(): void
    {
        Schema::table('rb_jobs', function (Blueprint $table) {
            $table->dropIndex('rb_jobs_plugin_device_post_idx');
            $table->dropColumn(['plugin_device_post_id', 'plugin_device_id_text']);
        });
    }
};
