<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchInvoiceCounter extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'year',
        'next_number',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'next_number' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope());

        static::creating(function (BranchInvoiceCounter $model) {
            $tenantId = TenantContext::tenantId();

            if (! $tenantId) {
                throw new \RuntimeException('Tenant context is missing.');
            }

            $model->tenant_id = $tenantId;
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
