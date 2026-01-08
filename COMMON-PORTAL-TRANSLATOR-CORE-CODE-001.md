# Translator Framework - Copy/Paste Ready

> **One file. Two functions. 100+ languages. Powered by xAI Grok. Just copy, paste, configure, done.**

---

## Quick Start

1. Copy the PHP code below into `translator.php`
2. Create the database table (SQL provided)
3. Set your `XAI_API_KEY` environment variable (get from https://console.x.ai)
4. Configure `SITE_DOMAIN` and `COOKIE_NAME` constants
5. Use `<?= __t("Your text") ?>` in templates

---

## STEP 1: The PHP File (translator.php)

Copy this entire block into a single file called `translator.php`:

```php
<?php
/**
 * TRANSLATOR FRAMEWORK - Single File Implementation
 * Copy this file to any project and configure the constants below.
 */

//=============================================================================
// CONFIGURATION - CHANGE THESE FOR YOUR PROJECT
//=============================================================================
define('TRANSLATOR_COOKIE_NAME', 'mysite_language');  // Change 'mysite' to your site
define('TRANSLATOR_COOKIE_DOMAIN', '.example.com');   // Your domain with leading dot
define('TRANSLATOR_DEFAULT_LANG', 'eng');             // Default language ISO3 code
define('TRANSLATOR_IP_API', 'https://utilities.getmondo.co/gateway/ip2location/ip2location.php?remote_address=');

//=============================================================================
// LANGUAGE DATA - 100+ languages with native names
//=============================================================================
function _translator_languages() {
    static $languages = null;
    if ($languages === null) {
        $languages = [
            'afr'=>'Afrikaans','amh'=>'አማርኛ','ara'=>'العربية','asm'=>'অসমীয়া','aze'=>'Azərbaycanca',
            'bak'=>'Башҡортса','bel'=>'Беларуская','bul'=>'Български','ben'=>'বাংলা','bod'=>'བོད་ཡིག',
            'bre'=>'Brezhoneg','bos'=>'Bosanski','cat'=>'Català','ces'=>'Čeština','cym'=>'Cymraeg',
            'dan'=>'Dansk','deu'=>'Deutsch','ell'=>'Ελληνικά','eng'=>'English','spa'=>'Español',
            'est'=>'Eesti','eus'=>'Euskara','fas'=>'فارسی','fin'=>'Suomi','fao'=>'Føroyskt',
            'fra'=>'Français','glg'=>'Galego','guj'=>'ગુજરાતી','hau'=>'Hausa','haw'=>'ʻŌlelo Hawaiʻi',
            'heb'=>'עברית','hin'=>'हिन्दी','hrv'=>'Hrvatski','hat'=>'Kreyòl ayisyen','hun'=>'Magyar',
            'hye'=>'Հայերեն','ind'=>'Bahasa Indonesia','isl'=>'Íslenska','ita'=>'Italiano','jpn'=>'日本語',
            'jav'=>'Basa Jawa','kat'=>'ქართული','kaz'=>'Қазақ тілі','khm'=>'ខ្មែរ','kan'=>'ಕನ್ನಡ',
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
    return $languages;
}

//=============================================================================
// FUNCTION 1: translator() - Translates text with DB cache + xAI Grok fallback
//=============================================================================
function translator($text, $lang = null, $pdo = null) {
    if (empty($text)) return '';
    
    // Get language - from param, cookie, IP detection, or default
    if ($lang === null) {
        if (isset($_COOKIE[TRANSLATOR_COOKIE_NAME])) {
            $lang = $_COOKIE[TRANSLATOR_COOKIE_NAME];
        } else {
            // Auto-detect from IP on first visit
            $lang = TRANSLATOR_DEFAULT_LANG;
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            if ($ip && TRANSLATOR_IP_API) {
                $ch = curl_init(TRANSLATOR_IP_API . $ip);
                curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>3, CURLOPT_SSL_VERIFYPEER=>false]);
                $data = json_decode(curl_exec($ch), true);
                curl_close($ch);
                if (!empty($data['language_iso3']) && isset(_translator_languages()[strtolower($data['language_iso3'])])) {
                    $lang = strtolower($data['language_iso3']);
                    // Set cookie for future visits
                    setcookie(TRANSLATOR_COOKIE_NAME, $lang, [
                        'expires' => time() + 31536000, 'path' => '/',
                        'domain' => TRANSLATOR_COOKIE_DOMAIN, 'secure' => true, 'samesite' => 'Lax'
                    ]);
                }
            }
        }
    }
    
    $lang = strtolower($lang);
    
    // Short-circuit for English
    if ($lang === 'eng' || $lang === 'en') return $text;
    
    // Validate language code
    $languages = _translator_languages();
    if (!isset($languages[$lang])) return $text;
    
    // Try database cache first
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("SELECT translation_text FROM translations WHERE english_text = ? AND language_iso3 = ? LIMIT 1");
            $stmt->execute([$text, $lang]);
            $row = $stmt->fetch();
            if ($row && !empty($row['translation_text'])) {
                return $row['translation_text'];
            }
        } catch (Exception $e) {
            error_log("Translator DB error: " . $e->getMessage());
        }
    }
    
    // xAI Grok API fallback
    $apiKey = getenv('XAI_API_KEY');
    if (empty($apiKey)) return $text;
    
    $langName = $languages[$lang];
    
    // System prompt for HTML-preserving translations
    $systemPrompt = "You are an HTML-preserving translator. Translate visible text into {$langName}, keeping ALL HTML tags, attributes, comments, scripts, styles, CDATA, doctype, and structure 100% unchanged. Never add, remove, modify, or reorder any HTML. Translate only human-readable text nodes and display attributes (alt, title, placeholder, aria-label). Leave code/scripts untouched. Output ONLY the translated content — no explanations, no markdown fences.";
    
    $ch = curl_init('https://api.x.ai/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $apiKey, 'Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode([
            'model' => 'grok-4-1-fast-non-reasoning',
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $text]
            ],
            'temperature' => 0.2
        ])
    ]);
    $response = json_decode(curl_exec($ch), true);
    curl_close($ch);
    
    $translated = $response['choices'][0]['message']['content'] ?? '';
    
    // Cache successful translation
    if ($pdo && $translated && !stristr($translated, "cannot provide")) {
        try {
            $stmt = $pdo->prepare("INSERT INTO translations (hash, english_text, language_iso3, translation_text) VALUES (?, ?, ?, ?) ON CONFLICT DO NOTHING");
            $stmt->execute([md5($text . $lang . microtime(true)), mb_substr($text, 0, 2000), $lang, mb_substr($translated, 0, 2000)]);
        } catch (Exception $e) { /* ignore cache errors */ }
    }
    
    return $translated ?: $text;
}

//=============================================================================
// FUNCTION 2: language_selector() - Renders <select> with top 10 first
//=============================================================================
function language_selector($current = null, $class = '') {
    $languages = _translator_languages();
    $current = $current ?? $_COOKIE[TRANSLATOR_COOKIE_NAME] ?? TRANSLATOR_DEFAULT_LANG;
    $top10 = ['eng', 'zho', 'spa', 'hin', 'ara', 'por', 'fra', 'rus', 'jpn', 'deu'];
    
    $class = $class ?: 'bg-white border border-gray-300 rounded-lg px-3 py-2 text-sm cursor-pointer';
    $html = '<select id="language-select" onchange="changeLanguage(this.value)" class="' . $class . '">';
    
    // Top 10 first
    foreach ($top10 as $code) {
        if (isset($languages[$code])) {
            $sel = ($code === $current) ? ' selected' : '';
            $html .= '<option value="' . $code . '"' . $sel . '>' . htmlspecialchars($languages[$code]) . '</option>';
        }
    }
    
    // Separator
    $html .= '<option disabled>──────────────</option>';
    
    // All languages alphabetically
    foreach ($languages as $code => $name) {
        $sel = ($code === $current && !in_array($code, $top10)) ? ' selected' : '';
        $html .= '<option value="' . $code . '"' . $sel . '>' . htmlspecialchars($name) . '</option>';
    }
    
    $html .= '</select>';
    return $html;
}

//=============================================================================
// SHORTHAND HELPER - Use <?= __t("text") ?> in templates
//=============================================================================
function __t($text, $lang = null) {
    global $pdo; // Uses global $pdo if available
    return translator($text, $lang, $pdo ?? null);
}
?>
```

---

## STEP 2: The JavaScript (add to footer)

```javascript
<script>
function changeLanguage(code) {
    const d = new Date();
    d.setFullYear(d.getFullYear() + 1);
    document.cookie = `<?= TRANSLATOR_COOKIE_NAME ?>=${code}; expires=${d.toUTCString()}; path=/; domain=<?= TRANSLATOR_COOKIE_DOMAIN ?>; secure; samesite=lax`;
    location.reload();
}
</script>
```

Or hardcode your values:

```javascript
<script>
function changeLanguage(code) {
    const d = new Date();
    d.setFullYear(d.getFullYear() + 1);
    document.cookie = `mysite_language=${code}; expires=${d.toUTCString()}; path=/; domain=.example.com; secure; samesite=lax`;
    location.reload();
}
</script>
```

---

## STEP 3: Database Schema (PostgreSQL)

```sql
CREATE TABLE IF NOT EXISTS translations (
    id SERIAL PRIMARY KEY,
    hash VARCHAR(64) NOT NULL UNIQUE,
    english_text TEXT NOT NULL,
    language_iso3 VARCHAR(3) NOT NULL,
    translation_text TEXT NOT NULL,
    datetime_created TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_translations_lookup ON translations(language_iso3);
CREATE UNIQUE INDEX IF NOT EXISTS idx_translations_unique ON translations(md5(english_text), language_iso3);
```

---

## STEP 4: Usage Examples

### Basic Translation
```php
<?php require_once 'translator.php'; ?>

<h1><?= __t("Welcome to our website") ?></h1>
<p><?= __t("Click here to get started") ?></p>
```

### With Database Connection
```php
<?php
require_once 'translator.php';
$pdo = new PDO('pgsql:host=localhost;dbname=mydb', 'user', 'pass');
?>

<h1><?= translator("Welcome", null, $pdo) ?></h1>
```

### Language Selector in Header/Sidebar
```php
<?= language_selector() ?>

<!-- With custom Tailwind classes -->
<?= language_selector(null, 'bg-gray-800 text-white rounded px-4 py-2') ?>
```

### Force Specific Language
```php
<?= translator("Hello", "fra") ?>  <!-- French -->
<?= translator("Hello", "deu") ?>  <!-- German -->
<?= translator("Hello", "jpn") ?>  <!-- Japanese -->
```

---

## Configuration Reference

| Constant | Description | Example |
|----------|-------------|---------|
| `TRANSLATOR_COOKIE_NAME` | Cookie name for language preference | `'mysite_language'` |
| `TRANSLATOR_COOKIE_DOMAIN` | Domain with leading dot for subdomains | `'.example.com'` |
| `TRANSLATOR_DEFAULT_LANG` | Fallback language code | `'eng'` |
| `TRANSLATOR_IP_API` | IP geolocation API URL (or empty to disable) | `'https://...'` |

---

## Environment Variables

```bash
XAI_API_KEY=xai-your-api-key-here
```

Get your API key from https://console.x.ai

---

## Top 10 Languages (shown first in selector)

| Code | Language |
|------|----------|
| `eng` | English |
| `zho` | 中文 (Chinese) |
| `spa` | Español (Spanish) |
| `hin` | हिन्दी (Hindi) |
| `ara` | العربية (Arabic) |
| `por` | Português (Portuguese) |
| `fra` | Français (French) |
| `rus` | Русский (Russian) |
| `jpn` | 日本語 (Japanese) |
| `deu` | Deutsch (German) |

---

## Summary

**What you get:**
- `translator($text, $lang, $pdo)` - Translates text (auto-detects language if not specified)
- `language_selector($current, $class)` - Renders dropdown with top 10 languages first
- `__t($text)` - Shorthand helper for templates
- Auto IP-based language detection on first visit
- Cookie persistence for language preference
- PostgreSQL caching with xAI Grok fallback
- 100+ supported languages

---

## xAI Grok Configuration

| Setting | Value |
|---------|-------|
| **API Endpoint** | `https://api.x.ai/v1/chat/completions` |
| **Model** | `grok-4-1-fast-non-reasoning` |
| **Pricing** | $0.20/M input, $0.50/M output |
| **Context Window** | 2M tokens |
| **Temperature** | 0.2 (low for consistent translations) |

### Why grok-4-1-fast-non-reasoning?

- **Faster**: Optimized for high-throughput, low-latency inference
- **Cheaper**: ~15× cheaper than older Grok-3 models
- **No reasoning overhead**: Unlike `-reasoning` variants, no extra thinking tokens
- **Excellent instruction following**: Preserves HTML structure perfectly

### System Prompt (HTML-Preserving)

```
You are an HTML-preserving translator. Translate visible text into {LANGUAGE}, 
keeping ALL HTML tags, attributes, comments, scripts, styles, CDATA, doctype, 
and structure 100% unchanged. Never add, remove, modify, or reorder any HTML. 
Translate only human-readable text nodes and display attributes (alt, title, 
placeholder, aria-label). Leave code/scripts untouched. Output ONLY the 
translated content — no explanations, no markdown fences.
```
