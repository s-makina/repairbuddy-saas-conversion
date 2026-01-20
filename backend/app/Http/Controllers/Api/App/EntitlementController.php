<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Models\TenantSubscription;
use App\Support\EntitlementsService;
use App\Support\TenantContext;
use Illuminate\Http\Request;

class EntitlementController extends Controller
{
    public function index(Request $request)
    {
        $tenant = TenantContext::tenant();

        $subscription = $tenant
            ? TenantSubscription::query()->with(['planVersion.plan', 'price'])->orderByDesc('id')->first()
            : null;

        return response()->json([
            'tenant' => $tenant,
            'subscription' => $subscription,
            'plan' => $subscription?->planVersion?->plan,
            'plan_version' => $subscription?->planVersion,
            'entitlements' => $tenant ? (new EntitlementsService())->resolveForTenant($tenant) : [],
        ]);
    }
}
