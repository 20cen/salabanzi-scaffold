<?php

namespace Salabanzi\LaravelScaffold\Support;

use Symfony\Component\Process\Process;

class AuthDetector
{
    public static function detect(): string
    {
        $composer = self::readJson(base_path('composer.json'));
        $deps     = array_merge(
            $composer['require']     ?? [],
            $composer['require-dev'] ?? []
        );

        if (isset($deps['laravel/sanctum']))  return 'sanctum';
        if (isset($deps['laravel/passport'])) return 'passport';
        if (isset($deps['tymon/jwt-auth']))   return 'jwt';

        return 'none';
    }

    public static function middleware(): string
    {
        return match(self::detect()) {
            'sanctum'  => 'auth:sanctum',
            'passport' => 'auth:api',
            'jwt'      => 'auth:api',
            default    => 'auth:sanctum',
        };
    }

    public static function installSanctumIfNeeded(): void
    {
        if (self::detect() !== 'none') return;

        $process = new Process(
            ['php', 'artisan', 'install:api', '--no-interaction'],
            base_path()
        );
        $process->setTimeout(120);
        $process->run();
    }

    private static function readJson(string $path): array
    {
        if (!file_exists($path)) return [];
        return json_decode(file_get_contents($path), true) ?? [];
    }
}