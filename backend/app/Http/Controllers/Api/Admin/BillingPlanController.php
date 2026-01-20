<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\BillingPlan;
use App\Models\BillingPlanVersion;
use App\Support\PlatformAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BillingPlanController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:64', 'unique:billing_plans,code'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $code = $validated['code'] ?? Str::slug($validated['name']);
        $code = $code ?: 'plan';

        $baseCode = $code;
        $i = 1;

        while (BillingPlan::query()->where('code', $code)->exists()) {
            $code = $baseCode.'-'.$i;
            $i++;
        }

        $plan = BillingPlan::query()->create([
            'name' => $validated['name'],
            'code' => $code,
            'description' => $validated['description'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        $version = BillingPlanVersion::query()->create([
            'billing_plan_id' => $plan->id,
            'version' => 1,
            'status' => 'draft',
        ]);

        PlatformAudit::log($request, 'billing.plan.created', null, null, [
            'billing_plan_id' => $plan->id,
            'code' => $plan->code,
            'billing_plan_version_id' => $version->id,
        ]);

        return response()->json([
            'plan' => $plan->load(['versions.prices', 'versions.entitlements.definition']),
        ], 201);
    }
}
