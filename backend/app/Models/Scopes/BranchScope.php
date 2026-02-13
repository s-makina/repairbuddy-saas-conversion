<?php

namespace App\Models\Scopes;

use App\Support\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Schema;

class BranchScope implements Scope
{
    /**
     * @var array<string, bool>
     */
    private static array $tablesWithBranchId = [];

    public function apply(Builder $builder, Model $model): void
    {
        $table = $model->getTable();

        if (! array_key_exists($table, self::$tablesWithBranchId)) {
            self::$tablesWithBranchId[$table] = Schema::hasColumn($table, 'branch_id');
        }

        if (! self::$tablesWithBranchId[$table]) {
            return;
        }

        $branchId = BranchContext::branchId();

        if (! $branchId) {
            $builder->whereRaw('1 = 0');

            return;
        }

        $builder->where($table.'.branch_id', $branchId);
    }
}
