<?php

namespace Wallo\Transmatic\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void setGlobalLocale(string $locale)
 * @method static string translate(string $text, array $replace = [], ?string $to = null)
 * @method static array translateMany(array $texts, array $replace = [], ?string $to = null)
 * @method static void processMissingTranslationsFor(array $locales)
 * @method static void processMissingTranslations()
 * @method static array getSupportedLocales()
 * @method static array getSupportedLanguages(?string $displayLocale = null)
 * @method static string getLanguage(string $locale, ?string $displayLocale = null)
 *
 * @see \Wallo\Transmatic\Transmatic
 */
class Transmatic extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'transmatic';
    }
}
