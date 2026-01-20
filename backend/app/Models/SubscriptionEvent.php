<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'tenant_subscription_id',
        'event_type',
        'payload_json',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'payload_json' => 'json',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope());

        static::creating(function (SubscriptionEvent $model) {
            $tenantId = TenantContext::tenantId();

            if (! $tenantId) {
                throw new \RuntimeException('Tenant context is missing.');
            }

            $model->tenant_id = $tenantId;
        });
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(TenantSubscription::class, 'tenant_subscription_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
