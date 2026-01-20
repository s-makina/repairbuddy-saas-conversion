<?php

namespace Tests\Unit;

use App\Models\BillingPlan;
use App\Models\BillingPlanVersion;
use App\Models\BillingPrice;
use App\Models\TaxProfile;
use App\Models\TaxRate;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Support\Billing\InvoicingService;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoicingServiceVatTest extends TestCase
{
    use RefreshDatabase;

    public function test_same_country_vat_applied_when_rate_exists(): void
    {
        config(['billing.seller_country' => 'EG']);

        $tenant = Tenant::query()->create([
            'name' => 'T',
            'slug' => 't',
            'status' => 'active',
            'contact_email' => 't@example.com',
            'currency' => 'USD',
            'billing_country' => 'EG',
        ]);

        TenantContext::set($tenant);

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

        $subscription = TenantSubscription::query()->create([
            'billing_plan_version_id' => $version->id,
            'billing_price_id' => $price->id,
            'currency' => 'USD',
            'status' => 'active',
            'started_at' => now(),
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        $profile = TaxProfile::query()->create([
            'country_code' => 'EG',
            'name' => 'VAT',
            'is_vat' => true,
        ]);

        TaxRate::query()->create([
            'tax_profile_id' => $profile->id,
            'rate_percent' => 14.00,
            'is_active' => true,
        ]);

        $svc = new InvoicingService();
        $invoice = $svc->createDraftFromSubscription($tenant, $subscription);

        $this->assertSame('draft', $invoice->status);
        $this->assertSame('same_country_vat', $invoice->tax_details_json['scenario']);
        $this->assertSame(14.0, (float) $invoice->tax_details_json['rate_percent']);
        $this->assertSame(10000, $invoice->subtotal_cents);
        $this->assertSame(1400, $invoice->tax_cents);
        $this->assertSame(11400, $invoice->total_cents);

        TenantContext::set(null);
    }

    public function test_reverse_charge_applies_zero_vat_when_vat_number_present_and_countries_differ(): void
    {
        config(['billing.seller_country' => 'EG']);

        $tenant = Tenant::query()->create([
            'name' => 'T',
            'slug' => 't',
            'status' => 'active',
            'contact_email' => 't@example.com',
            'currency' => 'USD',
            'billing_country' => 'FR',
            'billing_vat_number' => 'FR123456789',
        ]);

        TenantContext::set($tenant);

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

        $subscription = TenantSubscription::query()->create([
            'billing_plan_version_id' => $version->id,
            'billing_price_id' => $price->id,
            'currency' => 'USD',
            'status' => 'active',
            'started_at' => now(),
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        $svc = new InvoicingService();
        $invoice = $svc->createDraftFromSubscription($tenant, $subscription);

        $this->assertSame('reverse_charge', $invoice->tax_details_json['scenario']);
        $this->assertSame(0.0, (float) $invoice->tax_details_json['rate_percent']);
        $this->assertSame(0, $invoice->tax_cents);
        $this->assertSame(10000, $invoice->total_cents);

        TenantContext::set(null);
    }

    public function test_non_vat_when_no_rate_configured(): void
    {
        config(['billing.seller_country' => 'EG']);

        $tenant = Tenant::query()->create([
            'name' => 'T',
            'slug' => 't',
            'status' => 'active',
            'contact_email' => 't@example.com',
            'currency' => 'USD',
            'billing_country' => 'EG',
        ]);

        TenantContext::set($tenant);

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

        $subscription = TenantSubscription::query()->create([
            'billing_plan_version_id' => $version->id,
            'billing_price_id' => $price->id,
            'currency' => 'USD',
            'status' => 'active',
            'started_at' => now(),
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        $svc = new InvoicingService();
        $invoice = $svc->createDraftFromSubscription($tenant, $subscription);

        $this->assertSame('non_vat', $invoice->tax_details_json['scenario']);
        $this->assertSame(0.0, (float) $invoice->tax_details_json['rate_percent']);
        $this->assertSame(0, $invoice->tax_cents);

        TenantContext::set(null);
    }
}
