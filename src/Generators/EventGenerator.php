<?php

namespace Salabanzi\LaravelScaffold\Generators;

use Illuminate\Support\Str;

class EventGenerator extends BaseGenerator
{
    public function generate(): array
    {
        $model = $this->parser->getModelName();
        $var   = Str::camel($model);
        $files = [];

        foreach (['Created', 'Updated', 'Deleted'] as $event) {
            $content = <<<PHP
<?php

namespace App\\Events;

use App\\Models\\{$model};
use Illuminate\\Broadcasting\\InteractsWithSockets;
use Illuminate\\Foundation\\Events\\Dispatchable;
use Illuminate\\Queue\\SerializesModels;

class {$model}{$event}
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly {$model} \${$var}
    ) {}
}
PHP;

            $files[] = $this->writeFile(
                "app/Events/{$model}{$event}.php",
                $content
            );
        }

        return $files;
    }
}