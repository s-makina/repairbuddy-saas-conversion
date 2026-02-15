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
                $table->foreignId('parent_id')->nullable()->after('branch_id');
                $table->index(['tenant_id', 'branch_id', 'parent_id']);
            }
        });

        Schema::table('rb_device_brands', function (Blueprint $table) {
            if (Schema::hasColumn('rb_device_brands', 'parent_id')) {
                $table->foreign('parent_id')
                    ->references('id')
                    ->on('rb_device_brands')
                    ->restrictOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('rb_device_brands', function (Blueprint $table) {
            if (Schema::hasColumn('rb_device_brands', 'parent_id')) {
                $table->dropForeign(['parent_id']);
            }
        });

        Schema::table('rb_device_brands', function (Blueprint $table) {
            if (Schema::hasColumn('rb_device_brands', 'parent_id')) {
                $table->dropIndex(['tenant_id', 'branch_id', 'parent_id']);
                $table->dropColumn('parent_id');
            }
        });
    }
};
