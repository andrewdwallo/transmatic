<?php

namespace Wallo\Transmatic\Services\Translation;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use JsonException;
use RuntimeException;
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
        try {
            if (isset($this->checkedFiles[$locale])) {
                return;
            }

            $filePath = $this->getFilePath($locale);
            $directory = dirname($filePath);
            File::ensureDirectoryExists($directory);

            if (File::missing($filePath)) {
                $json = json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

                if ($json === false) {
                    throw new JsonException('Could not encode empty array to JSON: ' . json_last_error_msg());
                }

                if (File::put($filePath, $json) === false) {
                    throw new RuntimeException('Could not create file: ' . $filePath);
                }
            }

            $this->checkedFiles[$locale] = true;
        } catch (Exception $e) {
            Log::error("Could not create file for locale: {$locale}", [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            throw new RuntimeException("Could not create file for locale: {$locale}", $e->getCode(), $e);
        }
    }

    public function store(string $locale, array $translations): void
    {
        try {
            $this->ensureFileExists($locale);
            $filePath = $this->getFilePath($locale);

            $json = json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

            if ($json === false) {
                throw new JsonException('Could not encode translations to JSON: ' . json_last_error_msg());
            }

            if (File::put($filePath, $json) === false) {
                throw new RuntimeException('Could not write translations to file: ' . $filePath);
            }

        } catch (Exception $e) {
            Log::error("Could not write translations to file for locale: {$locale}", [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            throw new RuntimeException("Could not write translations to file for locale: {$locale}", $e->getCode(), $e);
        }
    }

    public function retrieve(string $locale): array
    {
        try {
            $this->ensureFileExists($locale);
            $filePath = $this->getFilePath($locale);

            $json = File::json($filePath);

            /** @phpstan-ignore-next-line */
            if ($json === null) {
                throw new JsonException('Could not decode translations from JSON: ' . json_last_error_msg());
            }

            if (! is_array($json)) {
                throw new \UnexpectedValueException('Decoded JSON content is not an array');
            }

            return $json;
        } catch (Exception $e) {
            Log::error("Could not read translations from file for locale: {$locale}", [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            throw new RuntimeException("Could not read translations from file for locale: {$locale}", $e->getCode(), $e);
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
