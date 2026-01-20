<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'status',
        'contact_email',
        'plan_id',
        'entitlement_overrides',
        'currency',
        'billing_country',
        'billing_vat_number',
        'billing_address_json',
        'activated_at',
        'suspended_at',
        'suspension_reason',
        'closed_at',
        'closed_reason',
        'data_retention_days',
    ];

    protected function casts(): array
    {
        return [
            'activated_at' => 'datetime',
            'suspended_at' => 'datetime',
            'closed_at' => 'datetime',
            'data_retention_days' => 'integer',
            'entitlement_overrides' => 'array',
            'billing_address_json' => 'array',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
