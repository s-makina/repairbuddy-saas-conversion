<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\RepairBuddyJob;
use App\Models\RepairBuddyEstimate;
use App\Models\RepairBuddyPayment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TenantDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_displays_correct_metrics(): void
    {
        // 1. Setup Tenant and Branch
        $tenant = Tenant::query()->create([
            'name' => 'Test Business',
            'slug' => 'test-business',
            'status' => 'active',
            'setup_completed_at' => now(),
            'currency' => 'USD',
        ]);
        
        \App\Support\TenantContext::set($tenant);
        
        if (!\App\Support\TenantContext::tenantId()) {
            throw new \Exception("TenantContext NOT SET correctly in test. ID: " . $tenant->id);
        }

        $branch = Branch::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Main Branch',
            'is_active' => true,
        ]);

        $user = User::query()->forceCreate([
            'tenant_id' => $tenant->id,
            'name' => 'Test Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'administrator',
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);

        // 2. Create some data
        // Active Jobs (3) - Should be exactly 3
        for ($i = 0; $i < 3; $i++) {
            RepairBuddyJob::query()->create([
                'tenant_id' => $tenant->id,
                'branch_id' => $branch->id,
                'status_slug' => 'new',
                'title' => "Active Job $i",
            ]);
        }

        // Completed Jobs (2) - Should be exactly 2
        for ($i = 0; $i < 2; $i++) {
            RepairBuddyJob::query()->create([
                'tenant_id' => $tenant->id,
                'branch_id' => $branch->id,
                'status_slug' => 'completed',
                'title' => "Completed Job $i",
            ]);
        }

        // Pending Estimates (4) - Should be exactly 4
        for ($i = 0; $i < 4; $i++) {
            RepairBuddyEstimate::query()->create([
                'tenant_id' => $tenant->id,
                'branch_id' => $branch->id,
                'status' => 'pending',
                'title' => "Pending Estimate $i",
            ]);
        }

        // Revenue ($75.50) - Should be exactly 75.50
        RepairBuddyPayment::query()->create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'amount_cents' => 5000,
            'method' => 'cash',
        ]);
        RepairBuddyPayment::query()->create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'amount_cents' => 2550,
            'method' => 'card',
        ]);

        // 3. Act
        $response = $this->actingAs($user)
            ->withSession([
                'tenant_id' => $tenant->id,
                'branch_id' => $branch->id,
                'active_branch_id' => $branch->id
            ])
            ->get("/{$tenant->slug}");

        // 4. Assert
        $response->assertStatus(200);
        $response->assertViewHas('overviewStats', function ($stats) {
            return $stats['active_jobs_count'] === 3 &&
                   $stats['completed_jobs_count'] === 2 &&
                   $stats['pending_estimates_count'] === 4 &&
                   str_contains($stats['revenue_formatted'], '75.50');
        });
        
        $response->assertViewHas('latestJobs');
        $response->assertViewHas('latestEstimates');
        $response->assertViewHas('activities');
        $response->assertViewHas('priorityJobs');
    }
}
