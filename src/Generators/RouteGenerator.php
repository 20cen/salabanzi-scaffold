<?php

namespace Salabanzi\LaravelScaffold\Generators;

use Illuminate\Support\Str;

class RouteGenerator extends BaseGenerator
{
    public function generate(): array
    {
        // Installe l'auth si nécessaire
        $this->ensureAuthInstalled();

        $model      = $this->parser->getModelName();
        $plural     = Str::plural(Str::snake($model));
        $controller = "App\\Http\\Controllers\\Api\\{$model}Controller";
        $routeBlock = "\nRoute::middleware('auth:sanctum')->group(function () {\n    Route::apiResource('{$plural}', {$controller}::class);\n});\n";
        $apiPath    = base_path('routes/api.php'); 
        $files      = [];

        // Étape 1 — S'assure que routes/api.php est chargé dans bootstrap/app.php
        $bootstrapFile = $this->ensureApiInBootstrap();
        if ($bootstrapFile) {
            $files[] = $bootstrapFile;
        }

        // Étape 2 — Crée ou met à jour routes/api.php
        if (!file_exists($apiPath)) {
            $content = <<<PHP
<?php

use Illuminate\\Support\\Facades\\Route;
{$routeBlock}
PHP;
            $files[] = $this->writeFile('routes/api.php', $content);
            return $files;
        }

        // Si la route existe déjà — skip
        $existing = file_get_contents($apiPath);
        if (str_contains($existing, "'{$plural}'")) {
            $files[] = ['path' => 'routes/api.php', 'skipped' => true];
            return $files;
        }

        // Sinon on append la route
        if (!$this->isDryRun()) {
            file_put_contents($apiPath, $existing . $routeBlock);
        }

        $files[] = ['path' => 'routes/api.php', 'skipped' => false];
        return $files;
    }

    protected function ensureAuthInstalled(): void
    {
        if ($this->isDryRun()) return;

        $composer = base_path('composer.json');
        if (!file_exists($composer)) return;

        $data = json_decode(file_get_contents($composer), true);
        $deps = array_merge($data['require'] ?? [], $data['require-dev'] ?? []);

        // Sanctum déjà installé
        if (isset($deps['laravel/sanctum'])) return;

        // Passport déjà installé
        if (isset($deps['laravel/passport'])) return;

        // JWT déjà installé
        if (isset($deps['tymon/jwt-auth'])) return;

        // Aucun auth installé → installe Sanctum automatiquement
        $process = new \Symfony\Component\Process\Process(
            ['php', 'artisan', 'install:api', '--no-interaction'],
            base_path()
        );
        $process->setTimeout(120);
        $process->run();
    }

    protected function ensureApiInBootstrap(): ?array
    {
        $bootstrapPath = base_path('bootstrap/app.php');

        if (!file_exists($bootstrapPath)) return null;

        $content = file_get_contents($bootstrapPath);

        // Déjà configuré
        if (str_contains($content, "'api'") || str_contains($content, '"api"')
            || str_contains($content, 'api:')) {
            return null;
        }

        // Patch bootstrap/app.php — ajoute api: après web:
        $patched = preg_replace(
            "/(web:\s*__DIR__\.'\/\.\.\/routes\/web\.php',)/",
            "$1\n        api: __DIR__.'/../routes/api.php',",
            $content
        );

        if ($patched === $content) return null;

        if (!$this->isDryRun()) {
            file_put_contents($bootstrapPath, $patched);
        }

        return ['path' => 'bootstrap/app.php', 'skipped' => false];
    }
}