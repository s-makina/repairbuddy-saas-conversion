<?php

namespace Tests\Feature;

use App\Models\Tenant;
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
