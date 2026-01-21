<?php

namespace App\Support;

use App\Models\PlatformSetting;

class PlatformSettings
{
    public static function getArray(string $key, array $default = []): array
    {
        $row = PlatformSetting::query()->where('key', $key)->first();

        $value = $row?->value_json;
        if (! is_array($value)) {
            return $default;
        }

        return $value;
    }

    public static function setArray(string $key, array $value): PlatformSetting
    {
        return PlatformSetting::query()->updateOrCreate(
            ['key' => $key],
            ['value_json' => $value],
        );
    }
}
