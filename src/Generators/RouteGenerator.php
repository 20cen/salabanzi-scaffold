<?php

namespace Salabanzi\LaravelScaffold\Generators;

use Illuminate\Support\Str;
use Salabanzi\LaravelScaffold\Support\AuthDetector;

class RouteGenerator extends BaseGenerator
{
    public function generate(): array
    {
        $model      = $this->parser->getModelName();
        $plural     = Str::plural(Str::snake($model));
        $controller = "App\\Http\\Controllers\\Api\\{$model}Controller";
        $files      = [];

        // Étape 1 — Installe Sanctum si aucun auth détecté
        if (!$this->isDryRun()) {
            AuthDetector::installSanctumIfNeeded();
        }

        // Étape 2 — Détecte le middleware selon l'auth installé
        $middleware  = AuthDetector::middleware();
        $routeBlock  = "\nRoute::middleware('{$middleware}')->group(function () {\n    Route::apiResource('{$plural}', {$controller}::class);\n});\n";

        // Étape 3 — S'assure que routes/api.php est chargé
        $bootstrapFile = $this->ensureApiInBootstrap();
        if ($bootstrapFile) {
            $files[] = $bootstrapFile;
        }

        $apiPath = base_path('routes/api.php');

        // Étape 4 — Crée ou met à jour routes/api.php
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

        // Sinon on append
        if (!$this->isDryRun()) {
            file_put_contents($apiPath, $existing . $routeBlock);
        }

        $files[] = ['path' => 'routes/api.php', 'skipped' => false];
        return $files;
    }

    protected function ensureApiInBootstrap(): ?array
    {
        $bootstrapPath = base_path('bootstrap/app.php');
        if (!file_exists($bootstrapPath)) return null;

        $content = file_get_contents($bootstrapPath);

        if (str_contains($content, "'api'") || str_contains($content, '"api"')
            || str_contains($content, 'api:')) {
            return null;
        }

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