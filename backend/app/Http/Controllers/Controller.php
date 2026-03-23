<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Tenant;
use App\Support\BranchContext;
use App\Support\TenantContext;

abstract class Controller
{
    protected function tenant(): Tenant
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            throw new \RuntimeException('Tenant context is missing.');
        }

        return $tenant;
    }

    protected function tenantId(): int
    {
        $tenantId = TenantContext::tenantId();

        if (! is_int($tenantId) || $tenantId <= 0) {
            throw new \RuntimeException('Tenant context is missing.');
        }

        return $tenantId;
    }

    protected function branch(): Branch
    {
        $branch = BranchContext::branch();

        if (! $branch instanceof Branch) {
            // Try to resolve or create a branch
            $branch = $this->resolveOrCreateBranch();
            BranchContext::set($branch);
        }

        return $branch;
    }

    protected function branchId(): int
    {
        $branchId = BranchContext::branchId();

        if (! is_int($branchId) || $branchId <= 0) {
            // Try to resolve or create a branch
            $branch = $this->resolveOrCreateBranch();
            BranchContext::set($branch);
            $branchId = $branch->id;
        }

        return $branchId;
    }

    /**
     * Resolve an existing branch or create a default one.
     */
    protected function resolveOrCreateBranch(): Branch
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            throw new \RuntimeException('Tenant context is missing.');
        }

        $tenantId = (int) $tenant->id;

        // First, check if tenant has a default branch
        if ($tenant->default_branch_id) {
            $branch = Branch::query()
                ->withoutGlobalScopes()
                ->where('id', $tenant->default_branch_id)
                ->where('tenant_id', $tenantId)
                ->first();

            if ($branch) {
                return $branch;
            }
        }

        // Second, find any existing branch for this tenant
        $branch = Branch::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->orderBy('id')
            ->first();

        if ($branch) {
            // Update tenant's default branch
            $tenant->forceFill(['default_branch_id' => $branch->id])->save();
            return $branch;
        }

        // Third, create a new default branch
        $branch = Branch::query()->create([
            'tenant_id' => $tenantId,
            'name' => 'Main Branch',
            'code' => Branch::generateUniqueCode($tenantId, 'MAIN'),
            'is_active' => true,
        ]);

        // Update tenant's default branch
        $tenant->forceFill(['default_branch_id' => $branch->id])->save();

        return $branch;
    }
}
