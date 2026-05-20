<?php

namespace Salabanzi\LaravelScaffold\Generators;

use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class RouteGenerator extends BaseGenerator
{
    public function generate(): array
    {
        $model       = $this->parser->getModelName();
        $plural      = Str::plural(Str::snake($model));
        $controller  = "App\\Http\\Controllers\\Api\\{$model}Controller";
        $routeBlock  = "\nRoute::apiResource('{$plural}', {$controller}::class);\n";
        $apiPath     = base_path('routes/api.php');

        // Installe les routes API si pas encore configurées
        $this->ensureApiRoutesInstalled($apiPath);

        // Si le fichier n'existe toujours pas on le crée
        if (!file_exists($apiPath)) {
            $content = <<<PHP
<?php

use Illuminate\\Support\\Facades\\Route;
{$routeBlock}
PHP;
            return [$this->writeFile('routes/api.php', $content)];
        }

        // Si la route existe déjà on ne touche pas au fichier
        $existing = file_get_contents($apiPath);
        if (str_contains($existing, "'{$plural}'")) {
            return [['path' => 'routes/api.php', 'skipped' => true]];
        }

        // Sinon on append la route
        if (!$this->isDryRun()) {
            file_put_contents($apiPath, $existing . $routeBlock);
        }

        return [['path' => 'routes/api.php', 'skipped' => false]];
    }

    protected function ensureApiRoutesInstalled(string $apiPath): void
    {
        // Vérifie si api.php est chargé dans bootstrap/app.php
        $bootstrapPath = base_path('bootstrap/app.php');

        if (!file_exists($bootstrapPath)) return;

        $bootstrap = file_get_contents($bootstrapPath);

        // Si api.php est déjà référencé, rien à faire
        if (str_contains($bootstrap, 'api')) return;

        // Lance php artisan install:api automatiquement
        if (!$this->isDryRun()) {
            $process = new Process(['php', 'artisan', 'install:api', '--no-interaction']);
            $process->setWorkingDirectory(base_path());
            $process->setTimeout(60);
            $process->run();
        }
    }
}