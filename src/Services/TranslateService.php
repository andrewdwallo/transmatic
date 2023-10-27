<?php

namespace Wallo\Transmatic\Services;

use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Throwable;
use Wallo\Transmatic\Contracts\TranslationHandler;
use Wallo\Transmatic\Contracts\Translator;
use Wallo\Transmatic\Jobs\ProcessTranslations;

class TranslateService
{
    private string $sourceLocale;

    private string $queue;

    private int $chunkSize;

    private TranslationHandler $translationHandler;

    private Translator $translator;

    public function __construct(TranslationHandler $translationHandler, Translator $translator)
    {
        $this->sourceLocale = config('transmatic.source_locale', 'en');
        $this->queue = config('transmatic.batching.queue', 'translations');
        $this->chunkSize = config('transmatic.batching.chunk_size', 50);
        $this->translationHandler = $translationHandler;
        $this->translator = $translator;
    }

    public function getCachedTranslation(string $text, string $to): string
    {
        $this->updateEnglishTranslation($text);

        if ($to === $this->sourceLocale) {
            return $text;
        }

        $batchRunning = $this->translationHandler->isBatchRunning($to);

        if ($batchRunning) {
            $translations = $this->translationHandler->retrieve($to);

            return $translations[$text] ?? $text;
        }

        return $this->handleStorage($text, $to);
    }

    private function updateEnglishTranslation(string $text): void
    {
        $englishTranslations = $this->translationHandler->retrieve($this->sourceLocale);

        if (! array_key_exists($text, $englishTranslations)) {
            $englishTranslations[$text] = $text;
            $this->translationHandler->store($this->sourceLocale, $englishTranslations);
        }
    }

    private function handleStorage(string $text, string $to): string
    {
        $translations = $this->translationHandler->retrieve($to);

        if (empty($translations)) {
            $this->generateAllTranslationsForLocale($to);
            $translations = $this->translationHandler->retrieve($to);
        }

        if (! array_key_exists($text, $translations)) {
            ProcessTranslations::dispatchSync($this->translator, $this->translationHandler, [$text], $to);
            $translations = $this->translationHandler->retrieve($to);
        }

        return $translations[$text] ?? $text;
    }

    private function generateAllTranslationsForLocale(string $to): void
    {
        $englishTranslations = $this->translationHandler->retrieve($this->sourceLocale);
        $textsToTranslate = array_keys($englishTranslations);

        $chunks = array_chunk($textsToTranslate, $this->chunkSize);

        $jobs = [];

        foreach ($chunks as $chunk) {
            $job = (new ProcessTranslations($this->translator, $this->translationHandler, $chunk, $to))->onQueue($this->queue);
            $jobs[] = $job;
        }

        $this->translationHandler->setBatchRunning($to);

        $translationHandler = $this->translationHandler;

        try {
            Bus::batch($jobs)
                ->allowFailures()
                ->catch(function (Batch $batch, Throwable $e) use ($to) {
                    Log::error('Translation batch failed:', [
                        'batchId' => $batch->id,
                        'locale' => $to,
                        'exception' => $e->getMessage(),
                    ]);
                })
                ->finally(function () use ($translationHandler, $to) {
                    $translationHandler->setBatchFinished($to);
                })
                ->dispatch();
        } catch (Throwable $e) {
            Log::error('Failed to dispatch translation batch:', [
                'locale' => $to,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
