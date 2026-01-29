<?php

namespace App\Http\Middleware;

use App\Models\Branch;
use App\Support\BranchContext;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePublicBranch
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = TenantContext::tenant();

        if (! $tenant) {
            return response()->json([
                'message' => 'Business is invalid.',
            ], 404);
        }

        $branchId = is_numeric($tenant->default_branch_id) ? (int) $tenant->default_branch_id : null;

        if (! $branchId) {
            return response()->json([
                'message' => 'Default branch is missing.',
            ], 409);
        }

        $branch = Branch::query()->whereKey($branchId)->first();

        if (! $branch || ! $branch->is_active || (int) $branch->tenant_id !== (int) $tenant->id) {
            return response()->json([
                'message' => 'Default branch is invalid.',
            ], 404);
        }

        BranchContext::set($branch);

        try {
            return $next($request);
        } finally {
            BranchContext::set(null);
        }
    }
}
