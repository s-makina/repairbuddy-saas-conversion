<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\BillingPlan;
use App\Models\EntitlementDefinition;
use Illuminate\Http\Request;

class BillingCatalogController extends Controller
{
    public function catalog(Request $request)
    {
        $validated = $request->validate([
            'include_inactive' => ['nullable', 'boolean'],
        ]);

        $includeInactive = (bool) ($validated['include_inactive'] ?? false);

        $plansQuery = BillingPlan::query()->orderBy('id', 'desc');

        if (! $includeInactive) {
            $plansQuery->where('is_active', true);
        }

        $plans = $plansQuery
            ->with([
                'versions' => function ($q) {
                    $q->orderByDesc('version');
                },
                'versions.prices' => function ($q) {
                    $q->orderBy('currency')->orderBy('interval')->orderByDesc('is_default');
                },
                'versions.entitlements.definition',
            ])
            ->get();

        $definitions = EntitlementDefinition::query()->orderBy('id')->get();

        return response()->json([
            'billing_plans' => $plans,
            'entitlement_definitions' => $definitions,
        ]);
    }
}
