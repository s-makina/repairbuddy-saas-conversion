<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenantAndBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RepairBuddyServiceType extends Model
{
    use HasFactory;
    use BelongsToTenantAndBranch;

    protected $table = 'rb_service_types';

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

    public function services(): HasMany
    {
        return $this->hasMany(RepairBuddyService::class, 'service_type_id');
    }
}
