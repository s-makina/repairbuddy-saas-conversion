<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenantAndBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RepairBuddyAppointmentSetting extends Model
{
    use HasFactory;
    use BelongsToTenantAndBranch;

    protected $table = 'rb_appointment_settings';

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'title',
        'description',
        'is_enabled',
        'slot_duration_minutes',
        'buffer_minutes',
        'max_appointments_per_day',
        'time_slots',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'slot_duration_minutes' => 'integer',
            'buffer_minutes' => 'integer',
            'max_appointments_per_day' => 'integer',
            'time_slots' => 'array',
        ];
    }
}
