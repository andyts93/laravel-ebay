<?php
namespace Andyts93\LaravelEbay\Models;

use Illuminate\Support\Facades\Cache;

class EbaySetting extends \Illuminate\Database\Eloquent\Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
        'is_public'
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    public function getValueAttribute($value)
    {
        return $this->castValue($value, $this->type);
    }

    public function setValueAttribute($value)
    {
        $this->attributes['value'] = $this->prepareValueForStorage($value, $this->type);
    }

    private function castValue($value, $type)
    {
        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int)$value,
            'float' => (float)$value,
            'json' => json_decode($value),
            default => $value,
        };
    }

    private function prepareValueForStorage($value, $type): bool|string
    {
        return match ($type) {
            'boolean' => $value ? 'true' : 'false',
            'json' => is_string($value) ? $value : json_encode($value),
            default => (string)$value,
        };
    }

    public static function get($key, $default = null)
    {
        return Cache::remember("ebay_settings.{$key}", 3600, function() use ($key, $default) {
            $setting = static::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    public static function set($key, $value, $type = 'string')
    {
        $setting = static::firstOrNew(['key' => $key]);
        if ($setting->value != $value) {
            $setting->value = $value;
            $setting->type = $type;
            $setting->save();

            Cache::forget("ebay_settings.{$key}");
        }

        return $setting;
    }

    public static function getPublic()
    {
        return Cache::remember('ebay_settings.public', 3600, function() {
            return static::where('is_public', true)
                ->pluck('value', 'key')
                ->toArray();
        });
    }

    public static function clearCache()
    {
        $keys = static::pluck('key');
        foreach ($keys as $key) {
            Cache::forget("ebay_settings.{$key}");
        }
        Cache::forget("ebay_settings.public");
    }

    protected static function boot()
    {
        parent::boot();

        static::saved(function ($setting) {
            Cache::forget("settings.{$setting->key}");
            Cache::forget("ebay_settings.public");
        });

        static::deleted(function ($setting) {
            Cache::forget("settings.{$setting->key}");
            Cache::forget("ebay_settings.public");
        });
    }
}
