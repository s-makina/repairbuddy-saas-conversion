<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenantAndBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RepairBuddyPayment extends Model
{
    use HasFactory;
    use BelongsToTenantAndBranch;

    protected $table = 'rb_payments';

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'job_id',
        'received_by',
        'method',
        'payment_status',
        'transaction_id',
        'amount_cents',
        'currency',
        'notes',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'paid_at'      => 'datetime',
        ];
    }

    /* ── Relationships ─────────────────────────────── */

    public function job(): BelongsTo
    {
        return $this->belongsTo(RepairBuddyJob::class, 'job_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
