<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenantAndBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RepairBuddyEvent extends Model
{
    use HasFactory;
    use BelongsToTenantAndBranch;

    protected $table = 'rb_events';

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'actor_user_id',
        'entity_type',
        'entity_id',
        'visibility',
        'event_type',
        'payload_json',
    ];

    protected function casts(): array
    {
        return [
            'actor_user_id' => 'integer',
            'entity_id' => 'integer',
            'payload_json' => 'array',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
