<?php

namespace Wallo\Transmatic\Services\Translators;

use Aws\Laravel\AwsFacade;
use Aws\Translate\TranslateClient;
use Wallo\Transmatic\Contracts\Translator;

class AwsTranslate implements Translator
{
    public function translate(string $text, string $from, string $to): string
    {
        $aws = AwsFacade::createClient('translate');

        /** @var TranslateClient $aws */
        $result = $aws->translateText([
            'Text' => $text,
            'SourceLanguageCode' => $from,
            'TargetLanguageCode' => $to,
        ]);

        return $result->hasKey('TranslatedText') ? $result['TranslatedText'] : $text;
    }
}
