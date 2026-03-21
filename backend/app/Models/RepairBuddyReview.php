<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenantAndBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RepairBuddyReview extends Model
{
    use HasFactory;
    use BelongsToTenantAndBranch;

    protected $table = 'rb_reviews';

    protected $fillable = [
        'tenant_id',
        'job_id',
        'customer_id',
        'rating',
        'feedback',
        'review_summary',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'is_published' => 'boolean',
        ];
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(RepairBuddyJob::class, 'job_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * Get star rating HTML representation.
     */
    public function getStarsHtmlAttribute(): string
    {
        $rating = min(5, max(1, (int) $this->rating));
        $html = '<span class="cd-stars">';

        for ($i = 1; $i <= 5; $i++) {
            if ($i <= $rating) {
                $html .= '<i class="bi bi-star-fill"></i>';
            } else {
                $html .= '<i class="bi bi-star"></i>';
            }
        }

        $html .= '</span>';
        return $html;
    }
}
