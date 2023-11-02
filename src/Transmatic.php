<?php

namespace Wallo\Transmatic;

use Locale;
use Wallo\Transmatic\Contracts\TranslationHandler;
use Wallo\Transmatic\Services\TranslateService;

class Transmatic
{
    protected static ?string $globalLocaleOverride = null;

    protected TranslateService $translateService;

    protected TranslationHandler $translationHandler;

    public function __construct(TranslateService $translateService, TranslationHandler $translationHandler)
    {
        $this->translateService = $translateService;
        $this->translationHandler = $translationHandler;
    }

    public static function setGlobalLocale(string $locale): void
    {
        self::$globalLocaleOverride = $locale;
    }

    public function translate(string $text, array $replace = [], ?string $to = null): string
    {
        $to = $this->determineLocale($to);

        return $this->translateService->getCachedTranslation($text, $replace, $to);
    }

    public function translateMany(array $texts, array $replace = [], ?string $to = null): array
    {
        $to = $this->determineLocale($to);

        return array_map(function ($text) use ($replace, $to) {
            return $this->translateService->getCachedTranslation($text, $replace, $to);
        }, $texts);
    }

    public function processMissingTranslationsFor(array $locales): void
    {
        $this->translateService->processMissingTranslationsForLocales($locales);
    }

    public function processMissingTranslations(): void
    {
        $allSupportedLocales = $this->getSupportedLocales();

        $sourceLocale = config('transmatic.source_locale', 'en');

        $localesToProcess = array_diff($allSupportedLocales, [$sourceLocale]);

        $this->translateService->processMissingTranslationsForLocales($localesToProcess);
    }

    protected function determineLocale(?string $to = null): string
    {
        return self::$globalLocaleOverride ?? $to ?? app()->getLocale();
    }

    public function getSupportedLocales(): array
    {
        return $this->translationHandler->getSupportedLocales();
    }

    public function getSupportedLanguages(?string $displayLocale = null): array
    {
        $locales = $this->getSupportedLocales();
        $languages = [];
        $displayLocale = $displayLocale ?? app()->getLocale();

        foreach ($locales as $locale) {
            $languages[$locale] = $this->getLanguage($locale, $displayLocale);
        }

        return $languages;
    }

    public function getLanguage(string $locale, ?string $displayLocale = null): string
    {
        $displayLocale = $displayLocale ?? app()->getLocale();

        return Locale::getDisplayLanguage($locale, $displayLocale);
    }
}
