<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenantAndBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RepairBuddyJobExtraItem extends Model
{
    use HasFactory;
    use BelongsToTenantAndBranch;

    protected $table = 'rb_job_extra_items';

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'job_id',
        'occurred_at',
        'label',
        'data_text',
        'description',
        'item_type',
        'visibility',
        'meta_json',
    ];

    protected function casts(): array
    {
        return [
            'job_id' => 'integer',
            'occurred_at' => 'datetime',
            'meta_json' => 'array',
        ];
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(RepairBuddyJob::class, 'job_id');
    }
}
