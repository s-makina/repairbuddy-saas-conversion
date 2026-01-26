<?php

namespace App\Models\Scopes;

use App\Support\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class BranchScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $branchId = BranchContext::branchId();

        if (! $branchId) {
            $builder->whereRaw('1 = 0');

            return;
        }

        $builder->where($model->getTable().'.branch_id', $branchId);
    }
}
