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

class RepairBuddyJobAggregateUpdateDevicesParityTest extends TestCase
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

    public function test_job_aggregate_update_replaces_job_devices_and_persists_overrides(): void
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

        $customerId = (int) DB::table('users')->insertGetId([
            'tenant_id' => $tenant->id,
            'name' => 'Customer A',
            'email' => 'customer-a@example.com',
            'password' => Hash::make('password'),
            'role' => 'customer',
            'is_admin' => 0,
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

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

        $deviceId = (int) DB::table('rb_devices')->insertGetId([
            'tenant_id' => $tenant->id,
            'branch_id' => $branchId,
            'model' => 'iPhone X',
            'device_type_id' => $deviceTypeId,
            'device_brand_id' => $deviceBrandId,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $customerDeviceId = (int) DB::table('rb_customer_devices')->insertGetId([
            'tenant_id' => $tenant->id,
            'branch_id' => $branchId,
            'customer_id' => $customerId,
            'device_id' => $deviceId,
            'label' => 'iPhone X',
            'serial' => 'CUSTSERIAL',
            'pin' => null,
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $createRes = $this
            ->withHeaders($headers)
            ->postJson('/api/'.$tenant->slug.'/app/repairbuddy/jobs', [
                'customer_id' => $customerId,
                'job_devices' => [
                    [
                        'customer_device_id' => $customerDeviceId,
                        'serial' => 'SERIAL-A',
                        'pin' => '1111',
                        'notes' => 'note-a',
                    ],
                ],
            ]);

        $createRes->assertStatus(201);

        $jobId = $createRes->json('job.id');
        $this->assertIsInt($jobId);

        $this->assertDatabaseHas('rb_job_devices', [
            'job_id' => $jobId,
            'tenant_id' => $tenant->id,
            'branch_id' => $branchId,
            'customer_device_id' => $customerDeviceId,
            'serial_snapshot' => 'SERIAL-A',
            'pin_snapshot' => '1111',
        ]);

        $updateRes = $this
            ->withHeaders($headers)
            ->putJson('/api/'.$tenant->slug.'/app/repairbuddy/jobs/'.$jobId.'/aggregate', [
                'job_devices' => [
                    [
                        'customer_device_id' => $customerDeviceId,
                        'serial' => 'SERIAL-B',
                        'pin' => '2222',
                        'notes' => 'note-b',
                        'extra_fields' => [
                            ['key' => 'imei', 'label' => 'IMEI', 'value_text' => '123'],
                        ],
                    ],
                ],
            ]);

        $updateRes->assertStatus(200);

        $this->assertDatabaseMissing('rb_job_devices', [
            'job_id' => $jobId,
            'tenant_id' => $tenant->id,
            'branch_id' => $branchId,
            'customer_device_id' => $customerDeviceId,
            'serial_snapshot' => 'SERIAL-A',
        ]);

        $this->assertDatabaseHas('rb_job_devices', [
            'job_id' => $jobId,
            'tenant_id' => $tenant->id,
            'branch_id' => $branchId,
            'customer_device_id' => $customerDeviceId,
            'serial_snapshot' => 'SERIAL-B',
            'pin_snapshot' => '2222',
            'notes_snapshot' => 'note-b',
        ]);

        $count = DB::table('rb_job_devices')
            ->where('tenant_id', $tenant->id)
            ->where('branch_id', $branchId)
            ->where('job_id', $jobId)
            ->count();

        $this->assertSame(1, (int) $count);
    }
}
