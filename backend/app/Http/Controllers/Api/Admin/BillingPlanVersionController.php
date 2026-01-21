<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\BillingInterval;
use App\Models\BillingPlan;
use App\Models\BillingPlanVersion;
use App\Models\BillingPrice;
use App\Models\EntitlementDefinition;
use App\Models\PlanEntitlement;
use App\Support\PlatformAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BillingPlanVersionController extends Controller
{
    protected function assertEditable(BillingPlanVersion $version): void
    {
        if ($version->status !== 'draft') {
            abort(422, 'Only draft plan versions can be modified.');
        }

        if ($version->locked_at) {
            abort(422, 'This plan version is locked and cannot be modified.');
        }

        if ($version->activated_at || $version->retired_at) {
            abort(422, 'Active/retired plan versions are immutable.');
        }
    }

    public function createDraftFromActive(Request $request, BillingPlan $plan)
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $draft = DB::transaction(function () use ($plan) {
            $active = $plan->versions()->where('status', 'active')->orderByDesc('version')->first();
            $source = $active ?: $plan->versions()->orderByDesc('version')->first();

            $nextVersionNumber = ((int) $plan->versions()->max('version')) + 1;

            $draft = BillingPlanVersion::query()->create([
                'billing_plan_id' => $plan->id,
                'version' => $nextVersionNumber,
                'status' => 'draft',
                'locked_at' => null,
                'activated_at' => null,
                'retired_at' => null,
            ]);

            if ($source) {
                foreach ($source->prices()->get() as $price) {
                    $draft->prices()->create([
                        'currency' => $price->currency,
                        'interval' => $price->interval,
                        'billing_interval_id' => $price->billing_interval_id,
                        'amount_cents' => (int) $price->amount_cents,
                        'trial_days' => $price->trial_days,
                        'is_default' => (bool) $price->is_default,
                    ]);
                }

                foreach ($source->entitlements()->get() as $ent) {
                    $draft->entitlements()->create([
                        'entitlement_definition_id' => $ent->entitlement_definition_id,
                        'value_json' => $ent->value_json,
                    ]);
                }
            }

            return $draft;
        });

        PlatformAudit::log($request, 'billing.plan_version.draft_created', null, $validated['reason'] ?? null, [
            'billing_plan_id' => $plan->id,
            'billing_plan_version_id' => $draft->id,
        ]);

        return response()->json([
            'version' => $draft->load(['plan', 'prices', 'entitlements.definition']),
        ], 201);
    }

    public function validateDraft(Request $request, BillingPlanVersion $version)
    {
        $this->assertEditable($version);

        $errors = [];

        $prices = $version->prices()->with('intervalModel')->get();
        if ($prices->count() === 0) {
            $errors[] = 'At least one price is required.';
        }

        $defaults = [];

        foreach ($prices as $p) {
            $currency = strtoupper((string) $p->currency);
            $interval = strtolower((string) ($p->intervalModel?->code ?? $p->interval));

            if (strlen($currency) !== 3) {
                $errors[] = "Invalid currency for price #{$p->id}.";
            }

            if (! $p->billing_interval_id) {
                if (! in_array($interval, ['month', 'year'], true)) {
                    $errors[] = "Invalid interval for price #{$p->id}.";
                }
            }

            if (! is_numeric($p->amount_cents) || (int) $p->amount_cents < 0) {
                $errors[] = "Invalid amount for price #{$p->id}.";
            }

            if ((bool) $p->is_default) {
                $key = $currency.'|'.$interval;
                $defaults[$key] = ($defaults[$key] ?? 0) + 1;
            }
        }

        foreach ($defaults as $key => $count) {
            if ($count > 1) {
                $errors[] = "Multiple default prices configured for {$key}.";
            }
        }

        // Require a default per currency+interval that exists.
        $pairs = [];
        foreach ($prices as $p) {
            $pairInterval = strtolower((string) ($p->intervalModel?->code ?? $p->interval));
            $pairs[strtoupper((string) $p->currency).'|'.$pairInterval] = true;
        }
        foreach (array_keys($pairs) as $pair) {
            if (! isset($defaults[$pair])) {
                $errors[] = "Missing default price for {$pair}.";
            }
        }

        // Validate entitlements have valid definitions.
        $definitions = EntitlementDefinition::query()->pluck('id')->all();
        $known = array_fill_keys(array_map('intval', $definitions), true);

        foreach ($version->entitlements()->get() as $e) {
            if (! isset($known[(int) $e->entitlement_definition_id])) {
                $errors[] = "Invalid entitlement definition id {$e->entitlement_definition_id}.";
            }
        }

        if (count($errors) > 0) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $errors,
            ], 422);
        }

        return response()->json([
            'status' => 'ok',
        ]);
    }

    public function syncEntitlements(Request $request, BillingPlanVersion $version)
    {
        $this->assertEditable($version);

        $validated = $request->validate([
            'entitlements' => ['required', 'array'],
            'entitlements.*.entitlement_definition_id' => ['required', 'integer', 'exists:entitlement_definitions,id'],
            'entitlements.*.value_json' => ['present'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $incoming = $validated['entitlements'] ?? [];

        $seen = [];
        foreach ($incoming as $row) {
            $defId = (int) $row['entitlement_definition_id'];
            if (isset($seen[$defId])) {
                return response()->json(['message' => 'Duplicate entitlement_definition_id in request.'], 422);
            }
            $seen[$defId] = true;
        }

        DB::transaction(function () use ($version, $incoming) {
            PlanEntitlement::query()->where('billing_plan_version_id', $version->id)->delete();

            foreach ($incoming as $row) {
                PlanEntitlement::query()->create([
                    'billing_plan_version_id' => $version->id,
                    'entitlement_definition_id' => (int) $row['entitlement_definition_id'],
                    'value_json' => $row['value_json'],
                ]);
            }
        });

        PlatformAudit::log($request, 'billing.plan_version.entitlements_synced', null, $validated['reason'] ?? null, [
            'billing_plan_version_id' => $version->id,
        ]);

        return response()->json([
            'version' => $version->fresh()->load(['plan', 'prices', 'entitlements.definition']),
        ]);
    }

    public function pricesStore(Request $request, BillingPlanVersion $version)
    {
        $this->assertEditable($version);

        $validated = $request->validate([
            'currency' => ['required', 'string', 'size:3'],
            'billing_interval_id' => ['nullable', 'integer', 'exists:billing_intervals,id'],
            'interval' => ['nullable', 'string', 'in:month,year'],
            'amount_cents' => ['required', 'integer', 'min:0'],
            'trial_days' => ['nullable', 'integer', 'min:0'],
            'is_default' => ['nullable', 'boolean'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $currency = strtoupper((string) $validated['currency']);
        $intervalModel = null;
        if (isset($validated['billing_interval_id']) && $validated['billing_interval_id']) {
            $intervalModel = BillingInterval::query()->find((int) $validated['billing_interval_id']);
        }

        $interval = $intervalModel ? strtolower((string) $intervalModel->code) : strtolower((string) ($validated['interval'] ?? ''));
        if ($interval === '') {
            return response()->json(['message' => 'billing_interval_id or interval is required.'], 422);
        }
        $isDefault = (bool) ($validated['is_default'] ?? false);

        $billingIntervalId = $intervalModel ? (int) $intervalModel->id : null;

        $price = DB::transaction(function () use ($version, $currency, $interval, $billingIntervalId, $validated, $isDefault) {
            if ($isDefault) {
                BillingPrice::query()
                    ->where('billing_plan_version_id', $version->id)
                    ->where('currency', $currency)
                    ->where('interval', $interval)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }

            return BillingPrice::query()->create([
                'billing_plan_version_id' => $version->id,
                'currency' => $currency,
                'interval' => $interval,
                'billing_interval_id' => $billingIntervalId,
                'amount_cents' => (int) $validated['amount_cents'],
                'trial_days' => isset($validated['trial_days']) ? (int) $validated['trial_days'] : null,
                'is_default' => $isDefault,
            ]);
        });

        PlatformAudit::log($request, 'billing.plan_version.price_created', null, $validated['reason'] ?? null, [
            'billing_plan_version_id' => $version->id,
            'billing_price_id' => $price->id,
        ]);

        return response()->json([
            'price' => $price,
            'version' => $version->fresh()->load(['plan', 'prices.intervalModel', 'entitlements.definition']),
        ], 201);
    }

    public function pricesUpdate(Request $request, BillingPrice $price)
    {
        $version = $price->planVersion;
        if (! $version) {
            return response()->json(['message' => 'Price version not found.'], 404);
        }

        $this->assertEditable($version);

        $validated = $request->validate([
            'amount_cents' => ['required', 'integer', 'min:0'],
            'trial_days' => ['nullable', 'integer', 'min:0'],
            'is_default' => ['nullable', 'boolean'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $isDefault = (bool) ($validated['is_default'] ?? $price->is_default);

        DB::transaction(function () use ($price, $version, $validated, $isDefault) {
            if ($isDefault) {
                BillingPrice::query()
                    ->where('billing_plan_version_id', $version->id)
                    ->where('currency', $price->currency)
                    ->where('interval', $price->interval)
                    ->where('is_default', true)
                    ->where('id', '!=', $price->id)
                    ->update(['is_default' => false]);
            }

            $price->forceFill([
                'amount_cents' => (int) $validated['amount_cents'],
                'trial_days' => isset($validated['trial_days']) ? (int) $validated['trial_days'] : null,
                'is_default' => $isDefault,
            ])->save();
        });

        PlatformAudit::log($request, 'billing.plan_version.price_updated', null, $validated['reason'] ?? null, [
            'billing_price_id' => $price->id,
            'billing_plan_version_id' => $version->id,
        ]);

        return response()->json([
            'price' => $price->fresh(),
            'version' => $version->fresh()->load(['plan', 'prices', 'entitlements.definition']),
        ]);
    }

    public function pricesDelete(Request $request, BillingPrice $price)
    {
        $version = $price->planVersion;
        if (! $version) {
            return response()->json(['message' => 'Price version not found.'], 404);
        }

        $this->assertEditable($version);

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $snapshot = $price->toArray();
        $priceId = $price->id;

        $price->delete();

        PlatformAudit::log($request, 'billing.plan_version.price_deleted', null, $validated['reason'] ?? null, [
            'billing_price_id' => $priceId,
            'billing_plan_version_id' => $version->id,
            'price' => $snapshot,
        ]);

        return response()->json([
            'status' => 'ok',
            'version' => $version->fresh()->load(['plan', 'prices', 'entitlements.definition']),
        ]);
    }

    public function activate(Request $request, BillingPlanVersion $version)
    {
        $this->assertEditable($version);

        $validated = $request->validate([
            'confirm' => ['required', 'string', 'in:ACTIVATE'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $res = $this->validateDraft($request, $version);
        if ($res->getStatusCode() !== 200) {
            return $res;
        }

        DB::transaction(function () use ($version) {
            $planId = (int) $version->billing_plan_id;

            $active = BillingPlanVersion::query()
                ->where('billing_plan_id', $planId)
                ->where('status', 'active')
                ->lockForUpdate()
                ->first();

            if ($active && (int) $active->id !== (int) $version->id) {
                $active->forceFill([
                    'status' => 'retired',
                    'retired_at' => now(),
                    'locked_at' => $active->locked_at ?? now(),
                ])->save();
            }

            $version->forceFill([
                'status' => 'active',
                'activated_at' => now(),
                'locked_at' => now(),
            ])->save();
        });

        PlatformAudit::log($request, 'billing.plan_version.activated', null, $validated['reason'] ?? null, [
            'billing_plan_version_id' => $version->id,
            'billing_plan_id' => $version->billing_plan_id,
        ]);

        return response()->json([
            'version' => $version->fresh()->load(['plan', 'prices', 'entitlements.definition']),
        ]);
    }

    public function retire(Request $request, BillingPlanVersion $version)
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        if ($version->status !== 'active') {
            return response()->json(['message' => 'Only active plan versions can be retired.'], 422);
        }

        $version->forceFill([
            'status' => 'retired',
            'retired_at' => now(),
            'locked_at' => $version->locked_at ?? now(),
        ])->save();

        PlatformAudit::log($request, 'billing.plan_version.retired', null, $validated['reason'] ?? null, [
            'billing_plan_version_id' => $version->id,
        ]);

        return response()->json([
            'version' => $version->fresh()->load(['plan', 'prices', 'entitlements.definition']),
        ]);
    }
}
