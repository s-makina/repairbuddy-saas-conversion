<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PlansEntitlementsTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_entitlements_endpoint_merges_plan_and_overrides(): void
    {
        $plan = Plan::query()->create([
            'name' => 'Starter',
            'code' => 'starter',
            'entitlements' => [
                'flags' => [
                    'feature_a' => true,
                    'feature_b' => false,
                ],
                'limits' => [
                    'users' => 3,
                ],
            ],
        ]);

        $tenant = Tenant::query()->create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
            'status' => 'active',
            'plan_id' => $plan->id,
            'entitlement_overrides' => [
                'flags' => [
                    'feature_b' => true,
                ],
                'limits' => [
                    'users' => 5,
                ],
            ],
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
            ->withHeaders([
                'Authorization' => 'Bearer '.$token,
            ])
            ->getJson('/api/'.$tenant->slug.'/app/entitlements');

        $res->assertStatus(200);
        $res->assertJsonPath('entitlements.flags.feature_a', true);
        $res->assertJsonPath('entitlements.flags.feature_b', true);
        $res->assertJsonPath('entitlements.limits.users', 5);
    }

    public function test_admin_can_assign_plan_to_tenant(): void
    {
        $plan = Plan::query()->create([
            'name' => 'Starter',
            'code' => 'starter',
            'entitlements' => ['flags' => ['feature_a' => true]],
        ]);

        $tenant = Tenant::query()->create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
            'status' => 'active',
        ]);

        $admin = User::query()->create([
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
                'plan_id' => $plan->id,
                'reason' => 'upgrade',
            ]);

        $res->assertStatus(200);
        $res->assertJsonPath('tenant.plan_id', $plan->id);
    }
}
