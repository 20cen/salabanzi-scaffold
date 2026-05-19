<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Couches activées par défaut
    |--------------------------------------------------------------------------
    | 1 = Code de base (Model, Controller, Request, Routes, Factory, Seeder, Tests, OpenAPI)
    | 2 = Frontend (SDK TypeScript, Postman)
    | 3 = DevOps (Docker, GitHub Actions)
    | 4 = DX Laravel (Policy, Observer, Events)
    | 5 = Intelligence (Filament, Audit, Changelog)
    */
    'layers' => [1, 4, 5],

    /*
    |--------------------------------------------------------------------------
    | Namespace de base
    |--------------------------------------------------------------------------
    */
    'namespace' => 'App',

    /*
    |--------------------------------------------------------------------------
    | Stack frontend
    |--------------------------------------------------------------------------
    | Détecté automatiquement depuis composer.json et package.json
    | Valeurs possibles : vue, react, livewire, none
    */
    'frontend' => env('SCAFFOLD_FRONTEND', 'none'),

    /*
    |--------------------------------------------------------------------------
    | Framework de tests
    |--------------------------------------------------------------------------
    */
    'tests' => [
        'framework' => env('SCAFFOLD_TESTS', 'pest'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Filament
    |--------------------------------------------------------------------------
    */
    'filament' => [
        'auto_detect' => true,
    ],

];