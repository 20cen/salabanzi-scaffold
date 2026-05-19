<?php

namespace Salabanzi\LaravelScaffold\Generators;

use Illuminate\Support\Str;

class RouteGenerator extends BaseGenerator
{
    public function generate(): array
    {
        $model      = $this->parser->getModelName();
        $plural     = Str::plural(Str::snake($model));
        $controller = "App\\Http\\Controllers\\Api\\{$model}Controller";

        $routeBlock = "\nRoute::apiResource('{$plural}', {$controller}::class);\n";

        $apiPath = base_path('routes/api.php');

        // Si le fichier n'existe pas encore on le crée
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
}