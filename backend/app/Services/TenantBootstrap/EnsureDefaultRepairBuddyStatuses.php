<?php

namespace App\Services\TenantBootstrap;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EnsureDefaultRepairBuddyStatuses
{
    public function ensure(int $tenantId): void
    {
        DB::transaction(function () use ($tenantId) {
            $jobDefaults = [
                ['slug' => 'new', 'label' => 'New Order', 'invoice_label' => 'Invoice', 'email_enabled' => false],
                ['slug' => 'quote', 'label' => 'Quote', 'invoice_label' => 'Quote', 'email_enabled' => false],
                ['slug' => 'cancelled', 'label' => 'Cancelled', 'invoice_label' => 'Cancelled', 'email_enabled' => false],
                ['slug' => 'inprocess', 'label' => 'In Process', 'invoice_label' => 'Work Order', 'email_enabled' => true],
                ['slug' => 'inservice', 'label' => 'In Service', 'invoice_label' => 'Work Order', 'email_enabled' => true],
                ['slug' => 'ready_complete', 'label' => 'Ready/Complete', 'invoice_label' => 'Invoice', 'email_enabled' => true],
                ['slug' => 'delivered', 'label' => 'Delivered', 'invoice_label' => 'Invoice', 'email_enabled' => true],
            ];

            $paymentDefaults = [
                ['slug' => 'nostatus', 'label' => 'No Status'],
                ['slug' => 'credit', 'label' => 'Credit'],
                ['slug' => 'paid', 'label' => 'Paid'],
                ['slug' => 'partial', 'label' => 'Partially Paid'],
            ];

            // Seed the generic statuses table (used by some parts of the system)
            $this->seedStatusesTable($tenantId, $jobDefaults, $paymentDefaults);

            // Seed the rb_job_statuses and rb_payment_statuses tables (used by settings UI)
            $this->seedRbJobStatusesTable($tenantId, $jobDefaults);
            $this->seedRbPaymentStatusesTable($tenantId, $paymentDefaults);
        });
    }

    private function seedStatusesTable(int $tenantId, array $jobDefaults, array $paymentDefaults): void
    {
        if (! Schema::hasTable('statuses')) {
            return;
        }

        $hasStatusCode = Schema::hasColumn('statuses', 'code');
        $hasStatusDescription = Schema::hasColumn('statuses', 'description');
        $hasStatusInvoiceLabel = Schema::hasColumn('statuses', 'invoice_label');

        if ($hasStatusCode) {
            foreach ($paymentDefaults as $s) {
                $exists = DB::table('statuses')->where([
                    'tenant_id' => $tenantId,
                    'status_type' => 'Payment',
                    'code' => $s['slug'],
                ])->exists();

                if (! $exists) {
                    DB::table('statuses')->insert([
                        'tenant_id' => $tenantId,
                        'status_type' => 'Payment',
                        'code' => $s['slug'],
                        'label' => $s['label'],
                        'email_enabled' => false,
                        'email_template' => null,
                        'sms_enabled' => false,
                        'is_active' => true,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]);
                }
            }

            foreach ($jobDefaults as $s) {
                $update = [
                    'label' => $s['label'],
                    'email_enabled' => false,
                    'email_template' => null,
                    'sms_enabled' => false,
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ];

                if ($hasStatusDescription) {
                    $update['description'] = null;
                }
                if ($hasStatusInvoiceLabel) {
                    $update['invoice_label'] = $s['invoice_label'];
                }

                DB::table('statuses')->updateOrInsert([
                    'tenant_id' => $tenantId,
                    'status_type' => 'Job',
                    'code' => $s['slug'],
                ], $update);
            }
        }
    }

    private function seedRbJobStatusesTable(int $tenantId, array $jobDefaults): void
    {
        if (! Schema::hasTable('rb_job_statuses')) {
            return;
        }

        foreach ($jobDefaults as $s) {
            DB::table('rb_job_statuses')->updateOrInsert([
                'tenant_id' => $tenantId,
                'slug' => $s['slug'],
            ], [
                'label' => $s['label'],
                'invoice_label' => $s['invoice_label'],
                'email_enabled' => $s['email_enabled'] ?? false,
                'email_template' => null,
                'sms_enabled' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function seedRbPaymentStatusesTable(int $tenantId, array $paymentDefaults): void
    {
        if (! Schema::hasTable('rb_payment_statuses')) {
            return;
        }

        foreach ($paymentDefaults as $s) {
            DB::table('rb_payment_statuses')->updateOrInsert([
                'tenant_id' => $tenantId,
                'slug' => $s['slug'],
            ], [
                'label' => $s['label'],
                'email_template' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
