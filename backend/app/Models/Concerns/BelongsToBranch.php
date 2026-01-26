<?php

namespace App\Models\Concerns;

use App\Models\Scopes\BranchScope;
use App\Support\BranchContext;

trait BelongsToBranch
{
    protected static function bootBelongsToBranch(): void
    {
        static::addGlobalScope(new BranchScope());

        static::creating(function ($model) {
            $branchId = BranchContext::branchId();

            if (! $branchId) {
                throw new \RuntimeException('Branch context is missing.');
            }

            if (! isset($model->branch_id) || ! $model->branch_id) {
                $model->branch_id = $branchId;
            }
        });
    }
}
