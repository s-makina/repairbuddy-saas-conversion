<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenantAndBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RepairBuddyPartType extends Model
{
    use HasFactory;
    use BelongsToTenantAndBranch;

    protected $table = 'rb_part_types';

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

    public function parts(): HasMany
    {
        return $this->hasMany(RepairBuddyPart::class, 'part_type_id');
    }
}
