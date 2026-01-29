<?php

namespace App\Models\Concerns;

use App\Models\Scopes\TenantScope;
use App\Support\TenantContext;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope());

        static::creating(function ($model) {
            $tenantId = TenantContext::tenantId();

            if (! $tenantId) {
                throw new \RuntimeException('Tenant context is missing.');
            }

            if (! isset($model->tenant_id) || ! $model->tenant_id) {
                $model->tenant_id = $tenantId;
            }
        });
    }
}
