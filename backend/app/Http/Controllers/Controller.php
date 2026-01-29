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
            throw new \RuntimeException('Branch context is missing.');
        }

        return $branch;
    }

    protected function branchId(): int
    {
        $branchId = BranchContext::branchId();

        if (! is_int($branchId) || $branchId <= 0) {
            throw new \RuntimeException('Branch context is missing.');
        }

        return $branchId;
    }
}
