<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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

        static::created(function (Branch $model) {
            if (! Schema::hasTable('rb_job_statuses') || ! Schema::hasTable('rb_payment_statuses')) {
                return;
            }

            $tenantId = (int) $model->tenant_id;
            $branchId = (int) $model->id;

            if ($tenantId <= 0 || $branchId <= 0) {
                return;
            }

            DB::transaction(function () use ($tenantId, $branchId) {
                $jobDefaults = [
                    ['slug' => 'new', 'label' => 'New Order', 'invoice_label' => 'Invoice'],
                    ['slug' => 'quote', 'label' => 'Quote', 'invoice_label' => 'Quote'],
                    ['slug' => 'cancelled', 'label' => 'Cancelled', 'invoice_label' => 'Cancelled'],
                    ['slug' => 'inprocess', 'label' => 'In Process', 'invoice_label' => 'Work Order'],
                    ['slug' => 'inservice', 'label' => 'In Service', 'invoice_label' => 'Work Order'],
                    ['slug' => 'ready_complete', 'label' => 'Ready/Complete', 'invoice_label' => 'Invoice'],
                    ['slug' => 'delivered', 'label' => 'Delivered', 'invoice_label' => 'Invoice'],
                ];

                $paymentDefaults = [
                    ['slug' => 'nostatus', 'label' => 'No Status'],
                    ['slug' => 'credit', 'label' => 'Credit'],
                    ['slug' => 'paid', 'label' => 'Paid'],
                    ['slug' => 'partial', 'label' => 'Partially Paid'],
                ];

                foreach ($jobDefaults as $s) {
                    DB::table('rb_job_statuses')->updateOrInsert([
                        'tenant_id' => $tenantId,
                        'branch_id' => $branchId,
                        'slug' => $s['slug'],
                    ], [
                        'label' => $s['label'],
                        'email_enabled' => false,
                        'email_template' => null,
                        'sms_enabled' => false,
                        'invoice_label' => $s['invoice_label'],
                        'is_active' => true,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]);
                }

                foreach ($paymentDefaults as $s) {
                    DB::table('rb_payment_statuses')->updateOrInsert([
                        'tenant_id' => $tenantId,
                        'branch_id' => $branchId,
                        'slug' => $s['slug'],
                    ], [
                        'label' => $s['label'],
                        'email_template' => null,
                        'is_active' => true,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]);
                }
            });
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
