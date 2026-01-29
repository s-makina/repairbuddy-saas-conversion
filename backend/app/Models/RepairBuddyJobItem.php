<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenantAndBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RepairBuddyJobItem extends Model
{
    use HasFactory;
    use BelongsToTenantAndBranch;

    protected $table = 'rb_job_items';

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'job_id',
        'item_type',
        'ref_id',
        'name_snapshot',
        'qty',
        'unit_price_amount_cents',
        'unit_price_currency',
        'tax_id',
        'meta_json',
    ];

    protected function casts(): array
    {
        return [
            'job_id' => 'integer',
            'ref_id' => 'integer',
            'qty' => 'integer',
            'unit_price_amount_cents' => 'integer',
            'tax_id' => 'integer',
            'meta_json' => 'array',
        ];
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(RepairBuddyJob::class, 'job_id');
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(RepairBuddyTax::class, 'tax_id');
    }
}
