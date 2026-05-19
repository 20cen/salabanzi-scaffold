<?php

namespace Salabanzi\LaravelScaffold\Generators;

use Illuminate\Support\Str;

class PostmanGenerator extends BaseGenerator
{
    public function generate(): array
    {
        $model   = $this->parser->getModelName();
        $plural  = Str::plural(Str::snake($model));
        $columns = $this->parser->getColumns();
        $rules   = $this->parser->getValidationRules();

        // Exemple de body pour POST/PUT
        $bodyFields = [];
        foreach ($columns as $col) {
            if ($col['is_foreign']) continue;
            $bodyFields[$col['name']] = $this->exampleValue($col);
        }
        $bodyJson = json_encode($bodyFields, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $collectionId = Str::uuid()->toString();
        $date         = date('Y-m-d');

        $collection = [
            'info' => [
                '_postman_id' => $collectionId,
                'name'        => "{$model} API",
                'description' => "Généré par salabanzi/laravel-scaffold le {$date}",
                'schema'      => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
            ],
            'variable' => [
                ['key' => 'base_url', 'value' => 'http://localhost:8000/api', 'type' => 'string'],
                ['key' => 'token',    'value' => 'your-token-here',           'type' => 'string'],
            ],
            'item' => [
                $this->buildRequest("Liste des {$plural}", 'GET', $plural, null),
                $this->buildRequest("Créer un {$model}", 'POST', $plural, $bodyJson),
                $this->buildRequest("Afficher un {$model}", 'GET', "{$plural}/1", null),
                $this->buildRequest("Modifier un {$model}", 'PUT', "{$plural}/1", $bodyJson),
                $this->buildRequest("Supprimer un {$model}", 'DELETE', "{$plural}/1", null),
            ],
        ];

        $content = json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return [$this->writeFile("postman/{$model}.collection.json", $content)];
    }

    private function buildRequest(string $name, string $method, string $path, ?string $body): array
    {
        $request = [
            'name'    => $name,
            'request' => [
                'method' => $method,
                'header' => [
                    ['key' => 'Authorization', 'value' => 'Bearer {{token}}', 'type' => 'text'],
                    ['key' => 'Accept',        'value' => 'application/json', 'type' => 'text'],
                    ['key' => 'Content-Type',  'value' => 'application/json', 'type' => 'text'],
                ],
                'url' => [
                    'raw'  => "{{base_url}}/{$path}",
                    'host' => ['{{base_url}}'],
                    'path' => explode('/', $path),
                ],
            ],
        ];

        if ($body) {
            $request['request']['body'] = [
                'mode' => 'raw',
                'raw'  => $body,
                'options' => ['raw' => ['language' => 'json']],
            ];
        }

        return $request;
    }

    private function exampleValue(array $col): mixed
    {
        $name = $col['name'];

        if (str_contains($name, 'email'))   return 'user@example.com';
        if (str_contains($name, 'name'))    return 'John Doe';
        if (str_contains($name, 'title'))   return 'Mon titre exemple';
        if (str_contains($name, 'slug'))    return 'mon-slug-exemple';
        if (str_contains($name, 'content')) return 'Contenu exemple...';
        if (str_contains($name, 'url'))     return 'https://example.com';
        if (str_contains($name, 'phone'))   return '+243000000000';

        return match($col['type']) {
            'integer', 'bigInteger',
            'unsignedBigInteger'     => 1,
            'float', 'double',
            'decimal'                => 10.5,
            'boolean'                => true,
            'date'                   => date('Y-m-d'),
            'dateTime', 'timestamp'  => date('Y-m-d H:i:s'),
            'json'                   => [],
            default                  => 'exemple',
        };
    }
}