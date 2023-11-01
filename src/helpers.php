<?php

use Wallo\Transmatic\Facades\Transmatic;

if (! function_exists('translate')) {
    function translate(string $text, array $replace = [], ?string $to = null): string
    {
        $to = $to ?? app()->getLocale();

        return Transmatic::translate($text, $replace, $to);
    }
}

if (! function_exists('translateMany')) {
    function translateMany(array $texts, array $replace = [], ?string $to = null): array
    {
        $to = $to ?? app()->getLocale();

        return Transmatic::translateMany($texts, $replace, $to);
    }
}
