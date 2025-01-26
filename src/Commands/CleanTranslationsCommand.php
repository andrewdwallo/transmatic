<?php

namespace Wallo\Transmatic\Commands;

use Illuminate\Console\Command;
use Wallo\Transmatic\Contracts\TranslationHandler;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;

class CleanTranslationsCommand extends Command
{
    protected $signature = 'transmatic:clean-translations';

    protected $description = 'Remove keys from translation files that are no longer in the source locale.';

    public function handle(TranslationHandler $translationHandler): void
    {
        info('Starting the translation cleaning process...');

        $sourceLocale = config('transmatic.source_locale', 'en');
        $sourceTranslations = $translationHandler->retrieve($sourceLocale);

        if (empty($sourceTranslations)) {
            error('Source locale file is empty or missing.');

            return;
        }

        $proceed = confirm('This will remove keys from other locale files that are missing in the source locale. Do you want to proceed?', true);

        if (! $proceed) {
            info('Translation cleaning process aborted.');

            return;
        }

        $supportedLocales = $translationHandler->getSupportedLocales();

        $cleanedLocales = [];
        $skippedLocales = [];
        $errors = [];

        foreach ($supportedLocales as $locale) {
            if ($locale === $sourceLocale) {
                continue;
            }

            try {
                $translations = $translationHandler->retrieve($locale);

                if (empty($translations)) {
                    $skippedLocales[] = $locale;

                    continue;
                }

                $cleanedTranslations = array_intersect_key($translations, $sourceTranslations);

                $translationHandler->store($locale, $cleanedTranslations);

                $cleanedLocales[] = $locale;
            } catch (\Exception $e) {
                $errors[] = "Locale '{$locale}': " . $e->getMessage();
            }
        }

        if (! empty($cleanedLocales)) {
            info('Cleaned translations for the following locales: ' . implode(', ', $cleanedLocales));
        }

        if (! empty($skippedLocales)) {
            info('Skipped the following locales (empty or missing): ' . implode(', ', $skippedLocales));
        }

        if (! empty($errors)) {
            error('Errors occurred for the following locales:');
            foreach ($errors as $error) {
                error($error);
            }
        }

        info('The translation cleaning process has been completed.');
    }
}
