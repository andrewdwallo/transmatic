<?php

namespace Wallo\Transmatic\Services\Translation;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Wallo\Transmatic\Contracts\TranslationHandler;

class CacheHandler implements TranslationHandler
{
    protected string $cacheKey;

    protected int $cacheDuration;

    protected string $supportedLocalesKey = 'supported_locales';

    public function __construct()
    {
        $this->cacheKey = config('transmatic.cache.key', 'translations');
        $this->cacheDuration = config('transmatic.cache.duration', 30);
    }

    public function store(string $locale, array $translations): void
    {
        try {
            Cache::put("{$this->cacheKey}_{$locale}", $translations, now()->addDays($this->cacheDuration));
            $this->updateSupportedLocales($locale);
        } catch (Exception $e) {
            Log::error("Could not store translations in cache for locale: {$locale}", [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            throw new RuntimeException("Could not store translations in cache for locale: {$locale}", $e->getCode(), $e);
        }
    }

    public function retrieve(string $locale): array
    {
        try {
            return Cache::get("{$this->cacheKey}_{$locale}") ?? [];
        } catch (Exception $e) {
            Log::error("Could not retrieve translations from cache for locale: {$locale}", [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            throw new RuntimeException("Could not retrieve translations from cache for locale: {$locale}", $e->getCode(), $e);
        }
    }

    public function isBatchRunning(string $locale): bool
    {
        return Cache::has("translation_batch_running_{$locale}");
    }

    public function setBatchRunning(string $locale): void
    {
        Cache::put("translation_batch_running_{$locale}", true, now()->addMinutes(5));
    }

    public function setBatchFinished(string $locale): void
    {
        Cache::forget("translation_batch_running_{$locale}");
    }

    private function updateSupportedLocales(string $locale): void
    {
        $supportedLocales = Cache::get($this->supportedLocalesKey) ?? [];

        if (! in_array($locale, $supportedLocales, true)) {
            $supportedLocales[] = $locale;
            Cache::put($this->supportedLocalesKey, $supportedLocales, now()->addDays($this->cacheDuration));
        }
    }

    public function getSupportedLocales(): array
    {
        return Cache::get($this->supportedLocalesKey) ?? [];
    }
}
