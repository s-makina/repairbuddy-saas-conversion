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
        'base_price_amount_cents',
        'base_price_currency',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'service_type_id' => 'integer',
            'base_price_amount_cents' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(RepairBuddyServiceType::class, 'service_type_id');
    }
}
