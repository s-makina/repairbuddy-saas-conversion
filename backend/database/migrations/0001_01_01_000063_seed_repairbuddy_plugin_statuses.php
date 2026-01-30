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
                ['slug' => 'new', 'label' => 'New Order', 'invoice_label' => 'Invoice'],
                ['slug' => 'quote', 'label' => 'Quote', 'invoice_label' => 'Quote'],
                ['slug' => 'cancelled', 'label' => 'Cancelled', 'invoice_label' => 'Cancelled'],
                ['slug' => 'inprocess', 'label' => 'In Process', 'invoice_label' => 'Work Order'],
                ['slug' => 'inservice', 'label' => 'In Service', 'invoice_label' => 'Work Order'],
                ['slug' => 'ready_complete', 'label' => 'Ready/Complete', 'invoice_label' => 'Invoice'],
                ['slug' => 'delivered', 'label' => 'Delivered', 'invoice_label' => 'Invoice'],
            ];

            $paymentDefaults = [
                ['slug' => 'nostatus', 'label' => 'No Status'],
                ['slug' => 'credit', 'label' => 'Credit'],
                ['slug' => 'paid', 'label' => 'Paid'],
                ['slug' => 'partial', 'label' => 'Partially Paid'],
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
