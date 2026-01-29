<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenantAndBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RepairBuddyPaymentStatus extends Model
{
    use HasFactory;
    use BelongsToTenantAndBranch;

    protected $table = 'rb_payment_statuses';

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'slug',
        'label',
        'email_template',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
