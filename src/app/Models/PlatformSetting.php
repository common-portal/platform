<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class PlatformSetting extends Model
{
    use HasFactory;

    protected $table = 'platform_settings';

    const CREATED_AT = 'created_at_timestamp';
    const UPDATED_AT = 'updated_at_timestamp';

    protected $fillable = [
        'setting_key',
        'setting_value',
    ];

    protected $casts = [
        'created_at_timestamp' => 'datetime',
        'updated_at_timestamp' => 'datetime',
    ];

    /**
     * Get a setting value by key.
     */
    public static function getValue(string $key, $default = null)
    {
        $setting = self::where('setting_key', $key)->first();
        
        if (!$setting) {
            return $default;
        }

        $value = $setting->setting_value;

        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        return $value;
    }

    /**
     * Set a setting value.
     */
    public static function setValue(string $key, $value): void
    {
        $stored_value = is_array($value) || is_object($value) 
            ? json_encode($value) 
            : (string) $value;

        self::updateOrCreate(
            ['setting_key' => $key],
            ['setting_value' => $stored_value]
        );

        Cache::forget("platform_setting:{$key}");
    }

    /**
     * Get a cached setting value.
     */
    public static function getCached(string $key, $default = null)
    {
        return Cache::remember("platform_setting:{$key}", 3600, function () use ($key, $default) {
            return self::getValue($key, $default);
        });
    }

    /**
     * Get all settings as key-value array.
     */
    public static function getAllSettings(): array
    {
        return self::all()->pluck('setting_value', 'setting_key')->toArray();
    }

    /**
     * Known setting keys.
     */
    public const SETTING_KEYS = [
        'platform_display_name',
        'platform_logo_image_path',
        'platform_favicon_image_path',
        'social_sharing_preview_image_path',
        'social_sharing_meta_description',
        'active_theme_preset_name',
        'custom_theme_color_overrides',
        'sidebar_menu_item_visibility_toggles',
    ];
}
