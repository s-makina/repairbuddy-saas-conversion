<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RepairBuddyPaymentStatus extends Model
{
    use HasFactory;
    use BelongsToTenant;

    protected $table = 'rb_payment_statuses';

    protected $fillable = [
        'tenant_id',
        'slug',
        'label',
        'description',
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
