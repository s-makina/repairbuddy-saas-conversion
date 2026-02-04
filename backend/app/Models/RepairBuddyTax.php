<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenantAndBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RepairBuddyTax extends Model
{
    use HasFactory;
    use BelongsToTenantAndBranch;

    protected $table = 'rb_taxes';

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'name',
        'rate',
        'is_default',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:3',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
