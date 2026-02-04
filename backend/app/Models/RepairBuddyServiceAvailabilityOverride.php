<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenantAndBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RepairBuddyServiceAvailabilityOverride extends Model
{
    use HasFactory;
    use BelongsToTenantAndBranch;

    protected $table = 'rb_service_availability_overrides';

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'service_id',
        'scope_type',
        'scope_ref_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'service_id' => 'integer',
            'scope_ref_id' => 'integer',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(RepairBuddyService::class, 'service_id');
    }
}
