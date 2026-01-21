<?php

namespace App\Http\Middleware;

use App\Models\TenantSecuritySetting;
use App\Models\User;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceTenantMfa
{
    public function handle(Request $request, Closure $next): Response
    {
        $path = $request->path();
        if (preg_match('#^[^/]+/app/(gate|security-status)$#', $path)) {
            return $next($request);
        }

        $session = $request->attributes->get('impersonation_session');
        if ($session) {
            return $next($request);
        }

        $user = $request->user();

        if (! $user instanceof User || $user->is_admin) {
            return $next($request);
        }

        $tenantId = TenantContext::tenantId();
        if (! $tenantId || (int) $user->tenant_id !== (int) $tenantId) {
            return $next($request);
        }

        $settings = TenantSecuritySetting::query()->where('tenant_id', $tenantId)->first();
        $requiredRoles = is_array($settings?->mfa_required_roles) ? $settings->mfa_required_roles : [];
        $requiredRoles = array_values(array_unique(array_filter(array_map(function ($v) {
            return is_numeric($v) ? (int) $v : null;
        }, $requiredRoles), function ($v) {
            return is_int($v) && $v > 0;
        })));

        if (count($requiredRoles) === 0) {
            return $next($request);
        }

        if (! $settings?->mfa_enforce_after || $settings->mfa_enforce_after->gt(now())) {
            return $next($request);
        }

        if (! $user->role_id || ! in_array((int) $user->role_id, $requiredRoles, true)) {
            return $next($request);
        }

        $isCompliant = (bool) ($user->otp_enabled && $user->otp_confirmed_at);
        if ($isCompliant) {
            return $next($request);
        }

        return response()->json([
            'message' => 'Multi-factor authentication is required.',
            'code' => 'mfa_required',
            'mfa_enforce_after' => $settings->mfa_enforce_after?->toIso8601String(),
        ], 403);
    }
}
