<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'price_display',
        'billing_interval',
        'entitlements',
    ];

    protected function casts(): array
    {
        return [
            'entitlements' => 'array',
        ];
    }
}
