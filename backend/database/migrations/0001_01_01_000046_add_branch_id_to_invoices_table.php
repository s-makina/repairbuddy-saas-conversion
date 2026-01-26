<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_id')->nullable()->after('tenant_id');
            $table->index(['tenant_id', 'branch_id', 'status']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
        });

        DB::transaction(function () {
            $tenants = DB::table('tenants')->select(['id', 'default_branch_id'])->get();

            foreach ($tenants as $tenant) {
                if (! $tenant->default_branch_id) {
                    continue;
                }

                DB::table('invoices')
                    ->where('tenant_id', $tenant->id)
                    ->whereNull('branch_id')
                    ->update([
                        'branch_id' => $tenant->default_branch_id,
                        'updated_at' => now(),
                    ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropIndex(['tenant_id', 'branch_id', 'status']);
            $table->dropColumn('branch_id');
        });
    }
};
