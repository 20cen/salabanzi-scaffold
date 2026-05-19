<?php

function base_path(string $path = ''): string {
    return 'C:/tmp/test-scaffold' . ($path ? '/' . $path : '');
}

function now() {
    return new class {
        public function format(string $f): string {
            return date($f);
        }
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

$parser  = new MigrationParser('C:/tmp/test-scaffold/create_posts_table.php');
$options = ['force' => true, 'tests' => 'pest'];

$generators = [
    'Model'      => new ModelGenerator($parser, $options),
    'Controller' => new ControllerGenerator($parser, $options),
    'Request'    => new RequestGenerator($parser, $options),
    'Route'      => new RouteGenerator($parser, $options),
    'Factory'    => new FactoryGenerator($parser, $options),
    'Seeder'     => new SeederGenerator($parser, $options),
    'Test'       => new TestGenerator($parser, $options),
    'OpenApi'    => new OpenApiGenerator($parser, $options),
];

foreach ($generators as $name => $generator) {
    echo "\n[{$name}]\n";
    $files = $generator->generate();
    foreach ($files as $file) {
        $status = $file['skipped'] ? 'skip' : 'ok';
        echo "  [{$status}] {$file['path']}\n";
    }
}

echo "\n\nCouche 1 complete !\n";