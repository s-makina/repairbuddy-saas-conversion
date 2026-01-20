<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillingPlanVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'billing_plan_id',
        'version',
        'status',
        'locked_at',
        'activated_at',
        'retired_at',
    ];

    protected function casts(): array
    {
        return [
            'locked_at' => 'datetime',
            'activated_at' => 'datetime',
            'retired_at' => 'datetime',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(BillingPlan::class, 'billing_plan_id');
    }

    public function prices(): HasMany
    {
        return $this->hasMany(BillingPrice::class, 'billing_plan_version_id');
    }

    public function entitlements(): HasMany
    {
        return $this->hasMany(PlanEntitlement::class, 'billing_plan_version_id');
    }
}
