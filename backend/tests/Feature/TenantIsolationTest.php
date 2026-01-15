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

        $userA = User::query()->create([
            'name' => 'User A',
            'email' => 'user-a@example.com',
            'password' => Hash::make('password'),
            'tenant_id' => $tenantA->id,
            'role' => 'member',
            'is_admin' => false,
        ]);

        $tokenA = $userA->createToken('api')->plainTextToken;

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
