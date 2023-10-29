<?php

namespace Wallo\Transmatic\Services\Translators;

use Aws\Exception\AwsException;
use Aws\Laravel\AwsFacade;
use Aws\Result;
use Aws\Translate\TranslateClient;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Support\Facades\Log;
use Wallo\Transmatic\Contracts\Translator;

class AwsTranslate implements Translator
{
    protected int $timeout;

    public function __construct()
    {
        $this->timeout = config('transmatic.translator.timeout', 30);
    }

    public function setTimeout(int $seconds): void
    {
        $this->timeout = $seconds;
    }

    public function translate(string $text, string $from, string $to): PromiseInterface
    {
        $aws = AwsFacade::createClient('translate', [
            'http' => [
                'timeout' => $this->timeout,
            ],
        ]);

        /** @var TranslateClient $aws */
        $promise = $aws->translateTextAsync([
            'Text' => $text,
            'SourceLanguageCode' => $from,
            'TargetLanguageCode' => $to,
        ]);

        return $promise->then(
            $this->onTranslationSuccess($text),
            $this->onTranslationFailure($text)
        );
    }

    private function onTranslationSuccess(string $text): callable
    {
        return static function (Result $result) use ($text) {
            return $result->hasKey('TranslatedText')
                ? $result['TranslatedText']
                : $text;
        };
    }

    private function onTranslationFailure(string $text): callable
    {
        return static function (AwsException $error) use ($text) {
            Log::error('Translation failed', [
                'error' => $error->getMessage(),
                'text' => $text,
            ]);

            return $text;
        };
    }
}
