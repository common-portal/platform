<?php
/**
 * Global Helper Functions
 * Reference: COMMON-PORTAL-TRANSLATOR-CORE-CODE-001.md
 */

use App\Services\TranslatorService;

if (!function_exists('__translator')) {
    /**
     * Translate text to the user's preferred language.
     * Shorthand for TranslatorService::translate()
     *
     * @param string $text The English text to translate
     * @param string|null $targetLanguage Optional target language ISO3 code
     * @return string Translated text
     */
    function __translator(string $text, ?string $targetLanguage = null): string
    {
        return TranslatorService::translate($text, $targetLanguage);
    }
}

if (!function_exists('translator')) {
    /**
     * Alias for __translator() - Translate text to target language.
     *
     * @param string $text The English text to translate
     * @param string|null $targetLanguage Optional target language ISO3 code
     * @return string Translated text
     */
    function translator(string $text, ?string $targetLanguage = null): string
    {
        return TranslatorService::translate($text, $targetLanguage);
    }
}

if (!function_exists('language_selector')) {
    /**
     * Render the language selector dropdown HTML.
     *
     * @param string|null $currentLanguage Current selected language
     * @param string $selectClass CSS classes
     * @param string $selectStyle Inline styles
     * @return string HTML
     */
    function language_selector(?string $currentLanguage = null, string $selectClass = '', string $selectStyle = ''): string
    {
        return TranslatorService::renderLanguageSelector($currentLanguage, $selectClass, $selectStyle);
    }
}
