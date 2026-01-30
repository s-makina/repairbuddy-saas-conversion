<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenantAndBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RepairBuddyPartPriceOverride extends Model
{
    use HasFactory;
    use BelongsToTenantAndBranch;

    protected $table = 'rb_part_price_overrides';

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'part_id',
        'part_variant_id',
        'scope_type',
        'scope_ref_id',
        'price_amount_cents',
        'price_currency',
        'tax_id',
        'manufacturing_code',
        'stock_code',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'part_id' => 'integer',
            'part_variant_id' => 'integer',
            'scope_ref_id' => 'integer',
            'price_amount_cents' => 'integer',
            'tax_id' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function part(): BelongsTo
    {
        return $this->belongsTo(RepairBuddyPart::class, 'part_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(RepairBuddyPartVariant::class, 'part_variant_id');
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(RepairBuddyTax::class, 'tax_id');
    }
}
