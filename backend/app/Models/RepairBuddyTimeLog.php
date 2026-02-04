<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenantAndBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RepairBuddyTimeLog extends Model
{
    use HasFactory;
    use BelongsToTenantAndBranch;

    protected $table = 'rb_time_logs';

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'job_id',
        'technician_id',
        'start_time',
        'end_time',
        'time_type',
        'activity',
        'priority',
        'work_description',
        'device_data_json',
        'log_state',
        'total_minutes',
        'hourly_rate_cents',
        'hourly_cost_cents',
        'currency',
        'is_billable',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'billed_job_item_id',
    ];

    protected function casts(): array
    {
        return [
            'job_id' => 'integer',
            'technician_id' => 'integer',
            'approved_by' => 'integer',
            'billed_job_item_id' => 'integer',
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'approved_at' => 'datetime',
            'total_minutes' => 'integer',
            'hourly_rate_cents' => 'integer',
            'hourly_cost_cents' => 'integer',
            'device_data_json' => 'array',
            'is_billable' => 'boolean',
        ];
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(RepairBuddyJob::class, 'job_id');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function billedJobItem(): BelongsTo
    {
        return $this->belongsTo(RepairBuddyJobItem::class, 'billed_job_item_id');
    }
}
