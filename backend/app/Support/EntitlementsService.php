<?php

namespace App\Support;

use App\Models\PlanEntitlement;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use Illuminate\Support\Facades\Cache;

class EntitlementsService
{
    public function resolveForTenant(Tenant $tenant, bool $useCache = true): array
    {
        $tenantId = (int) $tenant->id;

        $subscription = TenantSubscription::query()
            ->whereIn('status', ['trial', 'active', 'past_due'])
            ->orderByDesc('id')
            ->first();

        if (! $subscription) {
            return $this->applyOverrides([], $tenant);
        }

        $subscriptionId = (int) $subscription->id;
        $subscriptionUpdatedAt = $subscription->updated_at?->getTimestamp() ?? 0;

        $cacheKey = "tenant:{$tenantId}:entitlements:sub:{$subscriptionId}:{$subscriptionUpdatedAt}";

        if (! $useCache) {
            return $this->resolveFromPlanVersion((int) $subscription->billing_plan_version_id, $tenant);
        }

        return Cache::remember($cacheKey, 60, function () use ($subscription, $tenant) {
            return $this->resolveFromPlanVersion((int) $subscription->billing_plan_version_id, $tenant);
        });
    }

    public function maxUsersForTenant(Tenant $tenant): ?int
    {
        $resolved = $this->resolveForTenant($tenant);

        $limits = is_array($resolved['limits'] ?? null) ? $resolved['limits'] : [];
        $v = $limits['max_users'] ?? null;

        return is_int($v) ? $v : (is_numeric($v) ? (int) $v : null);
    }

    public function featureEnabledForTenant(Tenant $tenant, string $flagCode): bool
    {
        $resolved = $this->resolveForTenant($tenant);

        $flags = is_array($resolved['flags'] ?? null) ? $resolved['flags'] : [];

        return (bool) ($flags[$flagCode] ?? false);
    }

    protected function resolveFromPlanVersion(int $billingPlanVersionId, Tenant $tenant): array
    {
        $rows = PlanEntitlement::query()
            ->where('billing_plan_version_id', $billingPlanVersionId)
            ->with('definition')
            ->get();

        $limits = [];
        $flags = [];

        foreach ($rows as $row) {
            $code = (string) ($row->definition?->code ?? '');

            if ($code === '') {
                continue;
            }

            $type = (string) ($row->definition?->value_type ?? 'json');
            $value = $row->value_json;

            if ($type === 'boolean') {
                $flags[$code] = (bool) $value;
                continue;
            }

            if ($type === 'integer') {
                $limits[$code] = is_int($value) ? $value : (is_numeric($value) ? (int) $value : null);
                continue;
            }

            $limits[$code] = $value;
        }

        return $this->applyOverrides([
            'flags' => $flags,
            'limits' => $limits,
        ], $tenant);
    }

    protected function applyOverrides(array $resolved, Tenant $tenant): array
    {
        $overrides = is_array($tenant->entitlement_overrides) ? $tenant->entitlement_overrides : [];

        $baseFlags = is_array($resolved['flags'] ?? null) ? $resolved['flags'] : [];
        $baseLimits = is_array($resolved['limits'] ?? null) ? $resolved['limits'] : [];

        $overrideFlags = is_array($overrides['flags'] ?? null) ? $overrides['flags'] : [];
        $overrideLimits = is_array($overrides['limits'] ?? null) ? $overrides['limits'] : [];

        $resolved['flags'] = array_merge($baseFlags, $overrideFlags);
        $resolved['limits'] = array_merge($baseLimits, $overrideLimits);

        return $resolved;
    }
}
