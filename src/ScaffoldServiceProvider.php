<?php

namespace Salabanzi\LaravelScaffold;

use Illuminate\Support\ServiceProvider;
use Salabanzi\LaravelScaffold\Commands\ScaffoldGenerate;

class ScaffoldServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {

            $this->commands([
                ScaffoldGenerate::class,
            ]);

            // Publication des stubs
            $this->publishes([
                __DIR__ . '/../stubs' => base_path('stubs/scaffold'),
            ], 'scaffold-stubs');

            // Publication de la config
            $this->publishes([
                __DIR__ . '/../config/scaffold.php' => config_path('scaffold.php'),
            ], 'scaffold-config');
        }

        $this->mergeConfigFrom(
            __DIR__ . '/../config/scaffold.php',
            'scaffold'
        );
    }
}