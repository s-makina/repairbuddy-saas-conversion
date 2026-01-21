<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantSecuritySetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'mfa_required_roles',
        'mfa_grace_period_days',
        'mfa_enforce_after',
        'session_idle_timeout_minutes',
        'session_max_lifetime_days',
        'lockout_max_attempts',
        'lockout_duration_minutes',
    ];

    protected function casts(): array
    {
        return [
            'mfa_required_roles' => 'array',
            'mfa_grace_period_days' => 'integer',
            'mfa_enforce_after' => 'datetime',
            'session_idle_timeout_minutes' => 'integer',
            'session_max_lifetime_days' => 'integer',
            'lockout_max_attempts' => 'integer',
            'lockout_duration_minutes' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
