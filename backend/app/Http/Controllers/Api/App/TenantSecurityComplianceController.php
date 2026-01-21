<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantSecuritySetting;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Http\Request;

class TenantSecurityComplianceController extends Controller
{
    public function show(Request $request)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            return response()->json([
                'message' => 'Tenant is missing.',
            ], 400);
        }

        $tenantId = (int) $tenant->id;

        $settings = TenantSecuritySetting::query()->where('tenant_id', $tenantId)->first();
        $requiredRoles = is_array($settings?->mfa_required_roles) ? $settings->mfa_required_roles : [];
        $requiredRoles = array_values(array_unique(array_filter(array_map(function ($v) {
            return is_numeric($v) ? (int) $v : null;
        }, $requiredRoles), function ($v) {
            return is_int($v) && $v > 0;
        })));

        if (count($requiredRoles) === 0) {
            return response()->json([
                'mfa' => [
                    'required_roles' => [],
                    'enforce_after' => $settings?->mfa_enforce_after?->toIso8601String(),
                    'total_in_scope' => 0,
                    'compliant' => 0,
                    'non_compliant' => 0,
                    'non_compliant_users' => [],
                ],
            ]);
        }

        $base = User::query()
            ->where('tenant_id', $tenantId)
            ->where('is_admin', false)
            ->whereIn('role_id', $requiredRoles);

        $total = (clone $base)->count();

        $compliantCount = (clone $base)
            ->where('otp_enabled', true)
            ->whereNotNull('otp_confirmed_at')
            ->count();

        $nonCompliantQuery = (clone $base)
            ->where(function ($q) {
                $q->where('otp_enabled', false)
                    ->orWhereNull('otp_confirmed_at');
            })
            ->with(['roleModel'])
            ->orderBy('id');

        $nonCompliantUsers = $nonCompliantQuery->get();

        return response()->json([
            'mfa' => [
                'required_roles' => $requiredRoles,
                'enforce_after' => $settings?->mfa_enforce_after?->toIso8601String(),
                'total_in_scope' => $total,
                'compliant' => $compliantCount,
                'non_compliant' => max(0, $total - $compliantCount),
                'non_compliant_users' => $nonCompliantUsers,
            ],
        ]);
    }
}
