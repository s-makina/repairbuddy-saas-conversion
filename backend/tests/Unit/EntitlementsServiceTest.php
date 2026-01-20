<?php

namespace Tests\Unit;

use App\Models\BillingPlan;
use App\Models\BillingPlanVersion;
use App\Models\BillingPrice;
use App\Models\EntitlementDefinition;
use App\Models\PlanEntitlement;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Support\EntitlementsService;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntitlementsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolves_entitlements_from_subscription_plan_version_and_applies_overrides(): void
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
            'currency' => 'USD',
            'interval' => 'month',
            'amount_cents' => 4900,
            'trial_days' => null,
            'is_default' => true,
        ]);

        $defMaxUsers = EntitlementDefinition::query()->create([
            'code' => 'max_users',
            'name' => 'Maximum users',
            'value_type' => 'integer',
        ]);

        $defInvoices = EntitlementDefinition::query()->create([
            'code' => 'feature_invoices',
            'name' => 'feature_invoices',
            'value_type' => 'boolean',
        ]);

        PlanEntitlement::query()->create([
            'billing_plan_version_id' => $version->id,
            'entitlement_definition_id' => $defMaxUsers->id,
            'value_json' => 5,
        ]);

        PlanEntitlement::query()->create([
            'billing_plan_version_id' => $version->id,
            'entitlement_definition_id' => $defInvoices->id,
            'value_json' => true,
        ]);

        TenantSubscription::query()->create([
            'billing_plan_version_id' => $version->id,
            'billing_price_id' => $price->id,
            'currency' => 'USD',
            'status' => 'active',
            'started_at' => now(),
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        $tenant->forceFill([
            'entitlement_overrides' => [
                'limits' => [
                    'max_users' => 7,
                ],
            ],
        ])->save();

        $svc = new EntitlementsService();

        $resolved = $svc->resolveForTenant($tenant, false);

        $this->assertSame(7, $resolved['limits']['max_users']);
        $this->assertTrue($resolved['flags']['feature_invoices']);
        $this->assertSame(7, $svc->maxUsersForTenant($tenant));

        TenantContext::set(null);
    }
}
