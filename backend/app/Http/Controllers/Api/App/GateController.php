<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Support\TenantContext;
use Illuminate\Http\Request;

class GateController extends Controller
{
    public function show(Request $request)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            return response()->json([
                'message' => 'Tenant is missing.',
            ], 400);
        }

        $subscription = TenantSubscription::query()->orderByDesc('id')->first();

        $subscriptionStatus = $this->normalizeSubscriptionStatus($tenant, $subscription);

        return response()->json([
            'tenant' => $tenant,
            'gate' => [
                'tenant_status' => $tenant->status,
                'subscription_status' => $subscriptionStatus,
                'setup_completed_at' => $tenant->setup_completed_at,
                'setup_step' => $tenant->setup_step,
            ],
        ]);
    }

    private function normalizeSubscriptionStatus(Tenant $tenant, ?TenantSubscription $subscription): string
    {
        if (in_array($tenant->status, ['suspended', 'closed'], true)) {
            return 'suspended';
        }

        $raw = $subscription?->status;

        if ($raw === 'trial') {
            return 'trialing';
        }

        if (in_array($raw, ['active', 'past_due'], true)) {
            return $raw;
        }

        if ($raw === 'suspended') {
            return 'suspended';
        }

        // When a plan is selected but no active/trial subscription exists yet,
        // treat it as "pending_checkout".
        if ($tenant->plan_id) {
            return 'pending_checkout';
        }

        return 'none';
    }
}
