<?php

use Wallo\Transmatic\Facades\Transmatic;

if (! function_exists('translate')) {
    function translate(string $text, array $replace = [], ?string $to = null): string
    {
        return Transmatic::translate($text, $replace, $to);
    }
}

if (! function_exists('translateMany')) {
    function translateMany(array $texts, array $replace = [], ?string $to = null): array
    {
        return Transmatic::translateMany($texts, $replace, $to);
    }
}
