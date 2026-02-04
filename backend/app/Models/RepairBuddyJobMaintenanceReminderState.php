<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenantAndBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RepairBuddyJobMaintenanceReminderState extends Model
{
    use HasFactory;
    use BelongsToTenantAndBranch;

    protected $table = 'rb_job_maintenance_reminder_state';

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'job_id',
        'reminder_id',
        'last_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'job_id' => 'integer',
            'reminder_id' => 'integer',
            'last_sent_at' => 'datetime',
        ];
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(RepairBuddyJob::class, 'job_id');
    }

    public function reminder(): BelongsTo
    {
        return $this->belongsTo(RepairBuddyMaintenanceReminder::class, 'reminder_id');
    }
}
