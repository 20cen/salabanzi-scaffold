<?php

namespace Salabanzi\LaravelScaffold\Generators;

use Illuminate\Support\Str;

class AuditReportGenerator extends BaseGenerator
{
    public function generate(): array
    {
        $model   = $this->parser->getModelName();
        $table   = $this->parser->getTableName();
        $columns = $this->parser->getColumns();
        $indexes = $this->parser->getIndexes();
        $fks     = $this->parser->getForeignKeys();

        $warnings = [];
        $infos    = [];

        // Colonnes FK sans index
        $indexedCols = array_merge(...(!empty($indexes) ? $indexes : [[]]));
        foreach ($fks as $fk) {
            if (!in_array($fk['column'], $indexedCols)) {
                $warnings[] = "Index manquant sur `{$fk['column']}` (clé étrangère non indexée — risque de lenteur)";
            }
        }

        // N+1 potentiels
        foreach ($fks as $fk) {
            $method   = Str::camel(str_replace('_id', '', $fk['column']));
            $warnings[] = "N+1 potentiel : charger la relation `{$method}` avec `with('{$method}')` dans le Controller";
        }

        // Colonnes texte sans fulltext
        foreach ($columns as $col) {
            if (in_array($col['type'], ['text', 'longText', 'mediumText'])) {
                $infos[] = "Colonne `{$col['name']}` ({$col['type']}) : envisager un index FULLTEXT pour la recherche";
            }
        }

        // Colonnes non nullable sans default
        foreach ($columns as $col) {
            if (!$col['nullable'] && $col['default'] === null && !$col['is_foreign']) {
                $infos[] = "Colonne `{$col['name']}` : non nullable sans valeur par défaut";
            }
        }

        $warningsStr = !empty($warnings)
            ? implode("\n", array_map(fn($w) => "- ⚠️  {$w}", $warnings))
            : "_Aucun problème critique détecté._";

        $infosStr = !empty($infos)
            ? implode("\n", array_map(fn($i) => "- ℹ️  {$i}", $infos))
            : "_Aucune suggestion supplémentaire._";

        $date = date('Y-m-d H:i');

        // Tableau des colonnes
        $tableRows = '';
        foreach ($columns as $col) {
            $indexed  = in_array($col['name'], $indexedCols) || $col['unique'] ? 'Oui' : 'Non';
            $nullable = $col['nullable'] ? 'Oui' : 'Non';
            $default  = $col['default'] !== null ? $col['default'] : '-';
            $tableRows .= "\n| `{$col['name']}` | {$col['type']} | {$nullable} | {$indexed} | {$default} |";
        }

        $content = <<<MD
# Rapport d'audit — `{$table}`

> Généré le {$date} par `salabanzi/laravel-scaffold`

## Avertissements

{$warningsStr}

## Suggestions

{$infosStr}

## Résumé du schéma

| Colonne | Type | Nullable | Indexé | Default |
|---------|------|----------|--------|---------|{$tableRows}

## Relations détectées

MD;

        foreach ($fks as $fk) {
            $content .= "- `{$fk['column']}` → `{$fk['related_model']}` (`{$fk['on']}`.`{$fk['references']}`)\n";
        }

        $content .= "\n## Options de la migration\n\n";
        $content .= "- SoftDeletes : " . ($this->parser->hasSoftDeletes() ? 'Oui' : 'Non') . "\n";
        $content .= "- Timestamps  : " . ($this->parser->hasTimestamps() ? 'Oui' : 'Non') . "\n";

        return [$this->writeFile("storage/reports/{$table}-audit.md", $content)];
    }
}