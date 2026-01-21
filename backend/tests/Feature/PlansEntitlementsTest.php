<?php

namespace Tests\Feature;

use App\Models\BillingPlan;
use App\Models\BillingPlanVersion;
use App\Models\BillingPrice;
use App\Models\EntitlementDefinition;
use App\Models\PlanEntitlement;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PlansEntitlementsTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_entitlements_endpoint_merges_plan_and_overrides(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
            'status' => 'active',
            'currency' => 'USD',
            'setup_completed_at' => now(),
            'entitlement_overrides' => [
                'flags' => [
                    'feature_b' => true,
                ],
                'limits' => [
                    'max_users' => 5,
                ],
            ],
        ]);

        $billingPlan = BillingPlan::query()->create([
            'code' => 'starter',
            'name' => 'Starter',
            'is_active' => true,
        ]);

        $version = BillingPlanVersion::query()->create([
            'billing_plan_id' => $billingPlan->id,
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

        $defFlagA = EntitlementDefinition::query()->create([
            'code' => 'feature_a',
            'name' => 'feature_a',
            'value_type' => 'boolean',
        ]);

        $defFlagB = EntitlementDefinition::query()->create([
            'code' => 'feature_b',
            'name' => 'feature_b',
            'value_type' => 'boolean',
        ]);

        $defMaxUsers = EntitlementDefinition::query()->create([
            'code' => 'max_users',
            'name' => 'Maximum users',
            'value_type' => 'integer',
        ]);

        PlanEntitlement::query()->create([
            'billing_plan_version_id' => $version->id,
            'entitlement_definition_id' => $defFlagA->id,
            'value_json' => true,
        ]);

        PlanEntitlement::query()->create([
            'billing_plan_version_id' => $version->id,
            'entitlement_definition_id' => $defFlagB->id,
            'value_json' => false,
        ]);

        PlanEntitlement::query()->create([
            'billing_plan_version_id' => $version->id,
            'entitlement_definition_id' => $defMaxUsers->id,
            'value_json' => 3,
        ]);

        \App\Support\TenantContext::set($tenant);

        TenantSubscription::query()->create([
            'billing_plan_version_id' => $version->id,
            'billing_price_id' => $price->id,
            'currency' => 'USD',
            'status' => 'active',
            'started_at' => now(),
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        \App\Support\TenantContext::set(null);

        $user = User::query()->forceCreate([
            'name' => 'User A',
            'email' => 'user-a@example.com',
            'password' => Hash::make('password'),
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);

        $token = $user->createToken('api')->plainTextToken;

        $res = $this
            ->withHeaders([
                'Authorization' => 'Bearer '.$token,
            ])
            ->getJson('/api/'.$tenant->slug.'/app/entitlements');

        $res->assertStatus(200);
        $res->assertJsonPath('entitlements.flags.feature_a', true);
        $res->assertJsonPath('entitlements.flags.feature_b', true);
        $res->assertJsonPath('entitlements.limits.max_users', 5);
    }

    public function test_admin_can_assign_plan_to_tenant(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
            'status' => 'active',
        ]);

        $admin = User::query()->forceCreate([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);

        $token = $admin->createToken('api')->plainTextToken;

        $res = $this
            ->withHeaders([
                'Authorization' => 'Bearer '.$token,
            ])
            ->putJson('/api/admin/tenants/'.$tenant->id.'/plan', [
                'plan_id' => null,
                'reason' => 'upgrade',
            ]);

        $res->assertStatus(200);
        $res->assertJsonPath('tenant.plan_id', null);
    }
}
