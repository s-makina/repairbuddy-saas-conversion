<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'phone',
        'email',
        'address_line1',
        'address_line2',
        'address_city',
        'address_state',
        'address_postal_code',
        'address_country',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope());

        static::creating(function (Branch $model) {
            $tenantId = TenantContext::tenantId();

            if (! $tenantId) {
                throw new \RuntimeException('Tenant context is missing.');
            }

            $model->tenant_id = $tenantId;

            if (is_string($model->code) && $model->code !== '') {
                $model->code = strtoupper($model->code);
            }

            if (is_string($model->address_country) && $model->address_country !== '') {
                $model->address_country = strtoupper($model->address_country);
            }
        });

        static::updating(function (Branch $model) {
            if (is_string($model->code) && $model->code !== '') {
                $model->code = strtoupper($model->code);
            }

            if (is_string($model->address_country) && $model->address_country !== '') {
                $model->address_country = strtoupper($model->address_country);
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'branch_user');
    }
}
