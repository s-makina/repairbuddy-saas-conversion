<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $resolution = config('tenancy.resolution');

        $tenantSlug = null;

        if ($resolution === 'path') {
            $param = config('tenancy.route_param', 'business');
            $tenantSlug = $request->route($param);
        }

        if ($resolution === 'header') {
            $tenantSlug = $request->header(config('tenancy.header'));
        }

        if (! is_string($tenantSlug) || $tenantSlug === '') {
            TenantContext::set(null);

            return response()->json([
                'message' => 'Business is missing.',
            ], 400);
        }

        $tenant = Tenant::query()
            ->where('slug', $tenantSlug)
            ->whereIn('status', ['trial', 'active', 'past_due'])
            ->first();

        if (! $tenant) {
            TenantContext::set(null);

            return response()->json([
                'message' => 'Business is invalid.',
            ], 404);
        }

        TenantContext::set($tenant);

        $response = $next($request);

        TenantContext::set(null);

        return $response;
    }
}
