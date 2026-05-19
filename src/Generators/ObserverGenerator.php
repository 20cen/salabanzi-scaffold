<?php

namespace Salabanzi\LaravelScaffold\Generators;

use Illuminate\Support\Str;

class ObserverGenerator extends BaseGenerator
{
    public function generate(): array
    {
        $model = $this->parser->getModelName();
        $var   = Str::camel($model);
        $snake = Str::snake($model);

        $content = <<<PHP
<?php

namespace App\\Observers;

use App\\Events\\{$model}Created;
use App\\Events\\{$model}Updated;
use App\\Events\\{$model}Deleted;
use App\\Models\\{$model};
use Illuminate\\Support\\Facades\\Log;

class {$model}Observer
{
    public function created({$model} \${$var}): void
    {
        event(new {$model}Created(\${$var}));

        Log::info('{$model} created', [
            'id'   => \${$var}->id,
            'user' => auth()->id(),
        ]);
    }

    public function updated({$model} \${$var}): void
    {
        event(new {$model}Updated(\${$var}));

        Log::info('{$model} updated', [
            'id'      => \${$var}->id,
            'changes' => \${$var}->getChanges(),
        ]);
    }

    public function deleted({$model} \${$var}): void
    {
        event(new {$model}Deleted(\${$var}));

        Log::info('{$model} deleted', [
            'id'   => \${$var}->id,
            'user' => auth()->id(),
        ]);
    }
}
PHP;

        return [$this->writeFile("app/Observers/{$model}Observer.php", $content)];
    }
}