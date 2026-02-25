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

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /* ── Helpers ── */

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
        return route('tenant.signature.generator', [
            'business' => $tenantSlug,
            'jobId'    => $this->job_id,
            'signatureId' => $this->id,
        ]);
    }
}
