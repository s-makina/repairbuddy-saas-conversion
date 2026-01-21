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

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_member_cannot_access_other_tenant_data(): void
    {
        $unauthRoles = $this->getJson('/api/tenant-a/app/roles');
        $unauthRoles->assertStatus(401);

        $unauthUsers = $this->getJson('/api/tenant-a/app/users');
        $unauthUsers->assertStatus(401);

        $unauthPermissions = $this->getJson('/api/tenant-a/app/permissions');
        $unauthPermissions->assertStatus(401);

        $tenantA = Tenant::query()->create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
            'status' => 'active',
            'currency' => 'USD',
            'setup_completed_at' => now(),
        ]);

        $tenantB = Tenant::query()->create([
            'name' => 'Tenant B',
            'slug' => 'tenant-b',
            'status' => 'active',
        ]);

        $userA = User::query()->forceCreate([
            'name' => 'User A',
            'email' => 'user-a@example.com',
            'password' => Hash::make('password'),
            'tenant_id' => $tenantA->id,
            'role' => 'member',
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);

        $tokenA = $userA->createToken('api')->plainTextToken;

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

        \App\Support\TenantContext::set($tenantA);

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

        $rolesForbidden = $this
            ->withHeaders([
                'Authorization' => 'Bearer '.$tokenA,
            ])
            ->getJson('/api/'.$tenantA->slug.'/app/roles');

        $rolesForbidden->assertStatus(403);

        $usersForbidden = $this
            ->withHeaders([
                'Authorization' => 'Bearer '.$tokenA,
            ])
            ->getJson('/api/'.$tenantA->slug.'/app/users');

        $usersForbidden->assertStatus(403);

        $permissionsForbidden = $this
            ->withHeaders([
                'Authorization' => 'Bearer '.$tokenA,
            ])
            ->getJson('/api/'.$tenantA->slug.'/app/permissions');

        $permissionsForbidden->assertStatus(403);

        $createA = $this
            ->withHeaders([
                'Authorization' => 'Bearer '.$tokenA,
            ])
            ->postJson('/api/'.$tenantA->slug.'/app/notes', [
                'title' => 'Note A',
                'body' => 'Hello',
            ]);

        $createA->assertStatus(201);

        $listWrongTenant = $this
            ->withHeaders([
                'Authorization' => 'Bearer '.$tokenA,
            ])
            ->getJson('/api/'.$tenantB->slug.'/app/notes');

        $listWrongTenant->assertStatus(403);

        $listA = $this
            ->withHeaders([
                'Authorization' => 'Bearer '.$tokenA,
            ])
            ->getJson('/api/'.$tenantA->slug.'/app/notes');

        $listA->assertStatus(200);
        $listA->assertJsonCount(1, 'notes');
    }
}
