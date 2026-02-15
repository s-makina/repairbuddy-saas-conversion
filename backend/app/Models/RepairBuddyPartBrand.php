<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenantAndBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class RepairBuddyPartBrand extends Model
{
    use HasFactory;
    use BelongsToTenantAndBranch;

    protected $table = 'rb_part_brands';

    protected $appends = [
        'image_url',
    ];

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'name',
        'description',
        'parent_id',
        'image_path',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'parent_id' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function parts(): HasMany
    {
        return $this->hasMany(RepairBuddyPart::class, 'part_brand_id');
    }

    public function getImageUrlAttribute(): ?string
    {
        if (! is_string($this->image_path) || $this->image_path === '') {
            return null;
        }

        return Storage::disk('public')->url($this->image_path);
    }
}
