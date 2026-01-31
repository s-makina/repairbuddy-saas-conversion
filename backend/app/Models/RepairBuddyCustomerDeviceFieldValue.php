<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenantAndBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RepairBuddyCustomerDeviceFieldValue extends Model
{
    use HasFactory;
    use BelongsToTenantAndBranch;

    protected $table = 'rb_customer_device_field_values';

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'customer_device_id',
        'field_definition_id',
        'value_text',
    ];

    protected function casts(): array
    {
        return [
            'customer_device_id' => 'integer',
            'field_definition_id' => 'integer',
        ];
    }

    public function customerDevice(): BelongsTo
    {
        return $this->belongsTo(RepairBuddyCustomerDevice::class, 'customer_device_id');
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(RepairBuddyDeviceFieldDefinition::class, 'field_definition_id');
    }
}
