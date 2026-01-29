<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenantAndBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RepairBuddyPartBrand extends Model
{
    use HasFactory;
    use BelongsToTenantAndBranch;

    protected $table = 'rb_part_brands';

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'name',
        'image_path',
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
        return $this->hasMany(RepairBuddyPart::class, 'part_brand_id');
    }
}
