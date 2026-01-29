<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('rb_job_statuses') || ! Schema::hasTable('rb_payment_statuses') || ! Schema::hasTable('branches')) {
            return;
        }

        DB::transaction(function () {
            $branches = DB::table('branches')->select(['id', 'tenant_id'])->orderBy('id')->get();

            $jobDefaults = [
                ['slug' => 'new_quote', 'label' => 'New / Quote', 'invoice_label' => 'Quote'],
                ['slug' => 'in_process', 'label' => 'In process', 'invoice_label' => 'Work Order'],
                ['slug' => 'ready', 'label' => 'Ready', 'invoice_label' => 'Invoice'],
                ['slug' => 'completed', 'label' => 'Completed', 'invoice_label' => 'Invoice'],
                ['slug' => 'delivered', 'label' => 'Delivered', 'invoice_label' => 'Invoice'],
                ['slug' => 'cancelled', 'label' => 'Cancelled', 'invoice_label' => 'Cancelled'],
            ];

            $paymentDefaults = [
                ['slug' => 'pending', 'label' => 'Pending'],
                ['slug' => 'paid', 'label' => 'Paid'],
                ['slug' => 'refunded', 'label' => 'Refunded'],
                ['slug' => 'failed', 'label' => 'Failed'],
            ];

            foreach ($branches as $branch) {
                foreach ($jobDefaults as $s) {
                    DB::table('rb_job_statuses')->updateOrInsert([
                        'tenant_id' => $branch->tenant_id,
                        'branch_id' => $branch->id,
                        'slug' => $s['slug'],
                    ], [
                        'label' => $s['label'],
                        'email_enabled' => false,
                        'email_template' => null,
                        'sms_enabled' => false,
                        'invoice_label' => $s['invoice_label'],
                        'is_active' => true,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]);
                }

                foreach ($paymentDefaults as $s) {
                    DB::table('rb_payment_statuses')->updateOrInsert([
                        'tenant_id' => $branch->tenant_id,
                        'branch_id' => $branch->id,
                        'slug' => $s['slug'],
                    ], [
                        'label' => $s['label'],
                        'email_template' => null,
                        'is_active' => true,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]);
                }
            }
        });
    }

    public function down(): void
    {
        // no-op
    }
};
