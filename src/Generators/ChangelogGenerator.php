<?php

namespace Salabanzi\LaravelScaffold\Generators;

use Illuminate\Support\Str;

class ChangelogGenerator extends BaseGenerator
{
    public function generate(): array
    {
        $model   = $this->parser->getModelName();
        $table   = $this->parser->getTableName();
        $columns = $this->parser->getColumns();
        $date    = date('Y-m-d');
        $version = $this->resolveNextVersion();

        $colList = implode(', ', array_map(
            fn($c) => "`{$c['name']}`",
            $columns
        ));

        $layers = $this->options['layers'] ?? [1, 4, 5];
        $layerList = implode(', ', array_map(fn($l) => "couche {$l}", $layers));

        $entry = <<<MD


## [{$version}] — {$date}

### Ajouté
- Scaffold `{$model}` (table `{$table}`) — {$layerList}
- Colonnes : {$colList}
- Endpoints REST : `GET|POST /{$table}`, `GET|PUT|DELETE /{$table}/{id}`
- Policy, Observer, Events (`Created`, `Updated`, `Deleted`)
- Tests Feature (7 cas couverts)
- Documentation OpenAPI : `storage/api-docs/{$table}.yaml`
- Rapport d'audit : `storage/reports/{$table}-audit.md`

MD;

        $changelogPath = base_path('CHANGELOG.md');

        if (!file_exists($changelogPath)) {
            $existing = "# Changelog\n\nToutes les modifications notables de ce projet.\n";
        } else {
            $existing = file_get_contents($changelogPath);
        }

        // Évite les doublons
        if (str_contains($existing, "Scaffold `{$model}`")) {
            return [['path' => 'CHANGELOG.md', 'skipped' => true]];
        }

        if (!$this->isDryRun()) {
            $this->ensureDirectory(dirname($changelogPath));
            file_put_contents($changelogPath, $existing . $entry);
        }

        return [['path' => 'CHANGELOG.md', 'skipped' => false]];
    }

    private function resolveNextVersion(): string
    {
        $path = base_path('CHANGELOG.md');

        if (!file_exists($path)) {
            return '1.0.0';
        }

        preg_match_all('/## \[(\d+)\.(\d+)\.(\d+)\]/', file_get_contents($path), $m);

        if (empty($m[0])) {
            return '1.0.0';
        }

        $major = (int) $m[1][0];
        $minor = (int) $m[2][0];
        $patch = (int) $m[3][0];

        return "{$major}.{$minor}." . ($patch + 1);
    }
}