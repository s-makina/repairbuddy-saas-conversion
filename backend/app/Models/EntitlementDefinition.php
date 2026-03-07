<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EntitlementDefinition extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'value_type',
        'description',
        'is_premium',
    ];

    protected function casts(): array
    {
        return [
            'is_premium' => 'boolean',
        ];
    }

    public function planEntitlements(): HasMany
    {
        return $this->hasMany(PlanEntitlement::class);
    }
}
