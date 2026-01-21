<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceOnboardingGate
{
    public function handle(Request $request, Closure $next): Response
    {
        $path = $request->path();

        $session = $request->attributes->get('impersonation_session');
        if ($session) {
            return $next($request);
        }

        if (preg_match('#^(?:api/)?[^/]+/app/(gate|security-status)$#', $path)) {
            return $next($request);
        }

        if (preg_match('#^(?:api/)?[^/]+/app/billing(?:/.*)?$#', $path)) {
            return $next($request);
        }

        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            return $next($request);
        }

        $user = $request->user();
        if ($user instanceof User && $user->is_admin) {
            return $next($request);
        }

        $business = (string) $tenant->slug;

        if (in_array($tenant->status, ['suspended', 'closed'], true)) {
            return $this->deny('This business is suspended.', 'tenant_suspended', '/'.$business.'/suspended');
        }

        $subscription = TenantSubscription::query()->orderByDesc('id')->first();
        $subscriptionStatus = $this->normalizeSubscriptionStatus($tenant, $subscription);

        if ($subscriptionStatus === 'none') {
            return $this->deny('Plan selection is required.', 'subscription_required', '/'.$business.'/plans');
        }

        if ($subscriptionStatus === 'pending_checkout') {
            return $this->deny('Checkout is required.', 'checkout_required', '/'.$business.'/checkout');
        }

        if (in_array($subscriptionStatus, ['trialing', 'active', 'past_due'], true) && ! $tenant->setup_completed_at) {
            if (preg_match('#^(?:api/)?[^/]+/app/setup(?:/complete)?$#', $path)) {
                return $next($request);
            }

            return $this->deny('Business setup is required.', 'setup_required', '/'.$business.'/setup');
        }

        return $next($request);
    }

    private function deny(string $message, string $code, string $redirectTo): Response
    {
        return response()->json([
            'message' => $message,
            'code' => $code,
            'redirect_to' => $redirectTo,
        ], 403);
    }

    private function normalizeSubscriptionStatus(Tenant $tenant, ?TenantSubscription $subscription): string
    {
        $raw = $subscription?->status;

        if ($raw === 'trial') {
            return 'trialing';
        }

        if ($raw === 'pending') {
            return 'pending_checkout';
        }

        if (in_array($raw, ['active', 'past_due'], true)) {
            return $raw;
        }

        if ($raw === 'suspended') {
            return 'suspended';
        }

        return $tenant->plan_id ? 'pending_checkout' : 'none';
    }
}
