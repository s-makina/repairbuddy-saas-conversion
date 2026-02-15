<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rb_part_types', function (Blueprint $table) {
            $table->foreignId('parent_id')
                ->nullable()
                ->after('image_path')
                ->constrained('rb_part_types')
                ->nullOnDelete();

            $table->index(['tenant_id', 'branch_id', 'parent_id']);
        });

        Schema::table('rb_part_brands', function (Blueprint $table) {
            $table->foreignId('parent_id')
                ->nullable()
                ->after('description')
                ->constrained('rb_part_brands')
                ->nullOnDelete();

            $table->index(['tenant_id', 'branch_id', 'parent_id']);
        });
    }

    public function down(): void
    {
        Schema::table('rb_part_types', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropIndex(['tenant_id', 'branch_id', 'parent_id']);
            $table->dropColumn('parent_id');
        });

        Schema::table('rb_part_brands', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropIndex(['tenant_id', 'branch_id', 'parent_id']);
            $table->dropColumn('parent_id');
        });
    }
};
