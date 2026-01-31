<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rb_device_types', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->after('branch_id');
            $table->text('description')->nullable()->after('name');
            $table->string('image_path', 255)->nullable()->after('description');

            $table->index(['tenant_id', 'branch_id', 'parent_id']);
        });

        Schema::table('rb_device_types', function (Blueprint $table) {
            $table->foreign('parent_id')
                ->references('id')
                ->on('rb_device_types')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('rb_device_types', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
        });

        Schema::table('rb_device_types', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'branch_id', 'parent_id']);
            $table->dropColumn(['parent_id', 'description', 'image_path']);
        });
    }
};
