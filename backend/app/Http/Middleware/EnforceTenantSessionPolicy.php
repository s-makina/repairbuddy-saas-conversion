<?php

namespace App\Http\Middleware;

use App\Models\TenantSecuritySetting;
use App\Models\User;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceTenantSessionPolicy
{
    public function handle(Request $request, Closure $next): Response
    {
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

        $token = $user->currentAccessToken();
        if (! $token) {
            return $next($request);
        }

        $settings = TenantSecuritySetting::query()->where('tenant_id', $tenantId)->first();

        $idleMinutes = (int) ($settings?->session_idle_timeout_minutes ?? 60);
        $maxLifetimeDays = (int) ($settings?->session_max_lifetime_days ?? 30);

        if ($token->expires_at && $token->expires_at->lte(now())) {
            $token->delete();

            return response()->json([
                'message' => 'Session expired.',
            ], 401);
        }

        if ($maxLifetimeDays > 0 && $token->created_at && $token->created_at->lte(now()->subDays($maxLifetimeDays))) {
            $token->delete();

            return response()->json([
                'message' => 'Session expired.',
            ], 401);
        }

        if ($idleMinutes > 0) {
            $lastUsed = $token->last_used_at ?? $token->created_at;

            if ($lastUsed && $lastUsed->lte(now()->subMinutes($idleMinutes))) {
                $token->delete();

                return response()->json([
                    'message' => 'Session expired.',
                ], 401);
            }
        }

        return $next($request);
    }
}
