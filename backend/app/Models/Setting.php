<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    public static function getPrefixed(string $prefix): array
    {
        return static::where('key', 'like', $prefix . '_%')
            ->pluck('value', 'key')
            ->toArray();
    }

    public static function getBoolean(string $key, bool $default = false): bool
    {
        $value = static::get($key);
        if ($value === null) return $default;
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public static function getStripeSettings(): array
    {
        $settings = static::getPrefixed('stripe');
        return [
            'public_key' => $settings['stripe_public_key'] ?? '',
            'secret_key' => $settings['stripe_secret_key'] ?? '',
            'webhook_secret' => $settings['stripe_webhook_secret'] ?? '',
            'enabled' => filter_var($settings['stripe_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
        ];
    }
}
