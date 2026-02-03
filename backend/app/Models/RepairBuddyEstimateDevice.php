<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenantAndBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RepairBuddyEstimateDevice extends Model
{
    use HasFactory;
    use BelongsToTenantAndBranch;

    protected $table = 'rb_estimate_devices';

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'estimate_id',
        'customer_device_id',
        'label_snapshot',
        'serial_snapshot',
        'pin_snapshot',
        'notes_snapshot',
        'extra_fields_snapshot_json',
    ];

    protected function casts(): array
    {
        return [
            'estimate_id' => 'integer',
            'customer_device_id' => 'integer',
            'extra_fields_snapshot_json' => 'array',
        ];
    }

    public function estimate(): BelongsTo
    {
        return $this->belongsTo(RepairBuddyEstimate::class, 'estimate_id');
    }

    public function customerDevice(): BelongsTo
    {
        return $this->belongsTo(RepairBuddyCustomerDevice::class, 'customer_device_id');
    }
}
