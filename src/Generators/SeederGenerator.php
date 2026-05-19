<?php

namespace Salabanzi\LaravelScaffold\Generators;

use Illuminate\Support\Str;

class SeederGenerator extends BaseGenerator
{
    public function generate(): array
    {
        $model = $this->parser->getModelName();
        $count = 10;

        $content = <<<PHP
<?php

namespace Database\\Seeders;

use App\\Models\\{$model};
use Illuminate\\Database\\Seeder;

class {$model}Seeder extends Seeder
{
    public function run(): void
    {
        {$model}::factory({$count})->create();
    }
}
PHP;

        return [$this->writeFile("database/seeders/{$model}Seeder.php", $content)];
    }
}