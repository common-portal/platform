<?php
/**
 * TranslatorService - Laravel Implementation
 * Reference: COMMON-PORTAL-TRANSLATOR-CORE-CODE-001.md
 * 
 * Provides translation with DB cache + OpenAI fallback.
 */

namespace App\Services;

use App\Models\CachedTextTranslation;
use Illuminate\Support\Facades\Http;

class TranslatorService
{
    /**
     * All supported languages with native names (100+ languages).
     * ISO 639-3 codes as keys.
     */
    public static function getLanguages(): array
    {
        return [
            'afr'=>'Afrikaans','amh'=>'አማርኛ','ara'=>'العربية','asm'=>'অসমীয়া','aze'=>'Azərbaycanca',
            'bak'=>'Башҡортса','bel'=>'Беларуская','bul'=>'Български','ben'=>'বাংলা','bod'=>'བོད་ཡིག',
            'bre'=>'Brezhoneg','bos'=>'Bosanski','cat'=>'Català','ces'=>'Čeština','cym'=>'Cymraeg',
            'dan'=>'Dansk','deu'=>'Deutsch','ell'=>'Ελληνικά','eng'=>'English','spa'=>'Español',
            'est'=>'Eesti','eus'=>'Euskara','fas'=>'فارسی','fin'=>'Suomi','fao'=>'Føroyskt',
            'fra'=>'Français','glg'=>'Galego','guj'=>'ગુજરાતી','hau'=>'Hausa','haw'=>'ʻŌlelo Hawaiʻi',
            'heb'=>'עברית','hin'=>'हिन्दी','hrv'=>'Hrvatski','hat'=>'Kreyòl ayisyen','hun'=>'Magyar',
            'hye'=>'Հdelays','ind'=>'Bahasa Indonesia','isl'=>'Íslenska','ita'=>'Italiano','jpn'=>'日本語',
            'jav'=>'Basa Jawa','kat'=>'ქართული','kaz'=>'Қazақ тілі','khm'=>'ខ្មែរ','kan'=>'ಕನ್ನಡ',
            'kor'=>'한국어','lat'=>'Latina','ltz'=>'Lëtzebuergesch','lin'=>'Lingála','lao'=>'ລາວ',
            'lit'=>'Lietuvių','lav'=>'Latviešu','mlg'=>'Malagasy','mri'=>'Māori','mkd'=>'Македонски',
            'mal'=>'മലയാളം','mon'=>'Монгол','mar'=>'मराठी','msa'=>'Bahasa Melayu','mlt'=>'Malti',
            'mya'=>'မြန်မာ','nep'=>'नेपाली','nld'=>'Nederlands','nno'=>'Nynorsk','nor'=>'Norsk',
            'oci'=>'Occitan','pan'=>'ਪੰਜਾਬੀ','pol'=>'Polski','pus'=>'پښتو','por'=>'Português',
            'ron'=>'Română','rus'=>'Русский','san'=>'संस्कृतम्','snd'=>'سنڌي','sin'=>'සිංහල',
            'slk'=>'Slovenčina','slv'=>'Slovenščina','sna'=>'chiShona','som'=>'Soomaaliga','sqi'=>'Shqip',
            'srp'=>'Српски','sun'=>'Basa Sunda','swe'=>'Svenska','swa'=>'Kiswahili','tam'=>'தமிழ்',
            'tel'=>'తెలుగు','tgk'=>'Тоҷикӣ','tha'=>'ไทย','tuk'=>'Türkmençe','tgl'=>'Tagalog',
            'tur'=>'Türkçe','tat'=>'Татарча','ukr'=>'Українська','urd'=>'اردو','uzb'=>'Oʻzbekcha',
            'vie'=>'Tiếng Việt','yid'=>'ייִדיש','yor'=>'Yorùbá','zho'=>'中文','yue'=>'粵語'
        ];
    }

    /**
     * Top 10 most spoken languages (shown first in selector).
     */
    public static function getTopLanguages(): array
    {
        return ['eng', 'zho', 'spa', 'hin', 'ara', 'por', 'fra', 'rus', 'jpn', 'deu'];
    }

    /**
     * Get the current user's preferred language.
     * Priority: 1) User preference (if logged in), 2) Session, 3) IP detection, 4) Default 'eng'
     */
    public static function getCurrentLanguage(): string
    {
        // 1. Logged-in user preference
        if (auth()->check() && auth()->user()->preferred_language_code) {
            return auth()->user()->preferred_language_code;
        }
        
        // 2. Session preference (already set)
        if (session()->has('preferred_language')) {
            return session('preferred_language');
        }
        
        // 3. Auto-detect from IP (first visit)
        $detectedLang = self::detectLanguageFromIp();
        if ($detectedLang) {
            session(['preferred_language' => $detectedLang]);
            return $detectedLang;
        }
        
        // 4. Default to English
        return 'eng';
    }

