<?php

use Wallo\Transmatic\Facades\Transmatic;

if (! function_exists('translate')) {
    /**
     * @throws Throwable
     */
    function translate(string $text, ?string $to = null): string
    {
        $to = $to ?? app()->getLocale();

        return Transmatic::translate($text, $to);
    }
}
