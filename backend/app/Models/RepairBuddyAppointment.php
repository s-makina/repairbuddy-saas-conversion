<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenantAndBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RepairBuddyAppointment extends Model
{
    use HasFactory;
    use BelongsToTenantAndBranch;
    use SoftDeletes;

    protected $table = 'rb_appointments';

    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_NO_SHOW = 'no_show';

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'appointment_setting_id',
        'job_id',
        'estimate_id',
        'customer_id',
        'technician_id',
        'title',
        'appointment_date',
        'time_slot_start',
        'time_slot_end',
        'status',
        'notes',
        'cancellation_reason',
        'reminder_sent_at',
        'confirmed_at',
        'cancelled_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'appointment_date' => 'date',
            'time_slot_start' => 'datetime:H:i:s',
            'time_slot_end' => 'datetime:H:i:s',
            'reminder_sent_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function appointmentSetting(): BelongsTo
    {
        return $this->belongsTo(RepairBuddyAppointmentSetting::class, 'appointment_setting_id');
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(RepairBuddyJob::class, 'job_id');
    }

    public function estimate(): BelongsTo
    {
        return $this->belongsTo(RepairBuddyEstimate::class, 'estimate_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function isStandalone(): bool
    {
        return $this->job_id === null && $this->estimate_id === null;
    }

    public function isLinked(): bool
    {
        return ! $this->isStandalone();
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED);
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', self::STATUS_CONFIRMED);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('appointment_date', '>=', now()->toDateString());
    }

    public function scopeOnDate($query, string $date)
    {
        return $query->where('appointment_date', $date);
    }

    public function confirm(): void
    {
        $this->update([
            'status' => self::STATUS_CONFIRMED,
            'confirmed_at' => now(),
        ]);
    }

    public function cancel(?string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'cancellation_reason' => $reason,
            'cancelled_at' => now(),
        ]);
    }

    public function markNoShow(): void
    {
        $this->update([
            'status' => self::STATUS_NO_SHOW,
        ]);
    }

    public function complete(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
        ]);
    }

    public function getTimeSlotDisplayAttribute(): string
    {
        return $this->time_slot_start->format('H:i') . ' - ' . $this->time_slot_end->format('H:i');
    }
}
