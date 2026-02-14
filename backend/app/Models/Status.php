<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    use HasFactory;
    use BelongsToTenant;

    protected $table = 'statuses';

    protected $fillable = [
        'tenant_id',
        'status_type',
        'label',
        'email_enabled',
        'email_template',
        'sms_enabled',
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
