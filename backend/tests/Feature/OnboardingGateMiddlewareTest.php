<?php

namespace Tests\Feature;

use App\Models\BillingPlan;
use App\Models\BillingPlanVersion;
use App\Models\BillingPrice;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OnboardingGateMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_blocks_dashboard_when_no_subscription(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
            'status' => 'active',
        ]);

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
            ->withHeaders(['Authorization' => 'Bearer '.$token])
            ->getJson('/api/'.$tenant->slug.'/app/dashboard');

        $res->assertStatus(403);
        $res->assertJsonPath('code', 'subscription_required');
        $res->assertJsonPath('redirect_to', '/'.$tenant->slug.'/plans');
    }

    public function test_blocks_dashboard_when_pending_checkout(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
            'status' => 'active',
            'currency' => 'USD',
        ]);

        $user = User::query()->forceCreate([
            'name' => 'User A',
            'email' => 'user-a2@example.com',
            'password' => Hash::make('password'),
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);

        $plan = BillingPlan::query()->create([
            'code' => 'pro',
            'name' => 'Pro',
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
            'amount_cents' => 9900,
            'trial_days' => 0,
            'is_default' => true,
        ]);

        \App\Support\TenantContext::set($tenant);
        TenantSubscription::query()->create([
            'billing_plan_version_id' => $version->id,
            'billing_price_id' => $price->id,
            'currency' => 'USD',
            'status' => 'pending',
            'started_at' => now(),
        ]);
        \App\Support\TenantContext::set(null);

        $token = $user->createToken('api')->plainTextToken;

        $res = $this
            ->withHeaders(['Authorization' => 'Bearer '.$token])
            ->getJson('/api/'.$tenant->slug.'/app/dashboard');

        $res->assertStatus(403);
        $res->assertJsonPath('code', 'checkout_required');
        $res->assertJsonPath('redirect_to', '/'.$tenant->slug.'/checkout');
    }

    public function test_blocks_dashboard_when_setup_incomplete_but_subscription_active(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
            'status' => 'active',
            'currency' => 'USD',
            'setup_completed_at' => null,
        ]);

        $user = User::query()->forceCreate([
            'name' => 'User A',
            'email' => 'user-a3@example.com',
            'password' => Hash::make('password'),
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);

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
            'trial_days' => 0,
            'is_default' => true,
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

        $token = $user->createToken('api')->plainTextToken;

        $res = $this
            ->withHeaders(['Authorization' => 'Bearer '.$token])
            ->getJson('/api/'.$tenant->slug.'/app/dashboard');

        $res->assertStatus(403);
        $res->assertJsonPath('code', 'setup_required');
        $res->assertJsonPath('redirect_to', '/'.$tenant->slug.'/setup');
    }

    public function test_allows_dashboard_when_subscription_active_and_setup_complete(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
            'status' => 'active',
            'currency' => 'USD',
            'setup_completed_at' => now(),
        ]);

        $user = User::query()->forceCreate([
            'name' => 'User A',
            'email' => 'user-a4@example.com',
            'password' => Hash::make('password'),
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);

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
            'trial_days' => 0,
            'is_default' => true,
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

        $token = $user->createToken('api')->plainTextToken;

        $res = $this
            ->withHeaders(['Authorization' => 'Bearer '.$token])
            ->getJson('/api/'.$tenant->slug.'/app/dashboard');

        $res->assertStatus(200);
    }
}
