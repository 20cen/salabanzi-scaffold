<?php

namespace Salabanzi\LaravelScaffold\Commands;

use Illuminate\Console\Command;
use Salabanzi\LaravelScaffold\MigrationParser;
use Salabanzi\LaravelScaffold\Support\FrontendDetector;
use Salabanzi\LaravelScaffold\Generators\ModelGenerator;
use Salabanzi\LaravelScaffold\Generators\ControllerGenerator;
use Salabanzi\LaravelScaffold\Generators\RequestGenerator;
use Salabanzi\LaravelScaffold\Generators\RouteGenerator;
use Salabanzi\LaravelScaffold\Generators\FactoryGenerator;
use Salabanzi\LaravelScaffold\Generators\SeederGenerator;
use Salabanzi\LaravelScaffold\Generators\TestGenerator;
use Salabanzi\LaravelScaffold\Generators\OpenApiGenerator;
use Salabanzi\LaravelScaffold\Generators\PolicyGenerator;
use Salabanzi\LaravelScaffold\Generators\ObserverGenerator;
use Salabanzi\LaravelScaffold\Generators\EventGenerator;
use Salabanzi\LaravelScaffold\Generators\TypeScriptSdkGenerator;
use Salabanzi\LaravelScaffold\Generators\PostmanGenerator;
use Salabanzi\LaravelScaffold\Generators\DockerGenerator;
use Salabanzi\LaravelScaffold\Generators\GithubActionsGenerator;
use Salabanzi\LaravelScaffold\Generators\FilamentResourceGenerator;
use Salabanzi\LaravelScaffold\Generators\AuditReportGenerator;
use Salabanzi\LaravelScaffold\Generators\ChangelogGenerator;

class ScaffoldGenerate extends Command
{
    protected $signature = 'scaffold:generate
                            {migration : Chemin vers la migration (relatif à la racine)}
                            {--layers=default : Couches à générer : 1,2,3,4,5 ou all ou default}
                            {--frontend= : Override frontend : vue, react, livewire, none}
                            {--tests= : Override framework de tests : pest, phpunit}
                            {--force : Écraser les fichiers existants}
                            {--dry-run : Afficher sans écrire}';

    protected $description = 'Génère le scaffold complet depuis une migration (5 couches)';

    protected array $layerMap = [
        1 => [
            ModelGenerator::class,
            ControllerGenerator::class,
            RequestGenerator::class,
            RouteGenerator::class,
            FactoryGenerator::class,
            SeederGenerator::class,
            TestGenerator::class,
            OpenApiGenerator::class,
        ],
        2 => [
            TypeScriptSdkGenerator::class,
            PostmanGenerator::class,
        ],
        3 => [
            DockerGenerator::class,
            GithubActionsGenerator::class,
        ],
        4 => [
            PolicyGenerator::class,
            ObserverGenerator::class,
            EventGenerator::class,
        ],
        5 => [
            FilamentResourceGenerator::class,
            AuditReportGenerator::class,
            ChangelogGenerator::class,
        ],
    ];

    public function handle(): int
    {
        $migrationPath = base_path($this->argument('migration'));

        if (!file_exists($migrationPath)) {
            $this->error("Migration introuvable : {$migrationPath}");
            return self::FAILURE;
        }

        $parser  = new MigrationParser($migrationPath);
        $options = $this->resolveOptions();
        $layers  = $this->resolveLayers($options['layers']);

        $this->printHeader($parser, $options);

        $totalOk      = 0;
        $totalSkipped = 0;

        foreach ($layers as $layerNum => $generators) {
            $this->line("<fg=yellow>── Couche {$layerNum}</>");

            foreach ($generators as $generatorClass) {
                $generator = new $generatorClass($parser, $options);
                $files     = $generator->generate();

                foreach ($files as $file) {
                    if ($file['skipped']) {
                        $this->line("   <fg=gray>skip</>  {$file['path']}");
                        $totalSkipped++;
                    } else {
                        $this->line("   <fg=green>ok</>    {$file['path']}");
                        $totalOk++;
                    }
                }
            }

            $this->newLine();
        }

        $this->printSummary($parser, $totalOk, $totalSkipped, $options);

        return self::SUCCESS;
    }

    protected function resolveOptions(): array
    {
        $configLayers = config('scaffold.layers', [1, 4, 5]);

        $layersOpt = $this->option('layers');
        $layers    = match($layersOpt) {
            'default' => $configLayers,
            'all'     => [1, 2, 3, 4, 5],
            default   => array_map('intval', explode(',', $layersOpt)),
        };

        return [
            'layers'    => $layers,
            'force'     => $this->option('force'),
            'dry_run'   => $this->option('dry-run'),
            'frontend'  => $this->option('frontend') ?? FrontendDetector::detect(),
            'tests'     => $this->option('tests')    ?? config('scaffold.tests.framework', 'pest'),
            'namespace' => config('scaffold.namespace', 'App'),
        ];
    }

    protected function resolveLayers(array $selected): array
    {
        return array_filter(
            $this->layerMap,
            fn($k) => in_array($k, $selected),
            ARRAY_FILTER_USE_KEY
        );
    }

    protected function printHeader(MigrationParser $parser, array $options): void
    {
        $this->newLine();
        $this->line("<fg=cyan;options=bold>  Salabanzi Scaffold</>");
        $this->line("  ─────────────────────────────────");
        $this->line("  Model    : <fg=white>{$parser->getModelName()}</>");
        $this->line("  Table    : <fg=white>{$parser->getTableName()}</>");
        $this->line("  Colonnes : <fg=white>" . count($parser->getColumns()) . "</>");
        $this->line("  Couches  : <fg=white>" . implode(', ', $options['layers']) . "</>");
        $this->line("  Frontend : <fg=white>{$options['frontend']}</>");
        $this->line("  Tests    : <fg=white>{$options['tests']}</>");

        if ($options['dry_run']) {
            $this->line("  <fg=yellow>Mode dry-run — aucun fichier ne sera écrit</>");
        }

        $this->newLine();
    }

    protected function printSummary(MigrationParser $parser, int $ok, int $skipped, array $options): void
    {
        $model = $parser->getModelName();

        if ($options['dry_run']) {
            $this->warn("Dry-run : {$ok} fichier(s) seraient générés, {$skipped} ignorés.");
            return;
        }

        $this->info("✓ {$ok} fichier(s) générés pour {$model}. {$skipped} ignoré(s).");
        $this->newLine();
        $this->line("  Prochaines étapes :");
        $this->line("  <fg=gray>php artisan migrate</>");
        $this->line("  <fg=gray>php artisan db:seed --class={$model}Seeder</>");
        $this->line("  <fg=gray>php artisan test --filter={$model}Test</>");
        $this->newLine();
    }
}