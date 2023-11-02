<?php

namespace Wallo\Transmatic\Services;

use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;
use Wallo\Transmatic\Contracts\TranslationHandler;
use Wallo\Transmatic\Contracts\Translator;
use Wallo\Transmatic\Jobs\ProcessTranslations;

class TranslateService
{
    private string $sourceLocale;

    private string $name;

    private string $connection;

    private string $queue;

    private int $chunkSize;

    private bool $allowFailures;

    private TranslationHandler $translationHandler;

    private Translator $translator;

    public function __construct(TranslationHandler $translationHandler, Translator $translator)
    {
        $this->sourceLocale = config('transmatic.source_locale', 'en');
        $this->name = config('transmatic.batching.name', 'TransmaticBatch');
        $this->connection = config('transmatic.batching.connection', 'database');
        $this->queue = config('transmatic.batching.queue', 'translations');
        $this->chunkSize = config('transmatic.job.chunk_size', 200);
        $this->allowFailures = config('transmatic.batching.allow_failures', true);
        $this->translationHandler = $translationHandler;
        $this->translator = $translator;
    }

    public function getCachedTranslation(string $text, array $replace, string $to): string
    {
        $this->updateSourceTranslation($text);

        if ($to === $this->sourceLocale) {
            return $this->makeReplacements($text, $replace);
        }

        $batchRunning = $this->translationHandler->isBatchRunning($to);

        if ($batchRunning) {
            $translations = $this->translationHandler->retrieve($to);

            return $this->makeReplacements($translations[$text] ?? $text, $replace);
        }

        return $this->handleStorage($text, $replace, $to);
    }

    public function processMissingTranslationsForLocales(array $locales): void
    {
        foreach ($locales as $locale) {
            $this->generateMissingTranslationsForLocales($locale);
        }
    }

    private function updateSourceTranslation(string $text): void
    {
        $sourceTranslations = $this->translationHandler->retrieve($this->sourceLocale);

        if (! array_key_exists($text, $sourceTranslations)) {
            $sourceTranslations[$text] = $text;
            $this->translationHandler->store($this->sourceLocale, $sourceTranslations);
        }
    }

    private function handleStorage(string $text, array $replace, string $to): string
    {
        $translations = $this->translationHandler->retrieve($to);
        $sourceTranslations = $this->translationHandler->retrieve($this->sourceLocale);

        if (empty($translations)) {
            $this->generateAllTranslationsForLocale($to);
            $translations = $this->translationHandler->retrieve($to);
        }

        if (array_key_exists($text, $sourceTranslations) && ! array_key_exists($text, $translations)) {
            $missingTranslations = array_diff_key($sourceTranslations, $translations);
            $textsToTranslate = array_keys($missingTranslations);
            ProcessTranslations::dispatchSync($this->translator, $this->translationHandler, $textsToTranslate, $to);
            $translations = $this->translationHandler->retrieve($to);
        }

        $translatedText = $translations[$text] ?? $text;

        return $this->makeReplacements($translatedText, $replace);
    }

    private function generateAllTranslationsForLocale(string $to): void
    {
        $sourceTranslations = $this->translationHandler->retrieve($this->sourceLocale);
        $textsToTranslate = array_keys($sourceTranslations);

        if ($this->chunkSize <= 0) {
            $count = count($textsToTranslate);
            $this->chunkSize = $count > 0 ? $count : 200;
        }

        $chunks = array_chunk($textsToTranslate, $this->chunkSize);

        $jobs = [];

        foreach ($chunks as $chunk) {
            $job = new ProcessTranslations($this->translator, $this->translationHandler, $chunk, $to);
            $jobs[] = $job;
        }

        $this->translationHandler->setBatchRunning($to);

        $translationHandler = $this->translationHandler;

        try {
            Bus::batch($jobs)
                ->name($this->name)
                ->onConnection($this->connection)
                ->onQueue($this->queue)
                ->allowFailures($this->allowFailures)
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

    private function generateMissingTranslationsForLocales(string $locale): void
    {
        $sourceTranslations = $this->translationHandler->retrieve($this->sourceLocale);
        $translations = $this->translationHandler->retrieve($locale);

        $missingTranslations = array_diff_key($sourceTranslations, $translations);
        $textsToTranslate = array_keys($missingTranslations);

        if (empty($textsToTranslate)) {
            return;
        }

        $this->queueMissingTranslationsForLocale($textsToTranslate, $locale);
    }

    private function queueMissingTranslationsForLocale(array $textsToTranslate, string $locale): void
    {
        if ($this->chunkSize <= 0) {
            $count = count($textsToTranslate);
            $this->chunkSize = $count > 0 ? $count : 200;
        }

        $chunks = array_chunk($textsToTranslate, $this->chunkSize);

        $jobs = [];

        foreach ($chunks as $chunk) {
            $job = new ProcessTranslations($this->translator, $this->translationHandler, $chunk, $locale);
            $jobs[] = $job;
        }

        try {
            Bus::batch($jobs)
                ->name($this->name)
                ->onConnection($this->connection)
                ->onQueue($this->queue)
                ->allowFailures($this->allowFailures)
                ->catch(function (Batch $batch, Throwable $e) use ($locale) {
                    Log::error('Translation batch failed:', [
                        'batchId' => $batch->id,
                        'locale' => $locale,
                        'exception' => $e->getMessage(),
                    ]);
                })
                ->dispatch();
        } catch (Throwable $e) {
            Log::error('Failed to dispatch translation batch:', [
                'locale' => $locale,
                'exception' => $e->getMessage(),
            ]);
        }
    }

    private function makeReplacements(string $text, array $replace): string
    {
        if (empty($replace)) {
            return $text;
        }

        $shouldReplace = $this->prepareReplacements($replace);

        return strtr($text, $shouldReplace);
    }

    private function prepareReplacements(array $replace): array
    {
        $prepared = [];

        foreach ($replace as $key => $value) {
            $prepared[':' . Str::ucfirst($key)] = Str::ucfirst($value);
            $prepared[':' . Str::upper($key)] = Str::upper($value);
            $prepared[':' . $key] = $value;
        }

        return $prepared;
    }
}
