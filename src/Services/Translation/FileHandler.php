<?php

namespace Wallo\Transmatic\Services\Translation;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Wallo\Transmatic\Contracts\TranslationHandler;

class FileHandler implements TranslationHandler
{
    protected string $filePath;

    protected array $checkedFiles = [];

    public function __construct()
    {
        $this->filePath = config('transmatic.file.path', 'resources/data/lang');
    }

    private function getFilePath(string $locale): string
    {
        return base_path("{$this->filePath}/{$locale}.json");
    }

    private function ensureFileExists(string $locale): void
    {
        if (isset($this->checkedFiles[$locale])) {
            return;
        }

        $filePath = $this->getFilePath($locale);
        $directory = dirname($filePath);
        File::ensureDirectoryExists($directory);

        if (File::missing($filePath)) {
            File::put($filePath, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        $this->checkedFiles[$locale] = true;
    }

    public function store(string $locale, array $translations): void
    {
        $this->ensureFileExists($locale);
        $filePath = $this->getFilePath($locale);
        File::put($filePath, json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    public function retrieve(string $locale): array
    {
        $this->ensureFileExists($locale);
        $filePath = $this->getFilePath($locale);

        return File::json($filePath) ?? [];
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

    public function getSupportedLocales(): array
    {
        $files = File::files(base_path($this->filePath));
        $locales = [];

        foreach ($files as $file) {
            $locales[] = pathinfo($file->getFilename(), PATHINFO_FILENAME);
        }

        return $locales;
    }
}
