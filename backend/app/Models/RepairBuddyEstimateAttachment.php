<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenantAndBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RepairBuddyEstimateAttachment extends Model
{
    use HasFactory;
    use BelongsToTenantAndBranch;

    protected $table = 'rb_estimate_attachments';

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'estimate_id',
        'uploader_user_id',
        'visibility',
        'original_filename',
        'mime_type',
        'size_bytes',
        'storage_disk',
        'storage_path',
        'url',
    ];

    protected function casts(): array
    {
        return [
            'estimate_id' => 'integer',
            'uploader_user_id' => 'integer',
            'size_bytes' => 'integer',
        ];
    }

    public function estimate(): BelongsTo
    {
        return $this->belongsTo(RepairBuddyEstimate::class, 'estimate_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploader_user_id');
    }
}
