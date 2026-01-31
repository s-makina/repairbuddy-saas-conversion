<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('rb_job_statuses') || ! Schema::hasTable('branches')) {
            return;
        }

        DB::transaction(function () {
            $branches = DB::table('branches')->select(['id', 'tenant_id'])->orderBy('id')->get();

            foreach ($branches as $branch) {
                DB::table('rb_job_statuses')->updateOrInsert([
                    'tenant_id' => $branch->tenant_id,
                    'branch_id' => $branch->id,
                    'slug' => 'neworder',
                ], [
                    'label' => 'New Order',
                    'email_enabled' => false,
                    'email_template' => null,
                    'sms_enabled' => false,
                    'invoice_label' => 'Quote',
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]);

                DB::table('rb_job_statuses')->updateOrInsert([
                    'tenant_id' => $branch->tenant_id,
                    'branch_id' => $branch->id,
                    'slug' => 'new',
                ], [
                    'label' => 'New',
                    'email_enabled' => false,
                    'email_template' => null,
                    'sms_enabled' => false,
                    'invoice_label' => 'Quote',
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        // no-op
    }
};
