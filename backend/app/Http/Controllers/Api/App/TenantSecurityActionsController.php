<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Support\PlatformAudit;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class TenantSecurityActionsController extends Controller
{
    public function forceLogout(Request $request)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            return response()->json([
                'message' => 'Tenant is missing.',
            ], 400);
        }

        $tenantId = (int) $tenant->id;

        $tokensDeleted = PersonalAccessToken::query()
            ->whereHasMorph('tokenable', [User::class], function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId)->where('is_admin', false);
            })
            ->delete();

        PlatformAudit::log($request, 'tenant.force_logout', $tenant, null, [
            'tokens_deleted' => $tokensDeleted,
        ]);

        return response()->json([
            'status' => 'ok',
            'tokens_deleted' => $tokensDeleted,
        ]);
    }
}
