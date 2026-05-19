<?php

namespace Salabanzi\LaravelScaffold\Generators;

use Illuminate\Support\Str;

class OpenApiGenerator extends BaseGenerator
{
    public function generate(): array
    {
        $model      = $this->parser->getModelName();
        $plural     = Str::plural(Str::snake($model));
        $columns    = $this->parser->getColumns();

        $properties = '';
        foreach ($columns as $col) {
            $type        = $this->toOpenApiType($col['type']);
            $properties .= "          {$col['name']}:\n";
            $properties .= "            type: {$type}\n";
            if ($col['nullable']) {
                $properties .= "            nullable: true\n";
            }
        }

        $date    = now()->format('Y-m-d');
        $content = <<<YAML
openapi: "3.0.3"
info:
  title: "{$model} API"
  version: "1.0.0"
  description: "Généré par salabanzi/laravel-scaffold le {$date}"
tags:
  - name: {$model}
paths:
  /{$plural}:
    get:
      tags: [{$model}]
      summary: "Liste des {$plural}"
      parameters:
        - { name: page, in: query, schema: { type: integer } }
        - { name: per_page, in: query, schema: { type: integer, default: 15 } }
        - { name: search, in: query, schema: { type: string } }
        - { name: sort, in: query, schema: { type: string } }
        - { name: direction, in: query, schema: { type: string, enum: [asc, desc] } }
      responses:
        "200": { description: OK }
        "401": { description: Unauthenticated }
    post:
      tags: [{$model}]
      summary: "Créer un {$model}"
      requestBody:
        required: true
        content:
          application/json:
            schema:
              \$ref: "#/components/schemas/{$model}Input"
      responses:
        "201": { description: Created }
        "401": { description: Unauthenticated }
        "422": { description: Validation error }
  /{$plural}/{id}:
    get:
      tags: [{$model}]
      summary: "Afficher un {$model}"
      parameters:
        - { name: id, in: path, required: true, schema: { type: integer } }
      responses:
        "200": { description: OK }
        "401": { description: Unauthenticated }
        "404": { description: Not found }
    put:
      tags: [{$model}]
      summary: "Modifier un {$model}"
      parameters:
        - { name: id, in: path, required: true, schema: { type: integer } }
      requestBody:
        required: true
        content:
          application/json:
            schema:
              \$ref: "#/components/schemas/{$model}Input"
      responses:
        "200": { description: OK }
        "401": { description: Unauthenticated }
        "422": { description: Validation error }
    delete:
      tags: [{$model}]
      summary: "Supprimer un {$model}"
      parameters:
        - { name: id, in: path, required: true, schema: { type: integer } }
      responses:
        "204": { description: No Content }
        "401": { description: Unauthenticated }
        "404": { description: Not found }
components:
  schemas:
    {$model}Input:
      type: object
      properties:
{$properties}
YAML;

        return [$this->writeFile("storage/api-docs/{$plural}.yaml", $content)];
    }

    private function toOpenApiType(string $sqlType): string
    {
        return match($sqlType) {
            'integer', 'bigInteger',
            'unsignedBigInteger', 'unsignedInteger' => 'integer',
            'float', 'double', 'decimal'            => 'number',
            'boolean'                               => 'boolean',
            'json'                                  => 'object',
            default                                 => 'string',
        };
    }
}