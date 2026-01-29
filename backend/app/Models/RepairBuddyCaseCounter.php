<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenantAndBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RepairBuddyCaseCounter extends Model
{
    use HasFactory;
    use BelongsToTenantAndBranch;

    protected $table = 'rb_case_counters';

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'next_number',
    ];

    protected function casts(): array
    {
        return [
            'next_number' => 'integer',
        ];
    }
}
