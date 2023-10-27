<?php

use Wallo\Transmatic\Facades\Transmatic;

if (! function_exists('translate')) {
    function translate(string $text, ?string $to = null): string
    {
        $to = $to ?? app()->getLocale();

        return Transmatic::translate($text, $to);
    }
}
