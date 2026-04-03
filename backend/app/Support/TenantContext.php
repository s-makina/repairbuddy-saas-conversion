<?php

namespace App\Support;

use App\Models\Tenant;

class TenantContext
{
    private static ?Tenant $tenant = null;

    public static function set(?Tenant $tenant): void
    {
        self::$tenant = $tenant;
    }

    public static function tenant(): ?Tenant
    {
        return self::$tenant;
    }

    public static function tenantId(): ?int
    {
        return self::$tenant?->id;
    }

    /**
     * Get tenant by slug or fail with 404.
     */
    public static function getTenantOrFail(string $slug): Tenant
    {
        $tenant = Tenant::where('slug', $slug)->first();

        if (! $tenant) {
            abort(404, "Tenant not found: {$slug}");
        }

        // Set as current tenant context
        self::set($tenant);

        return $tenant;
    }
}
