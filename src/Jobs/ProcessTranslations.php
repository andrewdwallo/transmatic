<?php

namespace Wallo\Transmatic\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Wallo\Transmatic\Contracts\TranslationHandler;
use Wallo\Transmatic\Contracts\Translator;

class ProcessTranslations implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use Queueable;

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
        $this->sourceLocale = config('app.source_locale', 'en');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        foreach ($this->texts as $text) {
            $translatedText = $this->translator->translate($text, $this->sourceLocale, $this->to);

            $translations = $this->translationHandler->retrieve($this->to);
            $translations[$text] = $translatedText;
            $this->translationHandler->store($this->to, $translations);
        }
    }
}
