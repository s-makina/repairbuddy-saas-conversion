<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TenantStatusOverride extends Model
{
    use HasFactory;
    use BelongsToTenant;

    protected $table = 'tenant_status_overrides';

    protected $fillable = [
        'tenant_id',
        'domain',
        'code',
        'label',
        'color',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }
}
