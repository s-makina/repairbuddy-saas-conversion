<?php

namespace Tests\Feature;

use App\Models\BillingPlan;
use App\Models\BillingPlanVersion;
use App\Models\BillingPrice;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RepairBuddyJobMultiDeviceAdminParityTest extends TestCase
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

    public function test_admin_style_create_job_can_attach_multiple_catalog_devices(): void
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

        $deviceTypeId = (int) DB::table('rb_device_types')->insertGetId([
            'tenant_id' => $tenant->id,
            'branch_id' => $branchId,
            'name' => 'Phone',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $deviceBrandId = (int) DB::table('rb_device_brands')->insertGetId([
            'tenant_id' => $tenant->id,
            'branch_id' => $branchId,
            'name' => 'Brand',
            'image_path' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $device1Id = (int) DB::table('rb_devices')->insertGetId([
            'tenant_id' => $tenant->id,
            'branch_id' => $branchId,
            'model' => 'iPhone X',
            'device_type_id' => $deviceTypeId,
            'device_brand_id' => $deviceBrandId,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $device2Id = (int) DB::table('rb_devices')->insertGetId([
            'tenant_id' => $tenant->id,
            'branch_id' => $branchId,
            'model' => 'Samsung S10',
            'device_type_id' => $deviceTypeId,
            'device_brand_id' => $deviceBrandId,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $payload = [
            'customer_create' => [
                'name' => 'Customer A',
                'email' => 'customer-a@example.com',
            ],
            'devices' => [
                ['device_id' => $device1Id, 'serial' => 'SERIAL1'],
                ['device_id' => $device2Id, 'serial' => 'SERIAL2', 'pin' => '1234', 'notes' => 'note'],
            ],
        ];

        $res = $this
            ->withHeaders($headers)
            ->postJson('/api/'.$tenant->slug.'/app/repairbuddy/jobs', $payload);

        $res->assertStatus(201);

        $jobId = $res->json('job.id');
        $this->assertIsInt($jobId);

        $this->assertDatabaseHas('rb_job_devices', [
            'job_id' => $jobId,
            'tenant_id' => $tenant->id,
            'branch_id' => $branchId,
            'serial_snapshot' => 'SERIAL1',
        ]);

        $this->assertDatabaseHas('rb_job_devices', [
            'job_id' => $jobId,
            'tenant_id' => $tenant->id,
            'branch_id' => $branchId,
            'serial_snapshot' => 'SERIAL2',
            'pin_snapshot' => '1234',
        ]);

        $this->assertDatabaseHas('rb_jobs', [
            'id' => $jobId,
            'plugin_device_post_id' => $device1Id,
            'plugin_device_id_text' => 'SERIAL1',
        ]);
    }
}
