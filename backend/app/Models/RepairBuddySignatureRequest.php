<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenantAndBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RepairBuddySignatureRequest extends Model
{
    use HasFactory;
    use BelongsToTenantAndBranch;

    protected $table = 'rb_signature_requests';

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'job_id',
        'estimate_id',
        'signature_type',
        'signature_label',
        'verification_code',
        'generated_at',
        'expires_at',
        'status',
        'completed_at',
        'completed_ip',
        'completed_user_agent',
        'signature_file_path',
        'generated_by',
    ];

    protected function casts(): array
    {
        return [
            'job_id'       => 'integer',
            'estimate_id'  => 'integer',
            'generated_by' => 'integer',
            'generated_at' => 'datetime',
            'expires_at'   => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /* ── Relationships ── */

    public function job(): BelongsTo
    {
        return $this->belongsTo(RepairBuddyJob::class, 'job_id');
    }

    public function estimate(): BelongsTo
    {
        return $this->belongsTo(RepairBuddyEstimate::class, 'estimate_id');
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /* ── Helpers ── */

    /**
     * Get the entity this signature request belongs to (job or estimate).
     */
    public function getSignableEntity(): Model|null
    {
        if ($this->job_id) {
            return $this->job;
        }
        if ($this->estimate_id) {
            return $this->estimate;
        }
        return null;
    }

    /**
     * Check if this signature request is for an estimate.
     */
    public function isForEstimate(): bool
    {
        return $this->estimate_id !== null;
    }

    /**
     * Check if this signature request is for a job.
     */
    public function isForJob(): bool
    {
        return $this->job_id !== null;
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isExpired(): bool
    {
        if ($this->status === 'expired') {
            return true;
        }
        if ($this->expires_at && $this->expires_at->isPast()) {
            return true;
        }
        return false;
    }

    /**
     * Get the public URL for the customer to sign.
     */
    public function getSignatureUrl(string $tenantSlug): string
    {
        return route('tenant.signature.request', [
            'business'     => $tenantSlug,
            'verification' => $this->verification_code,
        ]);
    }

    /**
     * Get the generator page URL (for staff to generate & copy the link).
     */
    public function getGeneratorUrl(string $tenantSlug): string
    {
        if ($this->isForEstimate()) {
            return route('tenant.estimates.signatures.generator', [
                'business'    => $tenantSlug,
                'estimateId'  => $this->estimate_id,
                'signatureId' => $this->id,
            ]);
        }

        return route('tenant.signatures.generator', [
            'business'    => $tenantSlug,
            'jobId'       => $this->job_id,
            'signatureId' => $this->id,
        ]);
    }

    /**
     * Get the index URL for listing signature requests.
     */
    public function getIndexUrl(string $tenantSlug): string
    {
        if ($this->isForEstimate()) {
            return route('tenant.estimates.signatures.index', [
                'business'   => $tenantSlug,
                'estimateId' => $this->estimate_id,
            ]);
        }

        return route('tenant.signatures.index', [
            'business' => $tenantSlug,
            'jobId'    => $this->job_id,
        ]);
    }
}
