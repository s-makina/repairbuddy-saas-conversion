<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenantAndBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RepairBuddyJobStatus extends Model
{
    use HasFactory;
    use BelongsToTenantAndBranch;

    protected $table = 'rb_job_statuses';

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'slug',
        'label',
        'email_enabled',
        'email_template',
        'sms_enabled',
        'invoice_label',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'email_enabled' => 'boolean',
            'sms_enabled' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
