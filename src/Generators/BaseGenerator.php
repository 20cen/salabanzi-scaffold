<?php

namespace Salabanzi\LaravelScaffold\Generators;

use Salabanzi\LaravelScaffold\MigrationParser;

abstract class BaseGenerator
{
    public function __construct(
        protected MigrationParser $parser,
        protected array $options = []
    ) {}

    abstract public function generate(): array;

    protected function writeFile(string $relativePath, string $content): array
    {
        $fullPath = base_path($relativePath);
        $exists   = file_exists($fullPath);

        if ($exists && !($this->options['force'] ?? false)) {
            return ['path' => $relativePath, 'skipped' => true];
        }

        if (!($this->options['dry_run'] ?? false)) {
            $this->ensureDirectory(dirname($fullPath));
            file_put_contents($fullPath, $content);
        }

        return ['path' => $relativePath, 'skipped' => false];
    }

    protected function ensureDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
    }

    protected function stubPath(string $name): string
    {
        $published = base_path("stubs/scaffold/{$name}.stub");
        if (file_exists($published)) {
            return $published;
        }
        return __DIR__ . '/../../stubs/' . $name . '.stub';
    }

    protected function stub(string $name): string
    {
        $path = $this->stubPath($name);

        if (!file_exists($path)) {
            throw new \RuntimeException("Stub introuvable : {$path}");
        }

        return file_get_contents($path);
    }

    protected function fill(string $template, array $vars): string
    {
        foreach ($vars as $key => $value) {
            $template = str_replace('{{ ' . $key . ' }}', $value, $template);
        }
        return $template;
    }

    protected function indent(array $lines, int $spaces = 8): string
    {
        $pad = str_repeat(' ', $spaces);
        return implode("\n", array_map(fn($l) => $pad . $l, $lines));
    }

    protected function isForce(): bool
    {
        return $this->options['force'] ?? false;
    }

    protected function isDryRun(): bool
    {
        return $this->options['dry_run'] ?? false;
    }

    protected function frontend(): string
    {
        return $this->options['frontend'] ?? 'none';
    }

    protected function testsFramework(): string
    {
        return $this->options['tests'] ?? 'pest';
    }
}