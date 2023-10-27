<?php

namespace Wallo\Transmatic;

use Locale;
use Throwable;
use Wallo\Transmatic\Contracts\TranslationHandler;
use Wallo\Transmatic\Services\TranslateService;

class Transmatic
{
    protected TranslateService $translateService;

    protected TranslationHandler $translationHandler;

    public function __construct(TranslateService $translateService, TranslationHandler $translationHandler)
    {
        $this->translateService = $translateService;
        $this->translationHandler = $translationHandler;
    }

    /**
     * @throws Throwable
     */
    public function translate(string $text, ?string $to = null): string
    {
        $to = $to ?? app()->getLocale();

        return $this->translateService->getCachedTranslation($text, $to);
    }

    public function translateMany(array $texts, ?string $to = null): array
    {
        $to = $to ?? app()->getLocale();

        return array_map(/**
         * @throws Throwable
         */ function ($text) use ($to) {
            return $this->translateService->getCachedTranslation($text, $to);
        }, $texts);
    }

    public function getSupportedLocales(): array
    {
        return $this->translationHandler->getSupportedLocales();
    }

    public function getSupportedLanguages(): array
    {
        $locales = $this->getSupportedLocales();
        $languages = [];

        foreach ($locales as $locale) {
            $languages[$locale] = $this->getLanguage($locale);
        }

        return $languages;
    }

    public function getLanguage(string $locale): string
    {
        return Locale::getDisplayLanguage($locale, $locale);
    }
}
