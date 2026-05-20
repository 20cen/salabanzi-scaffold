<?php
// src/Support/TestsDetector.php

namespace Salabanzi\LaravelScaffold\Support;

class TestsDetector
{
    public static function detect(): string
    {
        $composer = self::readJson(base_path('composer.json'));

        $deps = array_merge(
            $composer['require']     ?? [],
            $composer['require-dev'] ?? []
        );

        if (isset($deps['pestphp/pest'])) {
            return 'pest';
        }

        return 'phpunit';
    }

    private static function readJson(string $path): array
    {
        if (!file_exists($path)) return [];
        return json_decode(file_get_contents($path), true) ?? [];
    }
}