<?php

namespace Wallo\Transmatic\Contracts;

interface TranslationHandler
{
    public function store(string $locale, array $translations): void;

    public function retrieve(string $locale): array;

    public function isBatchRunning(string $locale): bool;

    public function setBatchRunning(string $locale): void;

    public function setBatchFinished(string $locale): void;

    public function getSupportedLocales(): array;
}
