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
        'model',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'device_type_id' => 'integer',
            'device_brand_id' => 'integer',
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
}
