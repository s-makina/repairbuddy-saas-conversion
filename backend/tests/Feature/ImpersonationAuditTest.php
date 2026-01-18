<?php

namespace Tests\Feature;

use App\Models\ImpersonationSession;
use App\Models\PlatformAuditLog;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ImpersonationAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_impersonated_write_is_audited_with_impersonator_as_actor(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
            'status' => 'active',
        ]);

        $target = User::query()->forceCreate([
            'name' => 'Target User',
            'email' => 'target@example.com',
            'password' => Hash::make('password'),
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);

        $admin = User::query()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'is_admin' => true,
            'admin_role' => 'platform_admin',
            'email_verified_at' => now(),
        ]);

        $token = $admin->createToken('api')->plainTextToken;

        $session = ImpersonationSession::query()->create([
            'actor_user_id' => $admin->id,
            'tenant_id' => $tenant->id,
            'target_user_id' => $target->id,
            'reason' => 'support',
            'reference_id' => 'ticket-1',
            'started_at' => now(),
            'expires_at' => now()->addMinutes(30),
            'metadata' => ['duration_minutes' => 30],
            'ip' => '127.0.0.1',
            'user_agent' => 'phpunit',
        ]);

        $res = $this
            ->withHeaders([
                'Authorization' => 'Bearer '.$token,
                'X-RB-Impersonation' => (string) $session->id,
            ])
            ->postJson('/api/'.$tenant->slug.'/app/notes', [
                'title' => 'Note 1',
                'body' => 'Body',
            ]);

        $res->assertStatus(201);

        $log = PlatformAuditLog::query()
            ->where('action', 'impersonation.write')
            ->orderByDesc('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame($admin->id, $log->actor_user_id);
        $this->assertSame($tenant->id, $log->tenant_id);
        $this->assertIsArray($log->metadata);
        $this->assertSame($session->id, $log->metadata['impersonation_session_id'] ?? null);
        $this->assertSame($target->id, $log->metadata['target_user_id'] ?? null);
        $this->assertSame($admin->id, $log->metadata['impersonator_user_id'] ?? null);
    }
}
