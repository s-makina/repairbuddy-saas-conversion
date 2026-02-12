<?php

namespace App\Http\Middleware;

use App\Models\Branch;
use App\Support\BranchContext;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWebBranch
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = TenantContext::tenant();

        if (! $tenant) {
            abort(404, 'Business is invalid.');
        }

        $branchId = is_numeric($tenant->default_branch_id) ? (int) $tenant->default_branch_id : null;

        if (! $branchId) {
            abort(409, 'Default branch is missing.');
        }

        $branch = Branch::query()->whereKey($branchId)->first();

        if (! $branch || ! $branch->is_active || (int) $branch->tenant_id !== (int) $tenant->id) {
            abort(404, 'Default branch is invalid.');
        }

        BranchContext::set($branch);

        try {
            return $next($request);
        } finally {
            BranchContext::set(null);
        }
    }
}
