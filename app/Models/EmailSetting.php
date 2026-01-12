<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class EmailSetting extends Model
{
    protected $guarded = ['id'];

    /**
     * Get a setting value by key.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("email_setting_{$key}", 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();

            return $setting ? $setting->value : $default;
        });
    }

    /**
     * Set a setting value.
     */
    public static function set(string $key, mixed $value, string $type = 'text', string $group = 'general'): static
    {
        Cache::forget("email_setting_{$key}");

        return static::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'type' => $type, 'group' => $group]
        );
    }

    /**
     * Get all settings grouped by group.
     */
    public static function getAllGrouped(): \Illuminate\Support\Collection
    {
        return static::all()->groupBy('group');
    }

    /**
     * Clear all cached settings.
     */
    public static function clearCache(): void
    {
        $keys = static::pluck('key');

        foreach ($keys as $key) {
            Cache::forget("email_setting_{$key}");
        }
    }
}
