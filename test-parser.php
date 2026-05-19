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
use Salabanzi\LaravelScaffold\Generators\AuditReportGenerator;
use Salabanzi\LaravelScaffold\Generators\ChangelogGenerator;
use Salabanzi\LaravelScaffold\Generators\FilamentResourceGenerator;

$parser  = new MigrationParser('C:/tmp/test-scaffold/create_posts_table.php');
$options = ['force' => true, 'layers' => [1, 4, 5]];

$generators = [
    'AuditReport'      => new AuditReportGenerator($parser, $options),
    'Changelog'        => new ChangelogGenerator($parser, $options),
    'FilamentResource' => new FilamentResourceGenerator($parser, $options),
];

foreach ($generators as $name => $generator) {
    echo "\n[{$name}]\n";
    $files = $generator->generate();
    foreach ($files as $file) {
        $status = $file['skipped'] ? 'skip' : 'ok';
        echo "  [{$status}] {$file['path']}\n";
    }
}

echo "\nCouche 5 complete !\n";