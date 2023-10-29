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
                $promises[$text] = $this->translator->translate($text, $this->sourceLocale, $this->to)
                    ->then(function ($translatedText) use ($text) {
                        $translations = $this->translationHandler->retrieve($this->to);
                        $translations[$text] = $translatedText;
                        $this->translationHandler->store($this->to, $translations);
                    });
            }

            Utils::unwrap($promises);
        } catch (Exception $e) {
            $this->fail($e);
        }
    }
}
