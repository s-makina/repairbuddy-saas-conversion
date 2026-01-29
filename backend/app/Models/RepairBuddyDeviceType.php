<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenantAndBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RepairBuddyDeviceType extends Model
{
    use HasFactory;
    use BelongsToTenantAndBranch;

    protected $table = 'rb_device_types';

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function devices(): HasMany
    {
        return $this->hasMany(RepairBuddyDevice::class, 'device_type_id');
    }
}
