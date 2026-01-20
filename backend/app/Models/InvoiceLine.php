<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'description',
        'quantity',
        'unit_amount_cents',
        'subtotal_cents',
        'tax_rate_percent',
        'tax_cents',
        'total_cents',
        'tax_meta_json',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_amount_cents' => 'integer',
            'subtotal_cents' => 'integer',
            'tax_rate_percent' => 'decimal:2',
            'tax_cents' => 'integer',
            'total_cents' => 'integer',
            'tax_meta_json' => 'array',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
