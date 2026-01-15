<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminTenantsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_tenants(): void
    {
        Tenant::query()->create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
            'status' => 'active',
        ]);

        $admin = User::query()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'is_admin' => true,
        ]);

        $token = $admin->createToken('api')->plainTextToken;

        $response = $this
            ->withHeaders([
                'Authorization' => 'Bearer '.$token,
            ])
            ->getJson('/api/admin/tenants');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'tenants');
    }

    public function test_non_admin_cannot_list_tenants(): void
    {
        $user = User::query()->create([
            'name' => 'User',
            'email' => 'user@example.com',
            'password' => Hash::make('password'),
            'is_admin' => false,
            'role' => 'owner',
        ]);

        $token = $user->createToken('api')->plainTextToken;

        $response = $this
            ->withHeaders([
                'Authorization' => 'Bearer '.$token,
            ])
            ->getJson('/api/admin/tenants');

        $response->assertStatus(403);
    }
}
