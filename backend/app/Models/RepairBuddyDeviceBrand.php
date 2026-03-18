<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RepairBuddyDeviceBrand extends Model
{
    use HasFactory;
    use BelongsToTenant;

    protected $table = 'rb_device_brands';

    protected $appends = [
        'image_url',
    ];

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'parent_id',
        'name',
        'description',
        'image_path',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
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

    public function devices(): HasMany
    {
        return $this->hasMany(RepairBuddyDevice::class, 'device_brand_id');
    }

    public function getImageUrlAttribute(): ?string
    {
        if (! is_string($this->image_path) || $this->image_path === '') {
            return null;
        }

        $url = Storage::disk('public')->url($this->image_path);
        $request = request();

        if (Str::startsWith($url, ['http://', 'https://', '//'])) {
            if ($request !== null) {
                $requestOrigin = rtrim($request->getSchemeAndHttpHost(), '/');
                $parsedUrl = parse_url($url);
                $parsedRequest = parse_url($requestOrigin);
                $urlPath = is_string($parsedUrl['path'] ?? null) ? $parsedUrl['path'] : null;
                $urlHost = strtolower((string) ($parsedUrl['host'] ?? ''));
                $requestHost = strtolower((string) ($parsedRequest['host'] ?? ''));

                if ($urlPath !== null && $urlPath !== '' && $urlHost !== '' && $requestHost !== '' && $urlHost !== $requestHost) {
                    return $requestOrigin.'/'.$this->normalizeRelativePath($urlPath);
                }
            }

            return $url;
        }

        if ($request !== null) {
            return rtrim($request->getSchemeAndHttpHost(), '/').'/'.$this->normalizeRelativePath($url);
        }

        return asset($this->normalizeRelativePath($url));
    }

    protected function normalizeRelativePath(string $path): string
    {
        return ltrim($path, '/');
    }
}
