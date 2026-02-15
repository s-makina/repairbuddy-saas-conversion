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

        // Some screens (e.g. Settings) should still be accessible even if a tenant does not
        // have an active default branch yet. Only enforce branch resolution when possible.
        // For web UI, allow a per-user active branch stored in session.
        $sessionBranchId = $request->session()->get('active_branch_id');
        $branchId = is_numeric($sessionBranchId) ? (int) $sessionBranchId : null;

        if (! $branchId) {
            $branchId = is_numeric($tenant->default_branch_id) ? (int) $tenant->default_branch_id : null;
        }

        if (! $branchId) {
            return $next($request);
        }

        $branch = Branch::query()->whereKey($branchId)->first();

        if (! $branch || ! $branch->is_active || (int) $branch->tenant_id !== (int) $tenant->id) {
            return $next($request);
        }

        BranchContext::set($branch);

        try {
            return $next($request);
        } finally {
            BranchContext::set(null);
        }
    }
}
