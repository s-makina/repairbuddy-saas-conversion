<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenantAndBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
 use Illuminate\Database\Eloquent\Relations\HasMany;

class RepairBuddyJob extends Model
{
    use HasFactory;
    use BelongsToTenantAndBranch;

    protected $table = 'rb_jobs';

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'case_number',
        'plugin_device_post_id',
        'plugin_device_id_text',
        'title',
        'status_slug',
        'payment_status_slug',
        'priority',
        'customer_id',
        'created_by',
        'opened_at',
        'closed_at',
        'pickup_date',
        'delivery_date',
        'next_service_date',
        'case_detail',
        'assigned_technician_id',
    ];

    protected function casts(): array
    {
        return [
            'customer_id' => 'integer',
            'created_by' => 'integer',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'pickup_date' => 'date',
            'delivery_date' => 'date',
            'next_service_date' => 'date',
            'assigned_technician_id' => 'integer',
            'plugin_device_post_id' => 'integer',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedTechnician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_technician_id');
    }

    public function technicians(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'rb_job_technicians', 'job_id', 'technician_user_id')
            ->withPivot(['tenant_id', 'branch_id'])
            ->withTimestamps();
    }

    public function jobDevices(): HasMany
    {
        return $this->hasMany(RepairBuddyJobDevice::class, 'job_id');
    }
}
