<?php

namespace Salabanzi\LaravelScaffold\Generators;

use Illuminate\Support\Str;

class ControllerGenerator extends BaseGenerator
{
    public function generate(): array
    {
        $model      = $this->parser->getModelName();
        $var        = Str::camel($model);
        $plural     = Str::camel(Str::plural($model));
        $pluralSnake = Str::plural(Str::snake($model));

        $content = <<<PHP
<?php

namespace App\\Http\\Controllers\\Api;

use App\\Http\\Controllers\\Controller;
use App\\Http\\Requests\\Store{$model}Request;
use App\\Http\\Requests\\Update{$model}Request;
use App\\Http\\Resources\\{$model}Resource;
use App\\Models\\{$model};
use Illuminate\\Http\\JsonResponse;
use Illuminate\\Http\\Resources\\Json\\AnonymousResourceCollection;

class {$model}Controller extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        \${$plural} = {$model}::query()
            ->when(request('search'), fn(\$q, \$s) => \$q->where('name', 'like', "%{\$s}%"))
            ->when(request('sort'), fn(\$q, \$s) => \$q->orderBy(\$s, request('direction', 'asc')))
            ->paginate(request('per_page', 15));

        return {$model}Resource::collection(\${$plural});
    }

    public function store(Store{$model}Request \$request): JsonResponse
    {
        \${$var} = {$model}::create(\$request->validated());

        return response()->json(new {$model}Resource(\${$var}), 201);
    }

    public function show({$model} \${$var}): JsonResponse
    {
        return response()->json(new {$model}Resource(\${$var}));
    }

    public function update(Update{$model}Request \$request, {$model} \${$var}): JsonResponse
    {
        \${$var}->update(\$request->validated());

        return response()->json(new {$model}Resource(\${$var}));
    }

    public function destroy({$model} \${$var}): JsonResponse
    {
        \${$var}->delete();

        return response()->json(null, 204);
    }
}
PHP;

        return [$this->writeFile("app/Http/Controllers/Api/{$model}Controller.php", $content)];
    }
}