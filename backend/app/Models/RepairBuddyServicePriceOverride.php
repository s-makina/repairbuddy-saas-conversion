<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenantAndBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RepairBuddyServicePriceOverride extends Model
{
    use HasFactory;
    use BelongsToTenantAndBranch;

    protected $table = 'rb_service_price_overrides';

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'service_id',
        'scope_type',
        'scope_ref_id',
        'price_amount',
        'price_currency',
        'tax_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'service_id' => 'integer',
            'scope_ref_id' => 'integer',
            'price_amount' => 'decimal:2',
            'tax_id' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(RepairBuddyService::class, 'service_id');
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(RepairBuddyTax::class, 'tax_id');
    }
}
