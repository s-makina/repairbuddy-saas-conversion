<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenantAndBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'manufacturing_code',
        'stock_code',
        'price_amount',
        'price_currency',
        'tax_id',
        'warranty',
        'core_features',
        'capacity',
        'installation_charges_amount',
        'installation_charges_currency',
        'installation_message',
        'stock',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'part_type_id' => 'integer',
            'part_brand_id' => 'integer',
            'price_amount' => 'decimal:2',
            'tax_id' => 'integer',
            'installation_charges_amount' => 'decimal:2',
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

    public function tax(): BelongsTo
    {
        return $this->belongsTo(RepairBuddyTax::class, 'tax_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(RepairBuddyPartVariant::class, 'part_id');
    }

    public function priceOverrides(): HasMany
    {
        return $this->hasMany(RepairBuddyPartPriceOverride::class, 'part_id');
    }
}
