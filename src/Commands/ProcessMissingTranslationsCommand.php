<?php

namespace Wallo\Transmatic\Commands;

use Illuminate\Console\Command;
use Wallo\Transmatic\Facades\Transmatic;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\multiselect;

class ProcessMissingTranslationsCommand extends Command
{
    protected $signature = 'transmatic:process-missing-translations';

    protected $description = 'Process missing translations for all locales';

    public function handle(): void
    {
        info('Before processing missing translations, ensure that your queue worker is running to handle the translation jobs.');

        $queueWorkerRunning = confirm(
            label: 'Is your queue worker running?',
            default: false,
            yes: 'Yes, my queue worker is running.',
            no: 'No, I need to start my queue worker.',
            hint: 'You should run "php artisan queue:work" in a separate terminal window."'
        );

        if (! $queueWorkerRunning) {
            error('Please start your queue worker before proceeding.');

            return;
        }

        $locales = Transmatic::getSupportedLocales();
        array_unshift($locales, 'All');

        $selectedLocales = multiselect(
            label: 'Which locales would you like to process?',
            options: $locales,
            hint: 'Select one or multiple locales. Choose "All" to process all locales.',
        );

        if (in_array('All', $selectedLocales, true)) {
            Transmatic::processMissingTranslations();
        } else {
            Transmatic::processMissingTranslationsFor($selectedLocales);
        }

        info('Missing translations are being processed by your queue worker. Ensure that your queue worker continues to run until all jobs have been processed.');
    }
}
