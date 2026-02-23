<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenantAndBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RepairBuddyEstimate extends Model
{
    use HasFactory;
    use BelongsToTenantAndBranch;

    protected $table = 'rb_estimates';

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'case_number',
        'title',
        'status',
        'customer_id',
        'created_by',
        'pickup_date',
        'delivery_date',
        'case_detail',
        'assigned_technician_id',
        'sent_at',
        'approved_at',
        'rejected_at',
        'converted_job_id',
    ];

    protected function casts(): array
    {
        return [
            'customer_id' => 'integer',
            'created_by' => 'integer',
            'pickup_date' => 'date',
            'delivery_date' => 'date',
            'assigned_technician_id' => 'integer',
            'sent_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'converted_job_id' => 'integer',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedTechnician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_technician_id');
    }

    public function convertedJob(): BelongsTo
    {
        return $this->belongsTo(RepairBuddyJob::class, 'converted_job_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(RepairBuddyEstimateItem::class, 'estimate_id');
    }

    public function devices(): HasMany
    {
        return $this->hasMany(RepairBuddyEstimateDevice::class, 'estimate_id');
    }

    public function tokens(): HasMany
    {
        return $this->hasMany(RepairBuddyEstimateToken::class, 'estimate_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(RepairBuddyEstimateAttachment::class, 'estimate_id');
    }
}
