<?php

namespace Salabanzi\LaravelScaffold\Generators;

use Illuminate\Support\Str;

class ResourceGenerator extends BaseGenerator
{
    public function generate(): array
    {
        $model   = $this->parser->getModelName();
        $columns = $this->parser->getColumns();

        $fields = '';
        foreach ($columns as $col) {
            $fields .= "            '{$col['name']}' => \$this->{$col['name']},\n";
        }

        $content = <<<PHP
<?php

namespace App\\Http\\Resources;

use Illuminate\\Http\\Request;
use Illuminate\\Http\\Resources\\Json\\JsonResource;

class {$model}Resource extends JsonResource
{
    public function toArray(Request \$request): array
    {
        return [
            'id'         => \$this->id,
{$fields}            'created_at' => \$this->created_at,
            'updated_at' => \$this->updated_at,
        ];
    }
}
PHP;

        return [$this->writeFile("app/Http/Resources/{$model}Resource.php", $content)];
    }
}