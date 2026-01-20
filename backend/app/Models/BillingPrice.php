<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'billing_plan_version_id',
        'currency',
        'interval',
        'amount_cents',
        'trial_days',
        'is_default',
        'default_for_currency_interval',
    ];

    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'trial_days' => 'integer',
            'is_default' => 'boolean',
        ];
    }

    public function planVersion(): BelongsTo
    {
        return $this->belongsTo(BillingPlanVersion::class, 'billing_plan_version_id');
    }
}
