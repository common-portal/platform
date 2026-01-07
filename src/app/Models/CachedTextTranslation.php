<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CachedTextTranslation extends Model
{
    use HasFactory;

    protected $table = 'cached_text_translations';

    const CREATED_AT = 'created_at_timestamp';
    const UPDATED_AT = null;

    protected $fillable = [
        'translation_hash',
        'original_english_text',
        'target_language_iso3',
        'translated_text',
    ];

    protected $casts = [
        'created_at_timestamp' => 'datetime',
    ];

    /**
     * Find a cached translation.
     */
    public static function findTranslation(string $english_text, string $language_iso3): ?string
    {
        $translation = self::where('original_english_text', $english_text)
            ->where('target_language_iso3', $language_iso3)
            ->first();

        return $translation?->translated_text;
    }

    /**
     * Cache a translation.
     */
    public static function cacheTranslation(string $english_text, string $language_iso3, string $translated_text): self
    {
        $hash = md5($english_text . $language_iso3 . microtime(true));

        return self::create([
            'translation_hash' => $hash,
            'original_english_text' => mb_substr($english_text, 0, 2000),
            'target_language_iso3' => $language_iso3,
            'translated_text' => mb_substr($translated_text, 0, 2000),
        ]);
    }

    /**
     * Get or create a translation (with callback for API call).
     */
    public static function getOrCreate(string $english_text, string $language_iso3, callable $translator_callback): string
    {
        $cached = self::findTranslation($english_text, $language_iso3);
        
        if ($cached !== null) {
            return $cached;
        }

        $translated = $translator_callback($english_text, $language_iso3);

        if ($translated && $translated !== $english_text) {
            self::cacheTranslation($english_text, $language_iso3, $translated);
        }

        return $translated ?: $english_text;
    }

    /**
     * Scope: by language.
     */
    public function scopeForLanguage($query, string $language_iso3)
    {
        return $query->where('target_language_iso3', $language_iso3);
    }
}
