<?php

namespace Salabanzi\LaravelScaffold\Generators;

class RequestGenerator extends BaseGenerator
{
    public function generate(): array
    {
        $model  = $this->parser->getModelName();
        $rules  = $this->parser->getValidationRules();
        $files  = [];

        // StoreRequest
        $files[] = $this->writeFile(
            "app/Http/Requests/Store{$model}Request.php",
            $this->buildRequest("Store{$model}Request", $this->formatRules($rules))
        );

        // UpdateRequest — toutes les règles passent en "sometimes"
        $updateRules = array_map(fn($r) => 'sometimes|' . $r, $rules);
        $files[] = $this->writeFile(
            "app/Http/Requests/Update{$model}Request.php",
            $this->buildRequest("Update{$model}Request", $this->formatRules($updateRules))
        );

        return $files;
    }

    private function buildRequest(string $class, string $rules): string
    {
        return <<<PHP
<?php

namespace App\\Http\\Requests;

use Illuminate\\Foundation\\Http\\FormRequest;

class {$class} extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
{$rules}
        ];
    }
}
PHP;
    }

    private function formatRules(array $rules): string
    {
        $lines = [];
        foreach ($rules as $field => $rule) {
            $lines[] = "            '{$field}' => '{$rule}',";
        }
        return implode("\n", $lines);
    }
}