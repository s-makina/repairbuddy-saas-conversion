<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBusinessSetup
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            return $next($request);
        }

        // Setup already completed — carry on
        if ($tenant->setup_completed_at) {
            return $next($request);
        }

        // Allow admin users through without setup requirement
        $user = $request->user();
        if ($user && $user->is_admin) {
            return $next($request);
        }

        // Redirect to the setup wizard (subdomain-based URL)
        $setupUrl = route('tenant.subdomain.setup', ['business' => $tenant->slug]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Business setup is required.',
                'code' => 'setup_required',
                'redirect_to' => $setupUrl,
            ], 403);
        }

        return redirect($setupUrl);
    }
}
