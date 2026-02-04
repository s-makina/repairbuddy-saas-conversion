<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenantAndBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RepairBuddyMaintenanceReminderLog extends Model
{
    use HasFactory;
    use BelongsToTenantAndBranch;

    protected $table = 'rb_maintenance_reminder_logs';

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'reminder_id',
        'job_id',
        'customer_id',
        'channel',
        'to_address',
        'status',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'reminder_id' => 'integer',
            'job_id' => 'integer',
            'customer_id' => 'integer',
        ];
    }

    public function reminder(): BelongsTo
    {
        return $this->belongsTo(RepairBuddyMaintenanceReminder::class, 'reminder_id');
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(RepairBuddyJob::class, 'job_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }
}
