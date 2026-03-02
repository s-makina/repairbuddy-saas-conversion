<?php

namespace App\Models\Concerns;

use App\Models\Scopes\TenantScope;
use App\Support\BranchContext;
use App\Support\TenantContext;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope());

        static::creating(function ($model) {
            $tenantId = TenantContext::tenantId();

            if (! $tenantId) {
                throw new \RuntimeException('Tenant context is missing.');
            }

            if (! isset($model->tenant_id) || ! $model->tenant_id) {
                $model->tenant_id = $tenantId;
            }

            // Also auto-fill branch_id if the model has the column and BranchContext is set
            if (isset($model->branch_id) || in_array('branch_id', $model->getFillable())) {
                $branchId = BranchContext::branchId();
                if ($branchId && (! isset($model->branch_id) || ! $model->branch_id)) {
                    $model->branch_id = $branchId;
                }
            }
        });
    }
}
