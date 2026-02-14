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

            if (Schema::hasTable('statuses')) {
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
                }

                if ($hasStatusCode) {
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
        });
    }
}
