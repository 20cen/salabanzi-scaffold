<?php

namespace Salabanzi\LaravelScaffold;

use Illuminate\Support\Str;

class MigrationParser
{
    protected string $content;
    protected string $modelName;
    protected string $tableName;

    public function __construct(string $migrationPath)
    {
        $this->content   = file_get_contents($migrationPath);
        $this->tableName = $this->extractTableName();
        $this->modelName = Str::studly(Str::singular($this->tableName));
    }

    public function getModelName(): string
    {
        return $this->modelName;
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function getColumns(): array
    {
        $columns = [];

        preg_match_all(
            '/\$table->(\w+)\(\s*[\'"](\w+)[\'"](?:,\s*(\d+))?\s*\)((?:->[\w]+\([^)]*\))*);/',
            $this->content,
            $matches,
            PREG_SET_ORDER
        );

        foreach ($matches as $m) {
            $type      = $m[1];
            $name      = $m[2];
            $length    = $m[3] ?? null;
            $modifiers = $m[4] ?? '';

            // On ignore les colonnes système
            if (in_array($type, ['timestamps', 'softDeletes', 'rememberToken', 'foreign'])) {
                continue;
            }

            if ($name === 'id') {
                continue;
            }

            // Normalise foreignId → unsignedBigInteger
            if ($type === 'foreignId') {
                $type = 'unsignedBigInteger';
            }

            $columns[] = [
                'name'       => $name,
                'type'       => $type,
                'length'     => $length ? (int) $length : null,
                'nullable'   => str_contains($modifiers, '->nullable()'),
                'unique'     => str_contains($modifiers, '->unique()'),
                'default'    => $this->extractDefault($modifiers),
                'unsigned'   => str_contains($modifiers, '->unsigned()'),
                'is_foreign' => str_ends_with($name, '_id'),
            ];
        }

        return $columns;
    }

    public function getForeignKeys(): array
    {
        $fks = [];

        // Cas explicite : $table->foreign('user_id')->references('id')->on('users')
        preg_match_all(
            '/\$table->foreign\([\'"](\w+)[\'"]\)->references\([\'"](\w+)[\'"]\)->on\([\'"](\w+)[\'"]\)/',
            $this->content,
            $matches,
            PREG_SET_ORDER
        );

        foreach ($matches as $m) {
            $fks[] = [
                'column'        => $m[1],
                'references'    => $m[2],
                'on'            => $m[3],
                'related_model' => Str::studly(Str::singular($m[3])),
            ];
        }

        // Cas implicite : $table->foreignId('user_id')->constrained()
        preg_match_all(
            '/\$table->foreignId\([\'"](\w+)[\'"]\)/',
            $this->content,
            $implicit,
            PREG_SET_ORDER
        );

        foreach ($implicit as $m) {
            $col   = $m[1];
            $table = Str::plural(str_replace('_id', '', $col));

            $fks[] = [
                'column'        => $col,
                'references'    => 'id',
                'on'            => $table,
                'related_model' => Str::studly(Str::singular($table)),
            ];
        }

        return $fks;
    }

    public function hasSoftDeletes(): bool
    {
        return str_contains($this->content, 'softDeletes');
    }

    public function hasTimestamps(): bool
    {
        return str_contains($this->content, '->timestamps()');
    }

    public function getIndexes(): array
    {
        $indexes = [];

        preg_match_all(
            '/\$table->index\(\[?([^\]]+)\]?\)/',
            $this->content,
            $matches,
            PREG_SET_ORDER
        );

        foreach ($matches as $m) {
            $cols      = array_map(fn($c) => trim($c, " '\""), explode(',', $m[1]));
            $indexes[] = $cols;
        }

        return $indexes;
    }

    public function getValidationRules(): array
    {
        $rules = [];

        foreach ($this->getColumns() as $col) {
            $rule = [];

            if (!$col['nullable']) {
                $rule[] = 'required';
            } else {
                $rule[] = 'nullable';
            }

            match ($col['type']) {
                'string', 'char'                        => $rule[] = 'string|max:' . ($col['length'] ?? 255),
                'text', 'longText', 'mediumText'        => $rule[] = 'string',
                'integer', 'bigInteger',
                'unsignedBigInteger', 'unsignedInteger' => $rule[] = 'integer',
                'float', 'double', 'decimal'            => $rule[] = 'numeric',
                'boolean'                               => $rule[] = 'boolean',
                'date'                                  => $rule[] = 'date',
                'dateTime', 'timestamp'                 => $rule[] = 'date_format:Y-m-d H:i:s',
                'json'                                  => $rule[] = 'array',
                default                                 => $rule[] = 'string',
            };

            if ($col['unique']) {
                $rule[] = 'unique:' . $this->tableName . ',' . $col['name'];
            }

            if ($col['is_foreign']) {
                $related = Str::plural(str_replace('_id', '', $col['name']));
                $rule[]  = 'exists:' . $related . ',id';
            }

            $rules[$col['name']] = implode('|', $rule);
        }

        return $rules;
    }

    public function getCasts(): array
    {
        $casts = [];

        foreach ($this->getColumns() as $col) {
            $cast = match ($col['type']) {
                'boolean'                               => 'boolean',
                'integer', 'bigInteger',
                'unsignedBigInteger', 'unsignedInteger' => 'integer',
                'float', 'double'                       => 'float',
                'decimal'                               => 'decimal:2',
                'date'                                  => 'date',
                'dateTime', 'timestamp'                 => 'datetime',
                'json'                                  => 'array',
                default                                 => null,
            };

            if ($cast) {
                $casts[$col['name']] = $cast;
            }
        }

        return $casts;
    }

    public function getFillable(): array
    {
        return array_map(
            fn($col) => $col['name'],
            $this->getColumns()
        );
    }

    protected function extractTableName(): string
    {
        preg_match('/Schema::create\([\'"](\w+)[\'"]/', $this->content, $m);
        return $m[1] ?? 'unknown';
    }

    protected function extractDefault(string $modifiers): mixed
    {
        if (preg_match('/->default\(([^)]+)\)/', $modifiers, $m)) {
            $val = trim($m[1], "'\"");
            return is_numeric($val) ? (float) $val : $val;
        }

        return null;
    }
}