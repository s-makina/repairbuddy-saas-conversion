<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenantAndBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RepairBuddyService extends Model
{
    use HasFactory;
    use BelongsToTenantAndBranch;

    protected $table = 'rb_services';

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'service_type_id',
        'name',
        'description',
        'service_code',
        'time_required',
        'warranty',
        'pick_up_delivery_available',
        'laptop_rental_available',
        'base_price_amount_cents',
        'base_price_currency',
        'tax_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'service_type_id' => 'integer',
            'base_price_amount_cents' => 'integer',
            'tax_id' => 'integer',
            'pick_up_delivery_available' => 'boolean',
            'laptop_rental_available' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(RepairBuddyServiceType::class, 'service_type_id');
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(RepairBuddyTax::class, 'tax_id');
    }
}
