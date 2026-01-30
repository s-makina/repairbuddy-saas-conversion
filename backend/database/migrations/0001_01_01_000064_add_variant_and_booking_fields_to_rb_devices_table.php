<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rb_devices', function (Blueprint $table) {
            $table->foreignId('parent_device_id')
                ->nullable()
                ->after('device_brand_id')
                ->constrained('rb_devices')
                ->nullOnDelete();

            $table->boolean('disable_in_booking_form')->default(false)->after('model');
            $table->boolean('is_other')->default(false)->after('disable_in_booking_form');

            $table->index(['tenant_id', 'branch_id', 'parent_device_id'], 'rb_devices_parent_idx');
            $table->index(['tenant_id', 'branch_id', 'disable_in_booking_form'], 'rb_devices_booking_idx');
            $table->index(['tenant_id', 'branch_id', 'is_other'], 'rb_devices_is_other_idx');
        });
    }

    public function down(): void
    {
        Schema::table('rb_devices', function (Blueprint $table) {
            $table->dropIndex('rb_devices_parent_idx');
            $table->dropIndex('rb_devices_booking_idx');
            $table->dropIndex('rb_devices_is_other_idx');

            $table->dropForeign(['parent_device_id']);
            $table->dropColumn('parent_device_id');

            $table->dropColumn('disable_in_booking_form');
            $table->dropColumn('is_other');
        });
    }
};
