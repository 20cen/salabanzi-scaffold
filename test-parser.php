<?php

function base_path(string $path = ''): string {
    return 'C:/tmp/test-scaffold' . ($path ? '/' . $path : '');
}

function now() {
    return new class {
        public function format(string $f): string { return date($f); }
    };
}

require_once __DIR__ . '/vendor/autoload.php';

use Salabanzi\LaravelScaffold\MigrationParser;
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

$parser  = new MigrationParser('C:/tmp/test-scaffold/create_posts_table.php');
$options = ['force' => true, 'tests' => 'pest', 'layers' => [1, 2, 3, 4, 5]];

$layers = [
    '1 — Code de base' => [
        new ModelGenerator($parser, $options),
        new ControllerGenerator($parser, $options),
        new RequestGenerator($parser, $options),
        new RouteGenerator($parser, $options),
        new FactoryGenerator($parser, $options),
        new SeederGenerator($parser, $options),
        new TestGenerator($parser, $options),
        new OpenApiGenerator($parser, $options),
    ],
    '2 — Frontend' => [
        new TypeScriptSdkGenerator($parser, $options),
        new PostmanGenerator($parser, $options),
    ],
    '3 — DevOps' => [
        new DockerGenerator($parser, $options),
        new GithubActionsGenerator($parser, $options),
    ],
    '4 — DX Laravel' => [
        new PolicyGenerator($parser, $options),
        new ObserverGenerator($parser, $options),
        new EventGenerator($parser, $options),
    ],
    '5 — Intelligence' => [
        new FilamentResourceGenerator($parser, $options),
        new AuditReportGenerator($parser, $options),
        new ChangelogGenerator($parser, $options),
    ],
];

$totalOk      = 0;
$totalSkipped = 0;

foreach ($layers as $layerName => $generators) {
    echo "\n── Couche {$layerName}\n";
    foreach ($generators as $generator) {
        $files = $generator->generate();
        foreach ($files as $file) {
            $status = $file['skipped'] ? 'skip' : 'ok';
            echo "  [{$status}] {$file['path']}\n";
            $file['skipped'] ? $totalSkipped++ : $totalOk++;
        }
    }
}

echo "\n";
echo "✓ {$totalOk} fichier(s) générés\n";
echo "  {$totalSkipped} ignoré(s)\n";