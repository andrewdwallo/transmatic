<?php

namespace Wallo\Transmatic\Contracts;

use GuzzleHttp\Promise\PromiseInterface;

interface Translator
{
    public function translate(string $text, string $from, string $to): PromiseInterface;
}
