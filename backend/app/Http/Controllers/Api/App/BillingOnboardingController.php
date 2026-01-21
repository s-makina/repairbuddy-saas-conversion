<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Models\BillingPlan;
use App\Models\BillingPlanVersion;
use App\Models\BillingPrice;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Support\PlatformAudit;
use App\Support\SubscriptionService;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BillingOnboardingController extends Controller
{
    public function plans(Request $request)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            return response()->json([
                'message' => 'Tenant is missing.',
            ], 400);
        }

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

    public function checkout(Request $request)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            return response()->json([
                'message' => 'Tenant is missing.',
            ], 400);
        }

        $subscription = TenantSubscription::query()
            ->where('status', 'pending')
            ->orderByDesc('id')
            ->with(['planVersion.plan', 'price.intervalModel'])
            ->first();

        return response()->json([
            'tenant' => $tenant,
            'subscription' => $subscription,
        ]);
    }

    public function subscribe(Request $request)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            return response()->json([
                'message' => 'Tenant is missing.',
            ], 400);
        }

        $validated = $request->validate([
            'billing_price_id' => ['required', 'integer', 'exists:billing_prices,id'],
            'billing_country' => ['required', 'string', 'size:2'],
            'currency' => ['required', 'string', 'size:3'],
            'billing_vat_number' => ['nullable', 'string', 'max:255'],
        ]);

        $billingCountry = strtoupper((string) $validated['billing_country']);
        $currency = strtoupper((string) $validated['currency']);

        $price = BillingPrice::query()->with(['planVersion.plan', 'intervalModel'])->findOrFail((int) $validated['billing_price_id']);

        $version = $price->planVersion;
        if (! $version instanceof BillingPlanVersion || $version->status !== 'active') {
            return response()->json([
                'message' => 'Selected plan is not available.',
            ], 422);
        }

        $plan = $version->plan;
        if (! $plan || ! $plan->is_active) {
            return response()->json([
                'message' => 'Selected plan is not available.',
            ], 422);
        }

        $priceCurrency = strtoupper((string) $price->currency);
        if ($priceCurrency !== $currency) {
            return response()->json([
                'message' => 'Currency does not match the selected price.',
            ], 422);
        }

        $tenant->forceFill([
            'billing_country' => $billingCountry,
            'currency' => $currency,
            'billing_vat_number' => ($validated['billing_vat_number'] ?? null) ?: null,
        ])->save();

        $actor = $request->user();
        $actorUserId = $actor ? (int) $actor->id : null;

        $subscription = DB::transaction(function () use ($tenant, $version, $price, $actorUserId) {
            $existingActive = TenantSubscription::query()
                ->whereIn('status', ['trial', 'active', 'past_due'])
                ->orderByDesc('id')
                ->first();

            if ($existingActive) {
                abort(422, 'A subscription already exists for this tenant.');
            }

            TenantSubscription::query()
                ->where('status', 'pending')
                ->update([
                    'status' => 'canceled',
                    'canceled_at' => now(),
                    'cancel_at_period_end' => false,
                ]);

            $trialDays = is_numeric($price->trial_days) ? (int) $price->trial_days : 0;

            if ($trialDays > 0) {
                return (new SubscriptionService())->createOrChangeSubscription(
                    tenant: $tenant,
                    planVersion: $version,
                    price: $price,
                    actorUserId: $actorUserId,
                );
            }

            $subscription = TenantSubscription::query()->create([
                'billing_plan_version_id' => (int) $version->id,
                'billing_price_id' => (int) $price->id,
                'currency' => strtoupper((string) $price->currency),
                'status' => 'pending',
                'started_at' => now(),
                'current_period_start' => null,
                'current_period_end' => null,
                'cancel_at_period_end' => false,
                'canceled_at' => null,
            ]);

            $subscription->events()->create([
                'event_type' => 'subscription.pending_created',
                'payload_json' => [
                    'billing_plan_version_id' => (int) $version->id,
                    'billing_price_id' => (int) $price->id,
                    'currency' => strtoupper((string) $price->currency),
                ],
                'created_by_user_id' => $actorUserId,
            ]);

            return $subscription;
        });

        PlatformAudit::log($request, 'billing.onboarding.subscribed', $tenant, null, [
            'tenant_subscription_id' => $subscription->id,
            'billing_plan_version_id' => (int) $version->id,
            'billing_price_id' => (int) $price->id,
        ]);

        return response()->json([
            'tenant' => $tenant->fresh(),
            'subscription' => $subscription->load(['planVersion.plan', 'price.intervalModel']),
            'gate' => $this->gateSnapshot($tenant),
        ], 201);
    }

    public function confirmCheckout(Request $request)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            return response()->json([
                'message' => 'Tenant is missing.',
            ], 400);
        }

        $actor = $request->user();
        $actorUserId = $actor ? (int) $actor->id : null;

        $subscription = TenantSubscription::query()
            ->where('status', 'pending')
            ->orderByDesc('id')
            ->with(['price.intervalModel', 'planVersion.plan'])
            ->first();

        if (! $subscription) {
            return response()->json([
                'message' => 'No pending checkout was found.',
            ], 422);
        }

        $price = $subscription->price;
        if (! $price instanceof BillingPrice) {
            return response()->json([
                'message' => 'Subscription price is missing.',
            ], 422);
        }

        $now = now();
        $periodEnd = null;

        $months = $price->intervalModel?->months;
        if (is_numeric($months) && (int) $months > 0) {
            $periodEnd = $now->copy()->addMonths((int) $months);
        } else {
            $interval = strtolower((string) $price->interval);
            if ($interval === 'month') {
                $periodEnd = $now->copy()->addMonth();
            } elseif ($interval === 'year') {
                $periodEnd = $now->copy()->addYear();
            }
        }

        $subscription->forceFill([
            'status' => 'active',
            'started_at' => $subscription->started_at ?: $now,
            'current_period_start' => $now,
            'current_period_end' => $periodEnd,
            'cancel_at_period_end' => false,
            'canceled_at' => null,
        ])->save();

        $subscription->events()->create([
            'event_type' => 'subscription.activated',
            'payload_json' => [
                'current_period_start' => $subscription->current_period_start?->toIso8601String(),
                'current_period_end' => $subscription->current_period_end?->toIso8601String(),
            ],
            'created_by_user_id' => $actorUserId,
        ]);

        PlatformAudit::log($request, 'billing.onboarding.checkout_confirmed', $tenant, null, [
            'tenant_subscription_id' => $subscription->id,
        ]);

        return response()->json([
            'tenant' => $tenant->fresh(),
            'subscription' => $subscription->fresh()->load(['planVersion.plan', 'price.intervalModel']),
            'gate' => $this->gateSnapshot($tenant),
        ]);
    }

    private function gateSnapshot(Tenant $tenant): array
    {
        $subscription = TenantSubscription::query()
            ->whereIn('status', ['pending', 'trial', 'active', 'past_due', 'suspended'])
            ->orderByDesc('id')
            ->first();

        return [
            'tenant_status' => $tenant->status,
            'subscription_status' => $this->normalizeSubscriptionStatus($tenant, $subscription),
            'setup_completed_at' => $tenant->setup_completed_at,
            'setup_step' => $tenant->setup_step,
        ];
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
