<?php

namespace Salabanzi\LaravelScaffold\Generators;

use Illuminate\Support\Str;

class TestGenerator extends BaseGenerator
{
    public function generate(): array
    {
        $model  = $this->parser->getModelName();
        $plural = Str::plural(Str::snake($model));
        $var    = Str::camel($model);

        $content = $this->testsFramework() === 'pest'
            ? $this->buildPest($model, $plural, $var)
            : $this->buildPhpUnit($model, $plural, $var);

        return [$this->writeFile("tests/Feature/{$model}Test.php", $content)];
    }

    private function buildPest(string $model, string $plural, string $var): string
    {
        return <<<PHP
<?php

use App\\Models\\{$model};
use App\\Models\\User;
use Illuminate\\Foundation\\Testing\\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    \$this->user = User::factory()->create();
});

test('can list {$plural}', function () {
    {$model}::factory(3)->create();

    \$this->actingAs(\$this->user)
        ->getJson('/api/{$plural}')
        ->assertOk()
        ->assertJsonStructure(['data', 'meta']);
});

test('can create {$var}', function () {
    \$payload = {$model}::factory()->make()->toArray();

    \$this->actingAs(\$this->user)
        ->postJson('/api/{$plural}', \$payload)
        ->assertCreated()
        ->assertJsonFragment(\$payload);
});

test('can show {$var}', function () {
    \${$var} = {$model}::factory()->create();

    \$this->actingAs(\$this->user)
        ->getJson("/api/{$plural}/{\${$var}->id}")
        ->assertOk()
        ->assertJsonPath('data.id', \${$var}->id);
});

test('can update {$var}', function () {
    \${$var} = {$model}::factory()->create();
    \$payload = {$model}::factory()->make()->toArray();

    \$this->actingAs(\$this->user)
        ->putJson("/api/{$plural}/{\${$var}->id}", \$payload)
        ->assertOk();
});

test('can delete {$var}', function () {
    \${$var} = {$model}::factory()->create();

    \$this->actingAs(\$this->user)
        ->deleteJson("/api/{$plural}/{\${$var}->id}")
        ->assertNoContent();

    \$this->assertDatabaseMissing('{$plural}', ['id' => \${$var}->id]);
});

test('requires authentication', function () {
    \$this->getJson('/api/{$plural}')->assertUnauthorized();
});

test('validates required fields', function () {
    \$this->actingAs(\$this->user)
        ->postJson('/api/{$plural}', [])
        ->assertUnprocessable()
        ->assertJsonStructure(['errors']);
});
PHP;
    }

    private function buildPhpUnit(string $model, string $plural, string $var): string
    {
        $class = "{$model}Test";

        return <<<PHP
<?php

namespace Tests\\Feature;

use App\\Models\\{$model};
use App\\Models\\User;
use Illuminate\\Foundation\\Testing\\RefreshDatabase;
use Tests\\TestCase;

class {$class} extends TestCase
{
    use RefreshDatabase;

    private User \$user;

    protected function setUp(): void
    {
        parent::setUp();
        \$this->user = User::factory()->create();
    }

    public function test_can_list_{$plural}(): void
    {
        {$model}::factory(3)->create();

        \$this->actingAs(\$this->user)
            ->getJson('/api/{$plural}')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta']);
    }

    public function test_can_create_{$var}(): void
    {
        \$payload = {$model}::factory()->make()->toArray();

        \$this->actingAs(\$this->user)
            ->postJson('/api/{$plural}', \$payload)
            ->assertCreated()
            ->assertJsonFragment(\$payload);
    }

    public function test_can_show_{$var}(): void
    {
        \${$var} = {$model}::factory()->create();

        \$this->actingAs(\$this->user)
            ->getJson("/api/{$plural}/{\${$var}->id}")
            ->assertOk()
            ->assertJsonPath('data.id', \${$var}->id);
    }

    public function test_can_update_{$var}(): void
    {
        \${$var} = {$model}::factory()->create();
        \$payload = {$model}::factory()->make()->toArray();

        \$this->actingAs(\$this->user)
            ->putJson("/api/{$plural}/{\${$var}->id}", \$payload)
            ->assertOk();
    }

    public function test_can_delete_{$var}(): void
    {
        \${$var} = {$model}::factory()->create();

        \$this->actingAs(\$this->user)
            ->deleteJson("/api/{$plural}/{\${$var}->id}")
            ->assertNoContent();

        \$this->assertDatabaseMissing('{$plural}', ['id' => \${$var}->id]);
    }

    public function test_requires_authentication(): void
    {
        \$this->getJson('/api/{$plural}')->assertUnauthorized();
    }

    public function test_validates_required_fields(): void
    {
        \$this->actingAs(\$this->user)
            ->postJson('/api/{$plural}', [])
            ->assertUnprocessable()
            ->assertJsonStructure(['errors']);
    }
}
PHP;
    }
}