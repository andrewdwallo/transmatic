<?php

namespace Wallo\Transmatic\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string translate(string $text, ?string $to = null)
 * @method static array translateMany(array $texts, ?string $to = null)
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
