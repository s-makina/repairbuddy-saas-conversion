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
    public function index(Request $request)
    {
        $validated = $request->validate([
            'include_inactive' => ['nullable', 'boolean'],
            'q' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $q = BillingPlan::query()
            ->with(['versions' => function ($vq) {
                $vq->orderByDesc('version');
            }, 'versions.prices', 'versions.entitlements.definition'])
            ->orderByDesc('is_active')
            ->orderBy('name');

        if (! ($validated['include_inactive'] ?? false)) {
            $q->where('is_active', true);
        }

        if (! empty($validated['q'])) {
            $search = $validated['q'];
            $q->where(function ($sq) use ($search) {
                $sq->where('name', 'like', "%{$search}%")
                   ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $plans = $q->paginate((int) ($validated['per_page'] ?? 25));

        return response()->json($plans);
    }

    public function show(Request $request, BillingPlan $plan)
    {
        $plan->load([
            'versions' => function ($q) {
                $q->orderByDesc('version');
            },
            'versions.prices.intervalModel',
            'versions.entitlements.definition',
        ]);

        return response()->json([
            'plan' => $plan,
        ]);
    }

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

    public function update(Request $request, BillingPlan $plan)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:64', 'unique:billing_plans,code,'.$plan->id],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $before = $plan->toArray();

        $plan->forceFill([
            'name' => $validated['name'],
            'code' => $validated['code'],
            'description' => $validated['description'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? $plan->is_active),
        ])->save();

        PlatformAudit::log($request, 'billing.plan.updated', null, $validated['reason'] ?? null, [
            'billing_plan_id' => $plan->id,
            'before' => $before,
            'after' => $plan->toArray(),
        ]);

        return response()->json([
            'plan' => $plan->fresh()->load(['versions.prices', 'versions.entitlements.definition']),
        ]);
    }

    public function destroy(Request $request, BillingPlan $plan)
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        // Guard: cannot delete a plan with any active version
        $hasActive = $plan->versions()->where('status', 'active')->exists();
        if ($hasActive) {
            return response()->json([
                'message' => 'Cannot delete a plan that has an active version. Retire the version first.',
            ], 422);
        }

        $snapshot = $plan->toArray();
        $id = $plan->id;

        // Delete child versions (cascades prices + entitlements via DB FK or manual)
        $plan->versions()->each(function (BillingPlanVersion $v) {
            $v->prices()->delete();
            $v->entitlements()->delete();
            $v->delete();
        });

        $plan->delete();

        PlatformAudit::log($request, 'billing.plan.deleted', null, $validated['reason'] ?? null, [
            'billing_plan_id' => $id,
            'plan' => $snapshot,
        ]);

        return response()->json([
            'status' => 'ok',
        ]);
    }
}
