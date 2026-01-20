<?php

namespace Tests\Unit;

use App\Models\BillingPlan;
use App\Models\BillingPlanVersion;
use App\Models\BillingPrice;
use App\Models\Tenant;
use App\Support\Billing\CurrencyMismatchException;
use App\Support\SubscriptionService;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_throws_on_currency_mismatch(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'T',
            'slug' => 't',
            'status' => 'active',
            'contact_email' => 't@example.com',
            'currency' => 'USD',
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
            'currency' => 'EUR',
            'interval' => 'month',
            'amount_cents' => 4900,
            'trial_days' => null,
            'is_default' => true,
        ]);

        $svc = new SubscriptionService();

        $this->expectException(CurrencyMismatchException::class);
        $svc->createOrChangeSubscription($tenant, $version, $price);

        TenantContext::set(null);
    }
}
