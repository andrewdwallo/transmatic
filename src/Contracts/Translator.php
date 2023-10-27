<?php

namespace Wallo\Transmatic\Contracts;

interface Translator
{
    public function translate(string $text, string $from, string $to): string;
}
