<?php

namespace App\Support;

use App\Models\Branch;
use App\Models\User;
use App\Support\Permissions;

class BranchAccess
{
    public static function userCanAccessBranch(?User $user, ?Branch $branch): bool
    {
        if (! $user || ! $branch) {
            return false;
        }

        if ($user->is_admin) {
            return true;
        }

        $tenantId = TenantContext::tenantId();
        if (! $tenantId || (int) $user->tenant_id !== (int) $tenantId || (int) $branch->tenant_id !== (int) $tenantId) {
            return false;
        }

        if (Permissions::userHas($user, 'branches.manage')) {
            return true;
        }

        return $branch->users()->whereKey($user->id)->exists();
    }

    public static function accessibleBranchIdsForUser(User $user): array
    {
        $tenantId = TenantContext::tenantId();

        if ($user->is_admin) {
            return Branch::query()->pluck('id')->all();
        }

        if (! $tenantId || (int) $user->tenant_id !== (int) $tenantId) {
            return [];
        }

        if (Permissions::userHas($user, 'branches.manage')) {
            return Branch::query()->pluck('id')->all();
        }

        return Branch::query()
            ->select('branches.id')
            ->join('branch_user', 'branch_user.branch_id', '=', 'branches.id')
            ->where('branch_user.user_id', $user->id)
            ->pluck('branches.id')
            ->all();
    }
}
