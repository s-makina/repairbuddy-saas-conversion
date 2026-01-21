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

class TenantBillingOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscribe_to_trial_price_creates_trial_subscription_and_gate_returns_trialing(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
            'status' => 'active',
            'currency' => 'USD',
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

        $trialPrice = BillingPrice::query()->create([
            'billing_plan_version_id' => $version->id,
            'currency' => 'USD',
            'interval' => 'month',
            'amount_cents' => 4900,
            'trial_days' => 14,
            'is_default' => true,
        ]);

        $token = $user->createToken('api')->plainTextToken;

        $res = $this
            ->withHeaders(['Authorization' => 'Bearer '.$token])
            ->postJson('/api/'.$tenant->slug.'/app/billing/subscribe', [
                'billing_price_id' => $trialPrice->id,
                'billing_country' => 'US',
                'currency' => 'USD',
                'billing_vat_number' => null,
            ]);

        $res->assertStatus(201);
        $res->assertJsonPath('subscription.status', 'trial');
        $res->assertJsonPath('gate.subscription_status', 'trialing');

        $this->assertDatabaseHas('tenant_subscriptions', [
            'tenant_id' => $tenant->id,
            'billing_price_id' => $trialPrice->id,
            'status' => 'trial',
        ]);
    }

    public function test_subscribe_to_paid_price_creates_pending_subscription_and_confirm_checkout_activates_it(): void
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

        $paidPrice = BillingPrice::query()->create([
            'billing_plan_version_id' => $version->id,
            'currency' => 'USD',
            'interval' => 'month',
            'amount_cents' => 9900,
            'trial_days' => 0,
            'is_default' => true,
        ]);

        $token = $user->createToken('api')->plainTextToken;

        $subRes = $this
            ->withHeaders(['Authorization' => 'Bearer '.$token])
            ->postJson('/api/'.$tenant->slug.'/app/billing/subscribe', [
                'billing_price_id' => $paidPrice->id,
                'billing_country' => 'US',
                'currency' => 'USD',
                'billing_vat_number' => null,
            ]);

        $subRes->assertStatus(201);
        $subRes->assertJsonPath('subscription.status', 'pending');
        $subRes->assertJsonPath('gate.subscription_status', 'pending_checkout');

        $pending = TenantSubscription::query()->where('status', 'pending')->first();
        $this->assertNotNull($pending);

        $confirmRes = $this
            ->withHeaders(['Authorization' => 'Bearer '.$token])
            ->postJson('/api/'.$tenant->slug.'/app/billing/checkout/confirm');

        $confirmRes->assertStatus(200);
        $confirmRes->assertJsonPath('subscription.status', 'active');
        $confirmRes->assertJsonPath('gate.subscription_status', 'active');

        $this->assertDatabaseHas('tenant_subscriptions', [
            'id' => $pending->id,
            'tenant_id' => $tenant->id,
            'status' => 'active',
        ]);
    }
}
