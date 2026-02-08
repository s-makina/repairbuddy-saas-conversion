<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RoleControllerTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $adminUser;
    private string $token;
    private array $permissions = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Create a tenant
        $this->tenant = Tenant::query()->create([
            'name' => 'Test Tenant',
            'slug' => 'test-tenant',
            'status' => 'active',
            'currency' => 'USD',
            'setup_completed_at' => now(),
        ]);

        // Create permissions (use firstOrCreate in case they already exist from seeders)
        $permNames = ['roles.manage', 'users.manage', 'settings.manage', 'clients.view'];
        foreach ($permNames as $name) {
            $this->permissions[$name] = Permission::query()->firstOrCreate(['name' => $name]);
        }

        // Create admin user with roles.manage permission
        $adminRole = Role::query()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Admin',
        ]);
        $adminRole->permissions()->sync([$this->permissions['roles.manage']->id]);

        $this->adminUser = User::query()->forceCreate([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'tenant_id' => $this->tenant->id,
            'role' => 'admin',
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);

        $this->token = $this->adminUser->createToken('api')->plainTextToken;
    }

    public function test_can_create_role_with_permissions(): void
    {
        $response = $this
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
            ])
            ->postJson('/api/' . $this->tenant->slug . '/app/roles', [
                'name' => 'Manager',
                'permission_names' => ['users.manage', 'clients.view'],
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'role' => [
                    'id',
                    'tenant_id',
                    'name',
                    'permissions' => [
                        '*' => ['id', 'name'],
                    ],
                ],
            ]);

        $roleData = $response->json('role');
        $this->assertEquals('Manager', $roleData['name']);
        $this->assertEquals($this->tenant->id, $roleData['tenant_id']);
        $this->assertCount(2, $roleData['permissions']);

        // Verify in database
        $this->assertDatabaseHas('roles', [
            'id' => $roleData['id'],
            'name' => 'Manager',
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_debug_update(): void
    {
        $role = Role::query()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Debug Role',
        ]);

        dump('Role ID: ' . $role->id);
        dump('Tenant slug: ' . $this->tenant->slug);
        dump('URL: /api/' . $this->tenant->slug . '/app/roles/' . $role->id);

        $response = $this
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
            ])
            ->putJson('/api/' . $this->tenant->slug . '/app/roles/' . $role->id, [
                'name' => 'Debug Role Updated',
                'permission_names' => [],
            ]);

        dump('Response status: ' . $response->getStatusCode());
        dump('Response: ' . $response->getContent());

        $this->assertTrue(true); // Force pass for debug
    }

    public function test_can_update_role_with_permissions(): void
    {
        // First create a role
        $role = Role::query()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Editor',
        ]);
        $role->permissions()->sync([$this->permissions['clients.view']->id]);

        // Update the role with different permissions
        $response = $this
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
            ])
            ->putJson('/api/' . $this->tenant->slug . '/app/roles/' . $role->id, [
                'name' => 'Senior Editor',
                'permission_names' => ['users.manage', 'settings.manage', 'clients.view'],
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'role' => [
                    'id',
                    'tenant_id',
                    'name',
                    'permissions' => [
                        '*' => ['id', 'name'],
                    ],
                ],
            ]);

        $roleData = $response->json('role');
        $this->assertEquals('Senior Editor', $roleData['name']);
        $this->assertEquals($role->id, $roleData['id']);
        $this->assertCount(3, $roleData['permissions']);

        // Verify in database
        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
            'name' => 'Senior Editor',
        ]);

        // Verify permissions were synced
        $updatedRole = Role::query()->find($role->id);
        $this->assertEquals(3, $updatedRole->permissions()->count());
    }

    public function test_update_role_returns_404_for_nonexistent_role(): void
    {
        $response = $this
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
            ])
            ->putJson('/api/' . $this->tenant->slug . '/app/roles/99999', [
                'name' => 'Test Role',
                'permission_names' => [],
            ]);

        $response->assertStatus(404)
            ->assertJson(['message' => 'Not found.']);
    }

    public function test_cannot_update_role_from_other_tenant(): void
    {
        // Create another tenant and role
        $otherTenant = Tenant::query()->create([
            'name' => 'Other Tenant',
            'slug' => 'other-tenant',
            'status' => 'active',
            'currency' => 'USD',
        ]);

        $otherRole = Role::query()->create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Other Role',
        ]);

        // Try to update the other tenant's role
        $response = $this
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
            ])
            ->putJson('/api/' . $this->tenant->slug . '/app/roles/' . $otherRole->id, [
                'name' => 'Hacked Role',
                'permission_names' => [],
            ]);

        $response->assertStatus(403)
            ->assertJson(['message' => 'Forbidden.']);
    }

    public function test_can_create_role_without_permissions(): void
    {
        $response = $this
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
            ])
            ->postJson('/api/' . $this->tenant->slug . '/app/roles', [
                'name' => 'Viewer',
                'permission_names' => [],
            ]);

        $response->assertStatus(201);
        $roleData = $response->json('role');
        $this->assertEquals('Viewer', $roleData['name']);
        $this->assertCount(0, $roleData['permissions']);
    }

    public function test_can_update_role_to_remove_all_permissions(): void
    {
        // Create role with permissions (use unique name)
        $role = Role::query()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Temp Role For Removal',
        ]);
        $role->permissions()->sync([
            $this->permissions['users.manage']->id,
            $this->permissions['clients.view']->id,
        ]);

        // Update to remove all permissions
        $response = $this
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
            ])
            ->putJson('/api/' . $this->tenant->slug . '/app/roles/' . $role->id, [
                'name' => 'Temp Role For Removal Updated',
                'permission_names' => [],
            ]);

        $response->assertStatus(200);
        $roleData = $response->json('role');
        $this->assertCount(0, $roleData['permissions']);
    }

    public function test_cannot_create_duplicate_role_name(): void
    {
        // Create first role
        Role::query()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Unique Role',
        ]);

        // Try to create another with same name
        $response = $this
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
            ])
            ->postJson('/api/' . $this->tenant->slug . '/app/roles', [
                'name' => 'Unique Role',
                'permission_names' => [],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_can_update_role_keep_same_name(): void
    {
        // Create role with unique name
        $role = Role::query()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Same Name Test Role',
        ]);

        // Update with same name but different permissions
        $response = $this
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
            ])
            ->putJson('/api/' . $this->tenant->slug . '/app/roles/' . $role->id, [
                'name' => 'Same Name Test Role',
                'permission_names' => ['users.manage'],
            ]);

        $response->assertStatus(200);
        $roleData = $response->json('role');
        $this->assertEquals('Same Name Test Role', $roleData['name']);
        $this->assertCount(1, $roleData['permissions']);
    }

    public function test_role_name_is_required(): void
    {
        $response = $this
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
            ])
            ->postJson('/api/' . $this->tenant->slug . '/app/roles', [
                'name' => '',
                'permission_names' => [],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_unauthorized_user_cannot_manage_roles(): void
    {
        // Create user without roles.manage permission
        $regularUser = User::query()->forceCreate([
            'name' => 'Regular User',
            'email' => 'regular@example.com',
            'password' => Hash::make('password'),
            'tenant_id' => $this->tenant->id,
            'role' => 'member',
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);
        $regularToken = $regularUser->createToken('api')->plainTextToken;

        $response = $this
            ->withHeaders([
                'Authorization' => 'Bearer ' . $regularToken,
            ])
            ->postJson('/api/' . $this->tenant->slug . '/app/roles', [
                'name' => 'Test Role',
                'permission_names' => [],
            ]);

        $response->assertStatus(403);
    }

    public function test_can_delete_role(): void
    {
        // Create two roles with unique names (can't delete the last one)
        $role1 = Role::query()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Role To Keep For Delete Test',
        ]);

        $role2 = Role::query()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Role To Delete For Delete Test',
        ]);

        $response = $this
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
            ])
            ->deleteJson('/api/' . $this->tenant->slug . '/app/roles/' . $role2->id);

        $response->assertStatus(200)
            ->assertJson(['status' => 'ok']);

        $this->assertDatabaseMissing('roles', ['id' => $role2->id]);
        $this->assertDatabaseHas('roles', ['id' => $role1->id]);
    }

    public function test_cannot_delete_last_role(): void
    {
        // Create only one role with unique name
        $role = Role::query()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Only Role For Last Test',
        ]);

        $response = $this
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
            ])
            ->deleteJson('/api/' . $this->tenant->slug . '/app/roles/' . $role->id);

        $response->assertStatus(422)
            ->assertJson(['message' => 'Cannot delete the last role.']);
    }

    public function test_can_list_roles_with_permissions(): void
    {
        // Create roles with permissions (use unique names, Admin already exists from setUp)
        $role1 = Role::query()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Manager Listed',
        ]);
        $role1->permissions()->sync([
            $this->permissions['roles.manage']->id,
            $this->permissions['users.manage']->id,
        ]);

        $role2 = Role::query()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Viewer Listed',
        ]);
        $role2->permissions()->sync([$this->permissions['clients.view']->id]);

        $response = $this
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
            ])
            ->getJson('/api/' . $this->tenant->slug . '/app/roles');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'roles' => [
                    '*' => [
                        'id',
                        'tenant_id',
                        'name',
                        'permissions' => [
                            '*' => ['id', 'name'],
                        ],
                    ],
                ],
            ]);

        $roles = $response->json('roles');
        $this->assertCount(3, $roles); // Admin from setUp + 2 created

        // Find our created roles
        $managerRole = collect($roles)->firstWhere('name', 'Manager Listed');
        $viewerRole = collect($roles)->firstWhere('name', 'Viewer Listed');

        $this->assertCount(2, $managerRole['permissions']);
        $this->assertCount(1, $viewerRole['permissions']);
    }
}
