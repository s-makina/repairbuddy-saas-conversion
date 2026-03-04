<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenantAndBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RepairBuddyCustomerDevice extends Model
{
    use HasFactory;
    use BelongsToTenantAndBranch;

    protected $table = 'rb_customer_devices';

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'customer_id',
        'device_id',
        'label',
        'serial',
        'pin',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'customer_id' => 'integer',
            'device_id' => 'integer',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(RepairBuddyDevice::class, 'device_id');
    }

    public function jobDevices(): HasMany
    {
        return $this->hasMany(RepairBuddyJobDevice::class, 'customer_device_id');
    }
}
