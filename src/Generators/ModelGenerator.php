<?php

namespace Salabanzi\LaravelScaffold\Generators;

use Illuminate\Support\Str;

class ModelGenerator extends BaseGenerator
{
    public function generate(): array
    {
        $model   = $this->parser->getModelName();
        $columns = $this->parser->getColumns();
        $fks     = $this->parser->getForeignKeys();
        $casts   = $this->parser->getCasts();

        // Fillable
        $fillable = $this->indent(
            array_map(fn($col) => "'{$col['name']}',", $columns)
        );

        // Casts
        $castsLines = array_map(
            fn($k, $v) => "'{$k}' => '{$v}',",
            array_keys($casts),
            array_values($casts)
        );
        $castsStr = $this->indent($castsLines);

        // Traits et uses
        $traits = '';
        $uses   = '';

        if ($this->parser->hasSoftDeletes()) {
            $uses   = "use Illuminate\\Database\\Eloquent\\SoftDeletes;\n";
            $traits = "use SoftDeletes;\n    ";
        }

        // Relations BelongsTo
        $relations = '';
        foreach ($fks as $fk) {
            $method   = Str::camel(str_replace('_id', '', $fk['column']));
            $related  = $fk['related_model'];
            $relations .= "\n    public function {$method}(): \\Illuminate\\Database\\Eloquent\\Relations\\BelongsTo\n";
            $relations .= "    {\n";
            $relations .= "        return \$this->belongsTo({$related}::class);\n";
            $relations .= "    }\n";
        }

        $content = <<<PHP
<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
{$uses}
class {$model} extends Model
{
    use HasFactory;
    {$traits}
    protected \$fillable = [
{$fillable}
    ];

    protected \$casts = [
{$castsStr}
    ];
{$relations}}
PHP;

        return [$this->writeFile("app/Models/{$model}.php", $content)];
    }
}