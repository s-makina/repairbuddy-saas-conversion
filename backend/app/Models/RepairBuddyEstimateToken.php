<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenantAndBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RepairBuddyEstimateToken extends Model
{
    use HasFactory;
    use BelongsToTenantAndBranch;

    protected $table = 'rb_estimate_tokens';

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'estimate_id',
        'purpose',
        'token_hash',
        'expires_at',
        'used_at',
    ];

    protected function casts(): array
    {
        return [
            'estimate_id' => 'integer',
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    public function estimate(): BelongsTo
    {
        return $this->belongsTo(RepairBuddyEstimate::class, 'estimate_id');
    }
}
