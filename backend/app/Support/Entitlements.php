<?php

namespace App\Support;

use App\Models\Tenant;

class Entitlements
{
    public static function forTenant(Tenant $tenant): array
    {
        $tenant->loadMissing('plan');

        $base = is_array($tenant->plan?->entitlements) ? $tenant->plan->entitlements : [];
        $overrides = is_array($tenant->entitlement_overrides) ? $tenant->entitlement_overrides : [];

        return self::merge($base, $overrides);
    }

    public static function merge(array $base, array $overrides): array
    {
        $result = $base;

        $baseFlags = is_array($base['flags'] ?? null) ? $base['flags'] : [];
        $baseLimits = is_array($base['limits'] ?? null) ? $base['limits'] : [];

        $overrideFlags = is_array($overrides['flags'] ?? null) ? $overrides['flags'] : [];
        $overrideLimits = is_array($overrides['limits'] ?? null) ? $overrides['limits'] : [];

        $result['flags'] = array_merge($baseFlags, $overrideFlags);
        $result['limits'] = array_merge($baseLimits, $overrideLimits);

        return $result;
    }
}
