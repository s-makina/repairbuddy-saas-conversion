<?php

namespace App\Support;

use App\Models\BillingPrice;
use App\Models\BillingPlanVersion;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Support\Billing\CurrencyMismatchException;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    public function createOrChangeSubscription(Tenant $tenant, BillingPlanVersion $planVersion, BillingPrice $price, ?int $actorUserId = null): TenantSubscription
    {
        $tenantCurrency = strtoupper((string) ($tenant->currency ?? ''));
        $priceCurrency = strtoupper((string) ($price->currency ?? ''));

        if ($tenantCurrency === '' || $priceCurrency === '' || $tenantCurrency !== $priceCurrency) {
            throw new CurrencyMismatchException('Tenant currency does not match price currency.');
        }

        return DB::transaction(function () use ($tenant, $planVersion, $price, $actorUserId) {
            $existing = TenantSubscription::query()
                ->whereIn('status', ['trial', 'active', 'past_due'])
                ->orderByDesc('id')
                ->first();

            if ($existing) {
                $existing->forceFill([
                    'status' => 'canceled',
                    'canceled_at' => now(),
                    'cancel_at_period_end' => false,
                ])->save();

                $existing->events()->create([
                    'event_type' => 'subscription.replaced',
                    'payload_json' => [
                        'new_billing_plan_version_id' => (int) $planVersion->id,
                        'new_billing_price_id' => (int) $price->id,
                    ],
                    'created_by_user_id' => $actorUserId,
                ]);
            }

            [$status, $periodStart, $periodEnd] = $this->initialPeriod($price);

            $subscription = TenantSubscription::query()->create([
                'billing_plan_version_id' => (int) $planVersion->id,
                'billing_price_id' => (int) $price->id,
                'currency' => strtoupper((string) $price->currency),
                'status' => $status,
                'started_at' => now(),
                'current_period_start' => $periodStart,
                'current_period_end' => $periodEnd,
                'cancel_at_period_end' => false,
                'canceled_at' => null,
            ]);

            $subscription->events()->create([
                'event_type' => 'subscription.created',
                'payload_json' => [
                    'billing_plan_version_id' => (int) $planVersion->id,
                    'billing_price_id' => (int) $price->id,
                    'currency' => strtoupper((string) $price->currency),
                ],
                'created_by_user_id' => $actorUserId,
            ]);

            return $subscription;
        });
    }

    public function cancelSubscription(TenantSubscription $subscription, bool $atPeriodEnd = true, ?int $actorUserId = null): TenantSubscription
    {
        return DB::transaction(function () use ($subscription, $atPeriodEnd, $actorUserId) {
            if ($subscription->status === 'canceled') {
                return $subscription;
            }

            if ($atPeriodEnd) {
                $subscription->forceFill([
                    'cancel_at_period_end' => true,
                ])->save();

                $subscription->events()->create([
                    'event_type' => 'subscription.cancel_scheduled',
                    'payload_json' => null,
                    'created_by_user_id' => $actorUserId,
                ]);

                return $subscription;
            }

            $subscription->forceFill([
                'status' => 'canceled',
                'canceled_at' => now(),
                'cancel_at_period_end' => false,
            ])->save();

            $subscription->events()->create([
                'event_type' => 'subscription.canceled',
                'payload_json' => null,
                'created_by_user_id' => $actorUserId,
            ]);

            return $subscription;
        });
    }

    protected function initialPeriod(BillingPrice $price): array
    {
        $now = now();

        $trialDays = is_numeric($price->trial_days) ? (int) $price->trial_days : 0;

        if ($trialDays > 0) {
            return ['trial', $now, $now->copy()->addDays($trialDays)];
        }

        $interval = strtolower((string) $price->interval);

        if ($interval === 'month') {
            return ['active', $now, $now->copy()->addMonth()];
        }

        if ($interval === 'year') {
            return ['active', $now, $now->copy()->addYear()];
        }

        return ['active', $now, null];
    }
}
