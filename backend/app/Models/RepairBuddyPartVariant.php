<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenantAndBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RepairBuddyPartVariant extends Model
{
    use HasFactory;
    use BelongsToTenantAndBranch;

    protected $table = 'rb_part_variants';

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'part_id',
        'name',
        'sku',
        'manufacturing_code',
        'stock_code',
        'price_amount_cents',
        'price_currency',
        'tax_id',
        'warranty',
        'core_features',
        'capacity',
        'installation_charges_amount_cents',
        'installation_charges_currency',
        'installation_message',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'part_id' => 'integer',
            'price_amount_cents' => 'integer',
            'tax_id' => 'integer',
            'installation_charges_amount_cents' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function part(): BelongsTo
    {
        return $this->belongsTo(RepairBuddyPart::class, 'part_id');
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(RepairBuddyTax::class, 'tax_id');
    }
}
