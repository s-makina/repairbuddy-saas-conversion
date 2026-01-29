<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenantAndBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RepairBuddyPart extends Model
{
    use HasFactory;
    use BelongsToTenantAndBranch;

    protected $table = 'rb_parts';

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'part_type_id',
        'part_brand_id',
        'name',
        'sku',
        'price_amount_cents',
        'price_currency',
        'stock',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'part_type_id' => 'integer',
            'part_brand_id' => 'integer',
            'price_amount_cents' => 'integer',
            'stock' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(RepairBuddyPartType::class, 'part_type_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(RepairBuddyPartBrand::class, 'part_brand_id');
    }
}
