<?php

namespace App\Observers;

use App\Models\Tenant;
use App\Services\TenantBootstrap\EnsureDefaultRepairBuddyStatuses;
use App\Support\BranchContext;
use App\Support\TenantContext;

class TenantObserver
{
    /**
     * Handle the Tenant "created" event.
     */
    public function created(Tenant $tenant): void
    {
        // Set tenant context for seeding
        TenantContext::set($tenant);

        // Set branch context if default branch exists
        $branch = $tenant->defaultBranch;
        if ($branch) {
            BranchContext::set($branch);
        }

        try {
            // Seed default job and payment statuses
            $seeder = new EnsureDefaultRepairBuddyStatuses();
            $seeder->ensure((int) $tenant->id);
        } finally {
            // Clear context after seeding
            BranchContext::set(null);
            TenantContext::set(null);
        }
    }
}
