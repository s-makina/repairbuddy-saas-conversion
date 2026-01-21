<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Tenant extends Model
{
    use HasFactory;

    protected $appends = [
        'logo_url',
    ];

    protected $fillable = [
        'name',
        'slug',
        'status',
        'contact_email',
        'contact_phone',
        'plan_id',
        'entitlement_overrides',
        'currency',
        'billing_country',
        'billing_vat_number',
        'billing_address_json',
        'timezone',
        'language',
        'brand_color',
        'logo_path',
        'setup_completed_at',
        'setup_step',
        'setup_state',
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
            'setup_completed_at' => 'datetime',
            'activated_at' => 'datetime',
            'suspended_at' => 'datetime',
            'closed_at' => 'datetime',
            'data_retention_days' => 'integer',
            'entitlement_overrides' => 'array',
            'billing_address_json' => 'array',
            'setup_state' => 'array',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function getLogoUrlAttribute(): ?string
    {
        if (! is_string($this->logo_path) || $this->logo_path === '') {
            return null;
        }

        return Storage::disk('public')->url($this->logo_path);
    }
}
