<?php

namespace Tests\Feature;

use App\Models\BillingPlan;
use App\Models\BillingPlanVersion;
use App\Models\BillingPrice;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use App\Notifications\OneTimePasswordNotification;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RepairBuddyJobAdminCreateParityTest extends TestCase
{
    use RefreshDatabase;

    private function createTenantWithActiveSubscription(): Tenant
    {
        $tenant = Tenant::query()->create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
            'status' => 'active',
            'currency' => 'USD',
            'setup_completed_at' => now(),
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

        TenantContext::set($tenant);
        TenantSubscription::query()->create([
            'billing_plan_version_id' => $version->id,
            'billing_price_id' => $price->id,
            'currency' => 'USD',
            'status' => 'active',
            'started_at' => now(),
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);
        TenantContext::set(null);

        return $tenant;
    }

    private function createBranchForTenant(Tenant $tenant): int
    {
        TenantContext::set($tenant);
        $id = (int) DB::table('branches')->insertGetId([
            'tenant_id' => $tenant->id,
            'name' => 'Main',
            'code' => 'MAIN',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        TenantContext::set(null);

        return $id;
    }

    private function seedJobStatusesForBranch(int $tenantId, int $branchId): void
    {
        DB::table('rb_job_statuses')->insert([
            [
                'tenant_id' => $tenantId,
                'branch_id' => $branchId,
                'slug' => 'neworder',
                'label' => 'New Order',
                'email_enabled' => false,
                'email_template' => null,
                'sms_enabled' => false,
                'invoice_label' => 'Quote',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tenant_id' => $tenantId,
                'branch_id' => $branchId,
                'slug' => 'new',
                'label' => 'New',
                'email_enabled' => false,
                'email_template' => null,
                'sms_enabled' => false,
                'invoice_label' => 'Quote',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    private function authHeadersForUserWithBranch(User $user, int $branchId): array
    {
        $token = $user->createToken('api')->plainTextToken;

        DB::table('personal_access_tokens')
            ->where('tokenable_type', User::class)
            ->where('tokenable_id', $user->id)
            ->orderByDesc('id')
            ->limit(1)
            ->update([
                'active_branch_id' => $branchId,
                'last_used_at' => now(),
            ]);

        return [
            'Authorization' => 'Bearer '.$token,
        ];
    }

    public function test_admin_style_create_job_allows_minimal_payload_and_defaults_to_new(): void
    {
        $tenant = $this->createTenantWithActiveSubscription();
        $branchId = $this->createBranchForTenant($tenant);
        $this->seedJobStatusesForBranch($tenant->id, $branchId);

        $user = User::query()->forceCreate([
            'name' => 'Owner',
            'email' => 'owner@example.com',
            'password' => Hash::make('password'),
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);

        $headers = $this->authHeadersForUserWithBranch($user, $branchId);

        $res = $this
            ->withHeaders($headers)
            ->postJson('/api/'.$tenant->slug.'/app/repairbuddy/jobs', []);

        $res->assertStatus(201);
        $res->assertJsonPath('job.status', 'new');

        $jobId = $res->json('job.id');
        $this->assertIsInt($jobId);
        $this->assertDatabaseHas('rb_jobs', [
            'id' => $jobId,
            'tenant_id' => $tenant->id,
            'branch_id' => $branchId,
            'status_slug' => 'new',
        ]);
    }

    public function test_admin_style_create_job_can_inline_create_customer_and_sends_otp_email(): void
    {
        Notification::fake();

        $tenant = $this->createTenantWithActiveSubscription();
        $branchId = $this->createBranchForTenant($tenant);
        $this->seedJobStatusesForBranch($tenant->id, $branchId);

        $user = User::query()->forceCreate([
            'name' => 'Owner',
            'email' => 'owner2@example.com',
            'password' => Hash::make('password'),
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);

        $headers = $this->authHeadersForUserWithBranch($user, $branchId);

        $payload = [
            'customer_create' => [
                'name' => 'Customer A',
                'email' => 'customer-a@example.com',
                'phone' => '123',
            ],
        ];

        $res = $this
            ->withHeaders($headers)
            ->postJson('/api/'.$tenant->slug.'/app/repairbuddy/jobs', $payload);

        $res->assertStatus(201);

        $jobId = $res->json('job.id');
        $this->assertIsInt($jobId);

        $customerId = $res->json('job.customer_id');
        $this->assertIsInt($customerId);

        $this->assertDatabaseHas('users', [
            'id' => $customerId,
            'tenant_id' => $tenant->id,
            'email' => 'customer-a@example.com',
            'role' => 'customer',
            'is_admin' => 0,
        ]);

        Notification::assertSentTo(User::query()->findOrFail($customerId), OneTimePasswordNotification::class);
    }
}
