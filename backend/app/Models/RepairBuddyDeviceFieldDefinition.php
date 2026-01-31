<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenantAndBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RepairBuddyDeviceFieldDefinition extends Model
{
    use HasFactory;
    use BelongsToTenantAndBranch;

    protected $table = 'rb_device_field_definitions';

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'key',
        'label',
        'type',
        'show_in_booking',
        'show_in_invoice',
        'show_in_portal',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'show_in_booking' => 'boolean',
            'show_in_invoice' => 'boolean',
            'show_in_portal' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
