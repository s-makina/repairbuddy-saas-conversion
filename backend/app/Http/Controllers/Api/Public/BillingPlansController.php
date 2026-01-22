<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\BillingPlan;
use Illuminate\Http\Request;

class BillingPlansController extends Controller
{
    public function index(Request $request)
    {
        $plans = BillingPlan::query()
            ->where('is_active', true)
            ->with([
                'versions' => function ($q) {
                    $q
                        ->where('status', 'active')
                        ->orderByDesc('version')
                        ->with(['prices.intervalModel', 'entitlements.definition', 'plan']);
                },
            ])
            ->orderBy('id')
            ->get();

        return response()->json([
            'billing_plans' => $plans,
        ]);
    }
}
