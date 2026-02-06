<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop the generated column first
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE rb_taxes DROP COLUMN default_for_scope");
        }

        Schema::table('rb_taxes', function (Blueprint $table) {
            // Drop existing indexes and constraints
            $table->dropUnique(['tenant_id', 'branch_id', 'name']);
            $table->dropIndex(['tenant_id', 'branch_id', 'is_default']);
            $table->dropForeign(['branch_id']);

            // Make branch_id nullable
            $table->foreignId('branch_id')->nullable()->change()->constrained('branches')->cascadeOnDelete();

            // Add new indexes
            $table->unique(['tenant_id', 'name']);
            $table->index(['tenant_id', 'is_default']);
        });

        // Recreate the generated column with tenant-only scope
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE rb_taxes ADD COLUMN default_for_scope VARCHAR(64) GENERATED ALWAYS AS (CASE WHEN is_default = 1 THEN CAST(tenant_id AS CHAR) ELSE NULL END) STORED");
            DB::statement("ALTER TABLE rb_taxes ADD UNIQUE INDEX unique_default_for_scope (default_for_scope)");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the generated column
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE rb_taxes DROP INDEX unique_default_for_scope");
            DB::statement("ALTER TABLE rb_taxes DROP COLUMN default_for_scope");
        }

        Schema::table('rb_taxes', function (Blueprint $table) {
            // Drop new indexes
            $table->dropUnique(['tenant_id', 'name']);
            $table->dropIndex(['tenant_id', 'is_default']);

            // Make branch_id required again
            $table->foreignId('branch_id')->nullable(false)->change();

            // Restore old indexes
            $table->unique(['tenant_id', 'branch_id', 'name']);
            $table->index(['tenant_id', 'branch_id', 'is_default']);
        });

        // Recreate the original generated column
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE rb_taxes ADD COLUMN default_for_scope VARCHAR(64) GENERATED ALWAYS AS (CASE WHEN is_default = 1 THEN CONCAT(tenant_id, '|', branch_id) ELSE NULL END) STORED");
            DB::statement("ALTER TABLE rb_taxes ADD UNIQUE INDEX unique_default_for_scope (default_for_scope)");
        }
    }
};
