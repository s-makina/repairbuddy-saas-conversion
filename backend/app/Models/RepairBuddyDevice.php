<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenantAndBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RepairBuddyDevice extends Model
{
    use HasFactory;
    use BelongsToTenantAndBranch;

    protected $table = 'rb_devices';

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'device_type_id',
        'device_brand_id',
        'parent_device_id',
        'model',
        'disable_in_booking_form',
        'is_other',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'device_type_id' => 'integer',
            'device_brand_id' => 'integer',
            'parent_device_id' => 'integer',
            'disable_in_booking_form' => 'boolean',
            'is_other' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(RepairBuddyDeviceType::class, 'device_type_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(RepairBuddyDeviceBrand::class, 'device_brand_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_device_id');
    }
}
