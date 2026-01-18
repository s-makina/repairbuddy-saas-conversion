<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Support\PlatformAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PlanController extends Controller
{
    public function index(Request $request)
    {
        return response()->json([
            'plans' => Plan::query()->orderBy('id', 'desc')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:64', 'unique:plans,code'],
            'price_display' => ['nullable', 'string', 'max:64'],
            'billing_interval' => ['nullable', 'string', 'max:32'],
            'entitlements' => ['nullable', 'array'],
        ]);

        $code = $validated['code'] ?? Str::slug($validated['name']);
        $code = $code ?: 'plan';

        $baseCode = $code;
        $i = 1;

        while (Plan::query()->where('code', $code)->exists()) {
            $code = $baseCode.'-'.$i;
            $i++;
        }

        $plan = Plan::query()->create([
            'name' => $validated['name'],
            'code' => $code,
            'price_display' => $validated['price_display'] ?? null,
            'billing_interval' => $validated['billing_interval'] ?? null,
            'entitlements' => $validated['entitlements'] ?? null,
        ]);

        PlatformAudit::log($request, 'plan.created', null, null, [
            'plan_id' => $plan->id,
            'code' => $plan->code,
        ]);

        return response()->json([
            'plan' => $plan,
        ], 201);
    }

    public function update(Request $request, Plan $plan)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:64', 'unique:plans,code,'.$plan->id],
            'price_display' => ['nullable', 'string', 'max:64'],
            'billing_interval' => ['nullable', 'string', 'max:32'],
            'entitlements' => ['nullable', 'array'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $before = $plan->toArray();

        $plan->forceFill([
            'name' => $validated['name'],
            'code' => $validated['code'],
            'price_display' => $validated['price_display'] ?? null,
            'billing_interval' => $validated['billing_interval'] ?? null,
            'entitlements' => $validated['entitlements'] ?? null,
        ])->save();

        PlatformAudit::log($request, 'plan.updated', null, $validated['reason'] ?? null, [
            'plan_id' => $plan->id,
            'before' => $before,
            'after' => $plan->toArray(),
        ]);

        return response()->json([
            'plan' => $plan,
        ]);
    }

    public function destroy(Request $request, Plan $plan)
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $snapshot = $plan->toArray();
        $planId = $plan->id;

        $plan->delete();

        PlatformAudit::log($request, 'plan.deleted', null, $validated['reason'] ?? null, [
            'plan_id' => $planId,
            'plan' => $snapshot,
        ]);

        return response()->json([
            'status' => 'ok',
        ]);
    }
}
