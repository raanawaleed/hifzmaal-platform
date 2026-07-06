<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class PlatformSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    protected $casts = [
        'value' => 'array',
    ];

    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember(
            "platform_setting.{$key}",
            now()->addHour(),
            fn () => static::where('key', $key)->first()?->value ?? $default
        );
    }

    public static function set(string $key, mixed $value): static
    {
        $setting = static::updateOrCreate(['key' => $key], ['value' => $value]);

        Cache::forget("platform_setting.{$key}");

        return $setting;
    }
}
