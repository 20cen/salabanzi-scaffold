<?php

namespace Salabanzi\LaravelScaffold\Generators;

use Illuminate\Support\Str;

class PolicyGenerator extends BaseGenerator
{
    public function generate(): array
    {
        $model = $this->parser->getModelName();
        $var   = Str::camel($model);

        $content = <<<PHP
<?php

namespace App\\Policies;

use App\\Models\\{$model};
use App\\Models\\User;
use Illuminate\\Auth\\Access\\HandlesAuthorization;

class {$model}Policy
{
    use HandlesAuthorization;

    public function viewAny(User \$user): bool
    {
        return true;
    }

    public function view(User \$user, {$model} \${$var}): bool
    {
        return true;
    }

    public function create(User \$user): bool
    {
        return true;
    }

    public function update(User \$user, {$model} \${$var}): bool
    {
        return \$user->id === \${$var}->user_id
            || \$user->hasRole('admin');
    }

    public function delete(User \$user, {$model} \${$var}): bool
    {
        return \$user->id === \${$var}->user_id
            || \$user->hasRole('admin');
    }

    public function restore(User \$user, {$model} \${$var}): bool
    {
        return \$user->hasRole('admin');
    }

    public function forceDelete(User \$user, {$model} \${$var}): bool
    {
        return \$user->hasRole('admin');
    }
}
PHP;

        return [$this->writeFile("app/Policies/{$model}Policy.php", $content)];
    }
}