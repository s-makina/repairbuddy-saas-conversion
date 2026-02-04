<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenantAndBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RepairBuddyMaintenanceReminder extends Model
{
    use HasFactory;
    use BelongsToTenantAndBranch;

    protected $table = 'rb_maintenance_reminders';

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'name',
        'description',
        'interval_days',
        'device_type_id',
        'device_brand_id',
        'email_enabled',
        'sms_enabled',
        'reminder_enabled',
        'email_body',
        'sms_body',
        'last_executed_at',
        'created_by_user_id',
        'updated_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'interval_days' => 'integer',
            'device_type_id' => 'integer',
            'device_brand_id' => 'integer',
            'email_enabled' => 'boolean',
            'sms_enabled' => 'boolean',
            'reminder_enabled' => 'boolean',
            'last_executed_at' => 'datetime',
            'created_by_user_id' => 'integer',
            'updated_by_user_id' => 'integer',
        ];
    }

    public function deviceType(): BelongsTo
    {
        return $this->belongsTo(RepairBuddyDeviceType::class, 'device_type_id');
    }

    public function deviceBrand(): BelongsTo
    {
        return $this->belongsTo(RepairBuddyDeviceBrand::class, 'device_brand_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(RepairBuddyMaintenanceReminderLog::class, 'reminder_id');
    }
}
