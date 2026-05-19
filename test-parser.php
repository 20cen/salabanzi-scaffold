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
use Salabanzi\LaravelScaffold\Generators\PolicyGenerator;
use Salabanzi\LaravelScaffold\Generators\ObserverGenerator;
use Salabanzi\LaravelScaffold\Generators\EventGenerator;

$parser  = new MigrationParser('C:/tmp/test-scaffold/create_posts_table.php');
$options = ['force' => true];

$generators = [
    'Policy'   => new PolicyGenerator($parser, $options),
    'Observer' => new ObserverGenerator($parser, $options),
    'Event'    => new EventGenerator($parser, $options),
];

foreach ($generators as $name => $generator) {
    echo "\n[{$name}]\n";
    $files = $generator->generate();
    foreach ($files as $file) {
        $status = $file['skipped'] ? 'skip' : 'ok';
        echo "  [{$status}] {$file['path']}\n";
    }
}

echo "\nCouche 4 complete !\n";