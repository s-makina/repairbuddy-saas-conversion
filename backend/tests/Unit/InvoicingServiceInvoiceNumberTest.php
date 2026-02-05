<?php

namespace Tests\Unit;

use App\Models\BillingPlan;
use App\Models\BillingPlanVersion;
use App\Models\BillingPrice;
use App\Models\Branch;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Support\Billing\InvoicingService;
use App\Support\BranchContext;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoicingServiceInvoiceNumberTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_numbers_are_branch_specific_and_increment_per_year(): void
    {
        config(['billing.seller_country' => 'EG']);

        $tenant = Tenant::query()->create([
            'name' => 'Tenant A',
            'slug' => 'acme',
            'status' => 'active',
            'contact_email' => 't@example.com',
            'currency' => 'USD',
            'billing_country' => 'EG',
        ]);

        TenantContext::set($tenant);

        try {
            $branchA = Branch::query()->create([
                'name' => 'Cape Town',
                'code' => 'CPT',
                'is_active' => true,
            ]);

            $branchB = Branch::query()->create([
                'name' => 'Johannesburg',
                'code' => 'JHB',
                'is_active' => true,
            ]);

            $tenant->forceFill([
                'default_branch_id' => $branchA->id,
            ])->save();

            $subscription = $this->makeActiveSubscription();

            $svc = new InvoicingService();
            $year = (int) now()->format('Y');

            BranchContext::set($branchA);
            $invA1 = $svc->createDraftFromSubscription($tenant, $subscription);
            $invA2 = $svc->createDraftFromSubscription($tenant, $subscription);

            $this->assertSame('RB-ACME-CPT-'.$year.'-0001', $invA1->invoice_number);
            $this->assertSame('RB-ACME-CPT-'.$year.'-0002', $invA2->invoice_number);

            BranchContext::set($branchB);
            $invB1 = $svc->createDraftFromSubscription($tenant, $subscription);

            $this->assertSame('RB-ACME-JHB-'.$year.'-0001', $invB1->invoice_number);
        } finally {
            BranchContext::set(null);
            TenantContext::set(null);
        }
    }

    private function makeActiveSubscription(): TenantSubscription
    {
        $plan = BillingPlan::query()->create([
            'code' => 'starter',
            'name' => 'Starter',
            'is_active' => true,
        ]);

        $version = BillingPlanVersion::query()->create([
            'billing_plan_id' => $plan->id,
            'version' => 1,
            'status' => 'active',
            'locked_at' => now(),
        ]);

        $price = BillingPrice::query()->create([
            'billing_plan_version_id' => $version->id,
            'currency' => 'USD',
            'interval' => 'month',
            'amount_cents' => 10000,
            'trial_days' => null,
            'is_default' => true,
        ]);

        return TenantSubscription::query()->create([
            'billing_plan_version_id' => $version->id,
            'billing_price_id' => $price->id,
            'currency' => 'USD',
            'status' => 'active',
            'started_at' => now(),
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);
    }
}
