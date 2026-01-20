<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanEntitlement extends Model
{
    use HasFactory;

    protected $fillable = [
        'billing_plan_version_id',
        'entitlement_definition_id',
        'value_json',
    ];

    protected function casts(): array
    {
        return [
            'value_json' => 'json',
        ];
    }

    public function planVersion(): BelongsTo
    {
        return $this->belongsTo(BillingPlanVersion::class, 'billing_plan_version_id');
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(EntitlementDefinition::class, 'entitlement_definition_id');
    }
}
