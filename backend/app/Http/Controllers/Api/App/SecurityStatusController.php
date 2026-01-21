<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Models\TenantSecuritySetting;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Http\Request;

class SecurityStatusController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        if (! $user instanceof User || $user->is_admin) {
            return response()->json([
                'mfa' => [
                    'required_for_user' => false,
                    'enforced' => false,
                    'compliant' => true,
                    'enforce_after' => null,
                ],
            ]);
        }

        $tenantId = TenantContext::tenantId();

        $settings = $tenantId ? TenantSecuritySetting::query()->where('tenant_id', $tenantId)->first() : null;
        $requiredRoles = is_array($settings?->mfa_required_roles) ? $settings->mfa_required_roles : [];
        $requiredRoles = array_values(array_unique(array_filter(array_map(function ($v) {
            return is_numeric($v) ? (int) $v : null;
        }, $requiredRoles), function ($v) {
            return is_int($v) && $v > 0;
        })));

        $requiredForUser = $user->role_id && in_array((int) $user->role_id, $requiredRoles, true);
        $enforced = $requiredForUser && $settings?->mfa_enforce_after && $settings->mfa_enforce_after->lte(now());
        $compliant = (bool) ($user->otp_enabled && $user->otp_confirmed_at);

        return response()->json([
            'mfa' => [
                'required_for_user' => $requiredForUser,
                'enforced' => (bool) $enforced,
                'compliant' => $compliant,
                'enforce_after' => $settings?->mfa_enforce_after?->toIso8601String(),
            ],
        ]);
    }
}
