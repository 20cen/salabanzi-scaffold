<?php

namespace Salabanzi\LaravelScaffold\Generators;

use Illuminate\Support\Str;

class TypeScriptSdkGenerator extends BaseGenerator
{
    public function generate(): array
    {
        $model      = $this->parser->getModelName();
        $plural     = Str::plural(Str::snake($model));
        $camel      = Str::camel($model);
        $columns    = $this->parser->getColumns();

        // Interface TypeScript
        $properties = '';
        foreach ($columns as $col) {
            $type        = $this->toTsType($col['type']);
            $nullable    = $col['nullable'] ? '?' : '';
            $properties .= "  {$col['name']}{$nullable}: {$type};\n";
        }

        $content = <<<TS
// Généré par salabanzi/laravel-scaffold
// Ne pas modifier manuellement

export interface {$model} {
  id: number;
{$properties}  created_at: string;
  updated_at: string;
}

export interface {$model}Payload {
{$properties}}

export interface PaginatedResponse<T> {
  data: T[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}

const BASE_URL = '/api';

export const {$camel}Service = {

  async list(params?: {
    page?: number;
    per_page?: number;
    search?: string;
    sort?: string;
    direction?: 'asc' | 'desc';
  }): Promise<PaginatedResponse<{$model}>> {
    const query = new URLSearchParams(params as Record<string, string>).toString();
    const res   = await fetch(`\${BASE_URL}/{$plural}?\${query}`);
    if (!res.ok) throw new Error('Erreur liste {$plural}');
    return res.json();
  },

  async get(id: number): Promise<{$model}> {
    const res = await fetch(`\${BASE_URL}/{$plural}/\${id}`);
    if (!res.ok) throw new Error('{$model} introuvable');
    return res.json();
  },

  async create(payload: {$model}Payload): Promise<{$model}> {
    const res = await fetch(`\${BASE_URL}/{$plural}`, {
      method:  'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body:    JSON.stringify(payload),
    });
    if (!res.ok) throw new Error('Erreur création {$model}');
    return res.json();
  },

  async update(id: number, payload: Partial<{$model}Payload>): Promise<{$model}> {
    const res = await fetch(`\${BASE_URL}/{$plural}/\${id}`, {
      method:  'PUT',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body:    JSON.stringify(payload),
    });
    if (!res.ok) throw new Error('Erreur mise à jour {$model}');
    return res.json();
  },

  async remove(id: number): Promise<void> {
    const res = await fetch(`\${BASE_URL}/{$plural}/\${id}`, {
      method: 'DELETE',
    });
    if (!res.ok) throw new Error('Erreur suppression {$model}');
  },

};
TS;

        return [$this->writeFile("resources/js/services/{$model}Service.ts", $content)];
    }

    private function toTsType(string $sqlType): string
    {
        return match($sqlType) {
            'integer', 'bigInteger',
            'unsignedBigInteger', 'unsignedInteger',
            'float', 'double', 'decimal'            => 'number',
            'boolean'                               => 'boolean',
            'json'                                  => 'Record<string, unknown>',
            'date', 'dateTime', 'timestamp'         => 'string',
            default                                 => 'string',
        };
    }
}