<?php

namespace App\Models\Concerns;

use App\Models\Scopes\BranchScope;
use App\Models\Scopes\TenantScope;
use App\Support\BranchContext;
use App\Support\TenantContext;

trait BelongsToTenantAndBranch
{
    protected static function bootBelongsToTenantAndBranch(): void
    {
        static::addGlobalScope(new TenantScope());
        static::addGlobalScope(new BranchScope());

        static::creating(function ($model) {
            $tenantId = TenantContext::tenantId();
            if (! $tenantId) {
                throw new \RuntimeException('Tenant context is missing.');
            }

            $branchId = BranchContext::branchId();
            if (! $branchId) {
                throw new \RuntimeException('Branch context is missing.');
            }

            if (! isset($model->tenant_id) || ! $model->tenant_id) {
                $model->tenant_id = $tenantId;
            }

            if (! isset($model->branch_id) || ! $model->branch_id) {
                $model->branch_id = $branchId;
            }
        });
    }
}
