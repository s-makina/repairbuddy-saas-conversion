<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillingInterval extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'months',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'months' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function prices(): HasMany
    {
        return $this->hasMany(BillingPrice::class, 'billing_interval_id');
    }
}
