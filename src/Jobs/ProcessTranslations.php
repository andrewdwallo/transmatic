<?php

namespace Wallo\Transmatic\Jobs;

use DateTime;
use Exception;
use GuzzleHttp\Promise\Utils;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Throwable;
use Wallo\Transmatic\Contracts\TranslationHandler;
use Wallo\Transmatic\Contracts\Translator;

class ProcessTranslations implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public int $tries;

    public int $retryDuration;

    public string $placeholderFormat;

    public bool $supportsPlaceholders;

    public string $sourceLocale;

    public string $to;

    public array $texts;

    public Translator $translator;

    public TranslationHandler $translationHandler;

    /**
     * Create a new job instance.
     */
    public function __construct(Translator $translator, TranslationHandler $translationHandler, array $texts, string $to)
    {
        $this->translator = $translator;
        $this->translationHandler = $translationHandler;
        $this->texts = $texts;
        $this->to = $to;
        $this->sourceLocale = config('transmatic.source_locale', 'en');
        $this->tries = config('transmatic.job.max_attempts', 3);
        $this->retryDuration = config('transmatic.job.retry_duration', 60);
        $this->placeholderFormat = config('transmatic.translator.placeholder_format', '#placeholder');
        $this->supportsPlaceholders = config('transmatic.translator.supports_placeholders', true);
    }

    public function retryUntil(): DateTime
    {
        return now()->addSeconds($this->retryDuration);
    }

    /**
     * Execute the job.
     *
     * @throws Throwable
     */
    public function handle(): void
    {
        $promises = [];

        try {
            foreach ($this->texts as $text) {
                $apiText = $this->supportsPlaceholders ? $this->prepareTextForApi($text) : $text;

                $promises[$text] = $this->translator->translate($apiText, $this->sourceLocale, $this->to)
                    ->then(function ($translatedText) use ($text) {
                        $laravelText = $this->supportsPlaceholders ? $this->prepareTextFromApi($translatedText) : $translatedText;

                        if ($this->supportsPlaceholders === false) {
                            $laravelText = $this->replaceBackToOriginalPlaceholders($laravelText, $text);
                        }

                        $translations = $this->translationHandler->retrieve($this->to);
                        $translations[$text] = $laravelText;
                        $this->translationHandler->store($this->to, $translations);
                    });
            }

            Utils::unwrap($promises);
        } catch (Exception $e) {
            $this->fail($e);
        }
    }

    private function prepareTextForApi(string $text): string
    {
        $result = preg_replace_callback('/:(\w+)/', function ($matches) {
            return str_replace('placeholder', $matches[1], $this->placeholderFormat);
        }, $text);

        return $result ?? $text;
    }

    private function prepareTextFromApi(string $text): string
    {
        $escapedFormat = preg_quote($this->placeholderFormat, '/');
        $pattern = '/' . str_replace('placeholder', '(\w+)', $escapedFormat) . '/';

        $result = preg_replace_callback($pattern, static function ($matches) {
            return ':' . $matches[1];
        }, $text);

        return $result ?? $text;
    }

    private function replaceBackToOriginalPlaceholders(string $translatedText, string $originalText): string
    {
        preg_match_all('/:(\w+)/', $originalText, $originalPlaceholders);

        preg_match_all('/:(\w+)/', $translatedText, $translatedPlaceholders);

        return str_replace($translatedPlaceholders[0], $originalPlaceholders[0], $translatedText);
    }
}
