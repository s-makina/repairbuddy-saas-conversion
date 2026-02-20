<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class RepairBuddyDevice extends Model
{
    use HasFactory;
    use BelongsToTenant;

    protected $table = 'rb_devices';

    protected $appends = [
        'image_url',
    ];

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'device_type_id',
        'device_brand_id',
        'parent_device_id',
        'model',
        'image_path',
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

    public function getImageUrlAttribute(): ?string
    {
        if (! is_string($this->image_path) || $this->image_path === '') {
            return null;
        }

        return Storage::disk('public')->url($this->image_path);
    }
}