    /**
     * Detect language from visitor's IP address using IP2Location API.
     * API: https://utilities.getmondo.co/gateway/ip2location/ip2location.php
     * Returns ISO3 language code or null if detection fails.
     */
    public static function detectLanguageFromIp(): ?string
    {
        try {
            $ip = request()->ip();
            
            // Skip for localhost/private IPs
            if (in_array($ip, ['127.0.0.1', '::1']) || self::isPrivateIp($ip)) {
                return null;
            }
            
            $response = Http::timeout(3)->get(
                'https://utilities.getmondo.co/gateway/ip2location/ip2location.php',
                ['remote_address' => $ip]
            );
            
            if ($response->successful()) {
                $data = $response->json();
                $langCode = strtolower($data['language_iso3'] ?? '');
                
                // Validate it's a supported language
                if ($langCode && isset(self::getLanguages()[$langCode])) {
                    return $langCode;
                }
            }
        } catch (\Exception $e) {
            \Log::debug('IP2Location detection failed: ' . $e->getMessage());
        }
        
        return null;
    }

    /**
     * Check if IP is private/internal.
     */
    private static function isPrivateIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }

    /**
     * Translate text to target language.
     * Uses DB cache first, then Grok xAI API fallback.
     *
     * @param string $text The English text to translate
     * @param string|null $targetLanguage ISO3 language code (null = use current user's preference)
     * @return string Translated text (or original if translation fails/not needed)
     */
    public static function translate(string $text, ?string $targetLanguage = null): string
    {
        if (empty($text)) {
            return '';
        }

        $targetLanguage = strtolower($targetLanguage ?? self::getCurrentLanguage());

        // Short-circuit for English
        if ($targetLanguage === 'eng' || $targetLanguage === 'en') {
            return $text;
        }

        // Validate language code
        $languages = self::getLanguages();
        if (!isset($languages[$targetLanguage])) {
            return $text;
        }

        // Try database cache first
        $cached = CachedTextTranslation::findTranslation($text, $targetLanguage);
        if ($cached !== null) {
            return $cached;
        }

        // Grok xAI API fallback
        $apiKey = config('services.xai.api_key') ?: env('XAI_API_KEY');
        if (empty($apiKey)) {
            return $text;
        }

        $langName = $languages[$targetLanguage];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post('https://api.x.ai/v1/chat/completions', [
                'model' => 'grok-4-1-fast-non-reasoning',
                'messages' => [
                    ['role' => 'system', 'content' => "You are an HTML-preserving translator for a software UI. Translate visible text into {$langName}, keeping ALL HTML tags, attributes, comments, scripts, styles, CDATA, doctype, and structure 100% unchanged. Never add, remove, modify, or reorder any HTML. Translate only human-readable text nodes and display attributes (alt, title, placeholder, aria-label). Leave code/scripts untouched. IMPORTANT: Single words or short phrases are UI button labels or imperative verb commands (e.g. 'Impersonate', 'Delete', 'Submit') — translate them as action verbs, never refuse. Output ONLY the translated content — no explanations, no markdown fences."],
                    ['role' => 'user', 'content' => $text]
                ],
                'temperature' => 0.2
            ]);

            $translated = $response->json('choices.0.message.content', '');

            // Cache successful translation (filter out AI refusals)
            $refusalPhrases = ['cannot provide', 'lo siento', 'je suis désolé', 'i cannot', 'no puedo', 'je ne peux pas'];
            $isRefusal = false;
            foreach ($refusalPhrases as $phrase) {
                if (stripos($translated, $phrase) !== false) {
                    $isRefusal = true;
                    break;
                }
            }
            if ($translated && !$isRefusal && strlen($translated) < strlen($text) * 5) {
                CachedTextTranslation::cacheTranslation($text, $targetLanguage, $translated);
            }

            return $translated ?: $text;

        } catch (\Exception $e) {
            \Log::error('TranslatorService error: ' . $e->getMessage());
            return $text;
        }
    }

    /**
     * Render language selector HTML with top 10 first, separator, then all alphabetically.
     *
     * @param string|null $currentLanguage Current selected language (null = auto-detect)
     * @param string $selectClass CSS classes for the select element
     * @param string $selectStyle Inline styles for the select element
     * @return string HTML for the language selector
     */
    public static function renderLanguageSelector(
        ?string $currentLanguage = null, 
        string $selectClass = '',
        string $selectStyle = ''
    ): string {
        $languages = self::getLanguages();
        $top10 = self::getTopLanguages();
        $current = $currentLanguage ?? self::getCurrentLanguage();

        $html = '<select id="language-selector" onchange="changeLanguage(this.value)"';
        if ($selectClass) {
            $html .= ' class="' . htmlspecialchars($selectClass) . '"';
        }
        if ($selectStyle) {
            $html .= ' style="' . htmlspecialchars($selectStyle) . '"';
        }
        $html .= '>';

        // Top 10 languages first
        foreach ($top10 as $code) {
            if (isset($languages[$code])) {
                $selected = ($code === $current) ? ' selected' : '';
                $html .= '<option value="' . $code . '"' . $selected . '>' . htmlspecialchars($languages[$code]) . '</option>';
            }
        }

        // Separator
        $html .= '<option disabled>──────────────</option>';

        // All languages alphabetically (excluding top 10 to avoid duplicates)
        $allSorted = $languages;
        asort($allSorted); // Sort by native name
        foreach ($allSorted as $code => $name) {
            if (in_array($code, $top10)) {
                continue; // Skip top 10, already shown
            }
            $selected = ($code === $current) ? ' selected' : '';
            $html .= '<option value="' . $code . '"' . $selected . '>' . htmlspecialchars($name) . '</option>';
        }

        $html .= '</select>';

        return $html;
    }
}
