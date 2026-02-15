<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rb_device_brands', function (Blueprint $table) {
            if (! Schema::hasColumn('rb_device_brands', 'parent_id')) {
                $table->foreignId('parent_id')
                    ->nullable()
                    ->after('branch_id')
                    ->constrained('rb_device_brands')
                    ->nullOnDelete();

                $table->index(['tenant_id', 'branch_id', 'parent_id'], 'rb_device_brands_parent_idx');
            }
        });

        Schema::table('rb_devices', function (Blueprint $table) {
            if (! Schema::hasColumn('rb_devices', 'image_path')) {
                $table->string('image_path', 255)
                    ->nullable()
                    ->after('model');

                $table->index(['tenant_id', 'branch_id', 'image_path'], 'rb_devices_image_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('rb_devices', function (Blueprint $table) {
            if (Schema::hasColumn('rb_devices', 'image_path')) {
                if (Schema::hasIndex('rb_devices', 'rb_devices_image_idx')) {
                    $table->dropIndex('rb_devices_image_idx');
                }
                $table->dropColumn('image_path');
            }
        });

        Schema::table('rb_device_brands', function (Blueprint $table) {
            if (Schema::hasColumn('rb_device_brands', 'parent_id')) {
                try {
                    $table->dropForeign(['parent_id']);
                } catch (Throwable $e) {
                }
            }
        });

        Schema::table('rb_device_brands', function (Blueprint $table) {
            if (Schema::hasColumn('rb_device_brands', 'parent_id')) {
                if (Schema::hasIndex('rb_device_brands', 'rb_device_brands_parent_idx')) {
                    $table->dropIndex('rb_device_brands_parent_idx');
                }
                $table->dropColumn('parent_id');
            }
        });
    }
};
