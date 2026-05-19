<?php

namespace Salabanzi\LaravelScaffold\Generators;

use Illuminate\Support\Str;

class FactoryGenerator extends BaseGenerator
{
    public function generate(): array
    {
        $model   = $this->parser->getModelName();
        $columns = $this->parser->getColumns();
        $fks     = $this->parser->getForeignKeys();

        $fields = [];
        foreach ($columns as $col) {
            // Les FK sont gérées séparément
            if ($col['is_foreign']) continue;
            $faker    = $this->fakerMethod($col);
            $fields[] = "            '{$col['name']}' => {$faker},";
        }

        // FK avec factory()
        foreach ($fks as $fk) {
            $related  = $fk['related_model'];
            $fields[] = "            '{$fk['column']}' => {$related}::factory(),";
        }

        $fieldsStr = implode("\n", $fields);

        $fkImports = implode("\n", array_map(
            fn($fk) => "use App\\Models\\{$fk['related_model']};",
            $fks
        ));

        $content = <<<PHP
<?php

namespace Database\\Factories;

use App\\Models\\{$model};
{$fkImports}
use Illuminate\\Database\\Eloquent\\Factories\\Factory;

class {$model}Factory extends Factory
{
    protected \$model = {$model}::class;

    public function definition(): array
    {
        return [
{$fieldsStr}
        ];
    }
}
PHP;

        return [$this->writeFile("database/factories/{$model}Factory.php", $content)];
    }

    private function fakerMethod(array $col): string
    {
        $name = $col['name'];

        if (str_contains($name, 'email'))   return 'fake()->safeEmail()';
        if (str_contains($name, 'name'))    return 'fake()->name()';
        if (str_contains($name, 'phone'))   return 'fake()->phoneNumber()';
        if (str_contains($name, 'address')) return 'fake()->address()';
        if (str_contains($name, 'url'))     return 'fake()->url()';
        if (str_contains($name, 'slug'))    return 'fake()->slug()';
        if (str_contains($name, 'title'))   return 'fake()->sentence(3)';
        if (str_contains($name, 'content') || str_contains($name, 'body'))
            return 'fake()->paragraph()';
        if (str_contains($name, 'description'))
            return 'fake()->text(200)';

        return match($col['type']) {
            'string', 'char'                        => 'fake()->word()',
            'text', 'longText', 'mediumText'        => 'fake()->paragraph()',
            'integer', 'bigInteger',
            'unsignedBigInteger', 'unsignedInteger' => 'fake()->numberBetween(1, 1000)',
            'float', 'double', 'decimal'            => 'fake()->randomFloat(2, 1, 1000)',
            'boolean'                               => 'fake()->boolean()',
            'date'                                  => 'fake()->date()',
            'dateTime', 'timestamp'                 => 'fake()->dateTime()',
            'json'                                  => '[]',
            default                                 => 'fake()->word()',
        };
    }
}