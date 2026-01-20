<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaxProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'country_code',
        'name',
        'is_vat',
    ];

    protected function casts(): array
    {
        return [
            'is_vat' => 'boolean',
        ];
    }

    public function rates(): HasMany
    {
        return $this->hasMany(TaxRate::class);
    }
}
