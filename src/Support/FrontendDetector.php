<?php

namespace Salabanzi\LaravelScaffold\Support;

class FrontendDetector
{
    public static function detect(): string
    {
        $composer = self::readJson(base_path('composer.json'));

        $deps = array_merge(
            $composer['require']     ?? [],
            $composer['require-dev'] ?? []
        );

        // Inertia installé ?
        if (isset($deps['inertiajs/inertia-laravel'])) {
            $package = self::readJson(base_path('package.json'));
            $jsDeps  = array_merge(
                $package['dependencies']    ?? [],
                $package['devDependencies'] ?? []
            );

            if (isset($jsDeps['react'])) return 'react';
            if (isset($jsDeps['vue']))   return 'vue';
        }

        // Livewire installé ?
        if (isset($deps['livewire/livewire'])) {
            return 'livewire';
        }

        return 'none';
    }

    private static function readJson(string $path): array
    {
        if (!file_exists($path)) return [];
        return json_decode(file_get_contents($path), true) ?? [];
    }
}