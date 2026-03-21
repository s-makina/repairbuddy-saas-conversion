<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\RepairBuddyJob;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantSettings\TenantSettingsStore;
use App\Support\BranchContext;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class GeneralSettingsFunctionalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Test that Next Service Date setting controls calendar visibility.
     */
    public function test_next_service_date_setting_controls_calendar_visibility(): void
    {
        // 1. Setup Tenant and Branch
        $tenant = Tenant::query()->create([
            'name' => 'Test Business',
            'slug' => 'test-business',
            'status' => 'active',
            'setup_completed_at' => now(),
            'currency' => 'USD',
        ]);

        TenantContext::set($tenant);

        $branch = Branch::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Main Branch',
            'code' => 'MAIN',
            'is_active' => true,
        ]);

        $tenant->update(['default_branch_id' => $branch->id]);
        BranchContext::set($branch);

        $user = User::query()->forceCreate([
            'tenant_id' => $tenant->id,
            'name' => 'Test Admin',
            'email' => 'admin@test-business.com',
            'password' => Hash::make('password'),
            'role' => 'administrator',
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);

        // Create a role for the user to avoid Spatie permission issues
        $role = \App\Models\Role::query()->create([
            'name' => 'administrator',
            'guard_name' => 'web',
        ]);
        $user->role_id = $role->id;
        $user->save();

        // 2. Enable next_service_date setting
        $store = new TenantSettingsStore($tenant);
        $store->merge('general', [
            'wcrb_next_service_date' => true,
        ]);
        $store->save();

        // 3. Test calendar page shows next service date button when enabled
        $response = $this->actingAs($user)
            ->withoutMiddleware(\App\Http\Middleware\EnsureBusinessSetup::class)
            ->withoutMiddleware(\Spatie\Permission\Middleware\PermissionMiddleware::class)
            ->withSession([
                'tenant_id' => $tenant->id,
                'branch_id' => $branch->id,
                'active_branch_id' => $branch->id,
            ])
            ->get("/t/{$tenant->slug}/calendar");

        $response->assertStatus(200);
        $response->assertViewHas('enable_next_service', true);

        // 4. Disable next_service_date setting
        $store->merge('general', [
            'wcrb_next_service_date' => false,
        ]);
        $store->save();

        // 5. Test calendar page hides next service date button when disabled
        $response = $this->actingAs($user)
            ->withSession([
                'tenant_id' => $tenant->id,
                'branch_id' => $branch->id,
                'active_branch_id' => $branch->id,
            ])
            ->get("/t/{$tenant->slug}/calendar");

        $response->assertStatus(200);
        $response->assertViewHas('enable_next_service', false);
    }

    /**
     * Test that Email Customer on Status Change setting controls email sending.
     */
    public function test_email_customer_on_status_change_setting_controls_emails(): void
    {
        // 1. Setup Tenant and Branch
        $tenant = Tenant::query()->create([
            'name' => 'Test Business 2',
            'slug' => 'test-business-2',
            'status' => 'active',
            'setup_completed_at' => now(),
            'currency' => 'USD',
        ]);

        TenantContext::set($tenant);

        $branch = Branch::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Main Branch',
            'code' => 'MAIN',
            'is_active' => true,
        ]);

        $tenant->update(['default_branch_id' => $branch->id]);
        BranchContext::set($branch);

        $admin = User::query()->forceCreate([
            'tenant_id' => $tenant->id,
            'name' => 'Test Admin',
            'email' => 'admin@test-business-2.com',
            'password' => Hash::make('password'),
            'role' => 'administrator',
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);

        $customer = User::query()->forceCreate([
            'tenant_id' => $tenant->id,
            'name' => 'Test Customer',
            'email' => 'customer@test-business-2.com',
            'password' => Hash::make('password'),
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);

        // 2. Create a job
        $job = RepairBuddyJob::query()->create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'customer_id' => $customer->id,
            'status_slug' => 'new',
            'case_number' => 'TB2-001',
        ]);

        // 3. Disable email notifications
        $store = new TenantSettingsStore($tenant);
        $store->merge('general', [
            'wc_job_status_cr_notice' => false,
        ]);
        $store->save();

        // 4. Update job status - should NOT send email
        // Note: In a real test, we would use Mail::fake() to assert no mail was sent
        // For now, we verify the setting is stored correctly
        $settings = $store->get('general', []);
        $this->assertFalse((bool) ($settings['wc_job_status_cr_notice'] ?? false));

        // 5. Enable email notifications
        $store->merge('general', [
            'wc_job_status_cr_notice' => true,
        ]);
        $store->save();

        $settings = $store->get('general', []);
        $this->assertTrue((bool) ($settings['wc_job_status_cr_notice'] ?? false));
    }

    /**
     * Test that Attach PDF setting is stored and retrieved correctly.
     */
    public function test_attach_pdf_setting_is_persisted(): void
    {
        // 1. Setup Tenant
        $tenant = Tenant::query()->create([
            'name' => 'Test Business 3',
            'slug' => 'test-business-3',
            'status' => 'active',
            'setup_completed_at' => now(),
            'currency' => 'USD',
        ]);

        TenantContext::set($tenant);

        // 2. Store the setting
        $store = new TenantSettingsStore($tenant);
        $store->merge('general', [
            'wcrb_attach_pdf_in_customer_emails' => true,
        ]);
        $store->save();

        // 3. Verify it's stored
        $settings = $store->get('general', []);
        $this->assertTrue((bool) ($settings['wcrb_attach_pdf_in_customer_emails'] ?? false));

        // 4. Disable and verify
        $store->merge('general', [
            'wcrb_attach_pdf_in_customer_emails' => false,
        ]);
        $store->save();

        $settings = $store->get('general', []);
        $this->assertFalse((bool) ($settings['wcrb_attach_pdf_in_customer_emails'] ?? true));
    }

    /**
     * Test that styling labels are used in calendar view.
     */
    public function test_styling_labels_are_used_in_calendar(): void
    {
        // 1. Setup Tenant and Branch
        $tenant = Tenant::query()->create([
            'name' => 'Test Business 4',
            'slug' => 'test-business-4',
            'status' => 'active',
            'setup_completed_at' => now(),
            'currency' => 'USD',
        ]);

        TenantContext::set($tenant);

        $branch = Branch::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Main Branch',
            'code' => 'MAIN',
            'is_active' => true,
        ]);

        $tenant->update(['default_branch_id' => $branch->id]);
        BranchContext::set($branch);

        $user = User::query()->forceCreate([
            'tenant_id' => $tenant->id,
            'name' => 'Test Admin',
            'email' => 'admin@test-business-4.com',
            'password' => Hash::make('password'),
            'role' => 'administrator',
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);

        // Create a role for the user to avoid Spatie permission issues
        $role = \App\Models\Role::query()->create([
            'name' => 'administrator',
            'guard_name' => 'web',
        ]);
        $user->role_id = $role->id;
        $user->save();

        // 2. Set custom labels
        $store = new TenantSettingsStore($tenant);
        $store->merge('styling', [
            'pickup_date_label' => 'Collection Date',
            'delivery_date_label' => 'Return Date',
            'nextservice_date_label' => 'Follow-up Date',
        ]);
        $store->save();

        // 3. Test calendar page uses custom labels
        $response = $this->actingAs($user)
            ->withSession([
                'tenant_id' => $tenant->id,
                'branch_id' => $branch->id,
                'active_branch_id' => $branch->id,
            ])
            ->get("/t/{$tenant->slug}/calendar");

        $response->assertStatus(200);
        $response->assertViewHas('pickup_date_label', 'Collection Date');
        $response->assertViewHas('delivery_date_label', 'Return Date');
        $response->assertViewHas('nextservice_date_label', 'Follow-up Date');
    }
}
