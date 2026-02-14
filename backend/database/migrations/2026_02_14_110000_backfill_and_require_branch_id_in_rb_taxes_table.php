<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            if (! Schema::hasTable('rb_taxes')) {
                return;
            }

            if (! Schema::hasTable('tenants') || ! Schema::hasTable('branches')) {
                return;
            }

            $tenants = DB::table('tenants')->select(['id', 'default_branch_id'])->orderBy('id')->get();

            foreach ($tenants as $tenant) {
                $branchId = is_numeric($tenant->default_branch_id) ? (int) $tenant->default_branch_id : null;
                if (! $branchId) {
                    $branchId = DB::table('branches')
                        ->where('tenant_id', $tenant->id)
                        ->orderBy('id')
                        ->value('id');
                }

                if (! $branchId) {
                    continue;
                }

                DB::table('rb_taxes')
                    ->where('tenant_id', $tenant->id)
                    ->whereNull('branch_id')
                    ->update([
                        'branch_id' => $branchId,
                        'updated_at' => now(),
                    ]);
            }
        });

        if (DB::getDriverName() === 'mysql') {
            // Restore generated column to include branch scope, so only one default per tenant+branch.
            DB::statement("ALTER TABLE rb_taxes DROP COLUMN default_for_scope");
        }

        Schema::table('rb_taxes', function (Blueprint $table) {
            // Make branch_id required again.
            $table->foreignId('branch_id')->nullable(false)->change();

            // Revert indexes to branch-level uniqueness.
            $table->dropUnique(['tenant_id', 'name']);
            $table->dropIndex(['tenant_id', 'is_default']);
            $table->unique(['tenant_id', 'branch_id', 'name']);
            $table->index(['tenant_id', 'branch_id', 'is_default']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE rb_taxes ADD COLUMN default_for_scope VARCHAR(64) GENERATED ALWAYS AS (CASE WHEN is_default = 1 THEN CONCAT(tenant_id, '|', branch_id) ELSE NULL END) STORED");
            DB::statement("ALTER TABLE rb_taxes ADD UNIQUE INDEX unique_default_for_scope (default_for_scope)");
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('rb_taxes')) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE rb_taxes DROP INDEX unique_default_for_scope");
            DB::statement("ALTER TABLE rb_taxes DROP COLUMN default_for_scope");
        }

        Schema::table('rb_taxes', function (Blueprint $table) {
            $table->dropUnique(['tenant_id', 'branch_id', 'name']);
            $table->dropIndex(['tenant_id', 'branch_id', 'is_default']);

            $table->foreignId('branch_id')->nullable()->change();

            $table->unique(['tenant_id', 'name']);
            $table->index(['tenant_id', 'is_default']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE rb_taxes ADD COLUMN default_for_scope VARCHAR(64) GENERATED ALWAYS AS (CASE WHEN is_default = 1 THEN CAST(tenant_id AS CHAR) ELSE NULL END) STORED");
            DB::statement("ALTER TABLE rb_taxes ADD UNIQUE INDEX unique_default_for_scope (default_for_scope)");
        }
    }
};
