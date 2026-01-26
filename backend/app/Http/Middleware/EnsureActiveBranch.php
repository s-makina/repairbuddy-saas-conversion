<?php

namespace App\Http\Middleware;

use App\Models\Branch;
use App\Models\ImpersonationSession;
use App\Models\User;
use App\Support\BranchAccess;
use App\Support\BranchContext;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveBranch
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || $user->is_admin) {
            return $next($request);
        }

        $tenantId = TenantContext::tenantId();
        if (! $tenantId || (int) $user->tenant_id !== (int) $tenantId) {
            return $next($request);
        }

        $activeBranchId = null;

        $session = $request->attributes->get('impersonation_session');
        if ($session instanceof ImpersonationSession) {
            $activeBranchId = $session->active_branch_id;
        } else {
            $token = $user->currentAccessToken();
            if ($token instanceof PersonalAccessToken) {
                $activeBranchId = $token->active_branch_id ?? null;
            }
        }

        $activeBranchId = is_numeric($activeBranchId) ? (int) $activeBranchId : null;

        if (! $activeBranchId) {
            return response()->json([
                'message' => 'Active branch is required.',
                'code' => 'branch_required',
            ], 428);
        }

        $branch = Branch::query()->whereKey($activeBranchId)->first();

        if (! $branch || ! $branch->is_active) {
            return response()->json([
                'message' => 'Active branch is invalid.',
                'code' => 'branch_invalid',
            ], 409);
        }

        if (! BranchAccess::userCanAccessBranch($user, $branch)) {
            return response()->json([
                'message' => 'Active branch is not accessible.',
                'code' => 'branch_forbidden',
            ], 403);
        }

        BranchContext::set($branch);

        try {
            return $next($request);
        } finally {
            BranchContext::set(null);
        }
    }
}
