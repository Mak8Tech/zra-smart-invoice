<?php

namespace Mak8Tech\ZraSmartInvoice;

use Illuminate\Support\ServiceProvider;
use Mak8Tech\ZraSmartInvoice\Console\Commands\ZraHealthCheckCommand;
use Mak8Tech\ZraSmartInvoice\Services\ZraService;

class ZraServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        // Load package routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Publish configuration
        $this->publishes([
            __DIR__ . '/../config/zra.php' => config_path('zra.php'),
        ], 'config');

        // Publish Inertia components
        $this->publishes([
            __DIR__ . '/../resources/js' => resource_path('js/vendor/mak8tech/zra-smart-invoice'),
        ], 'inertia-components');
        
        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                ZraHealthCheckCommand::class,
            ]);
        }
    }

    /**
     * Register any package services.
     */
    public function register(): void
    {
        // Merge configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../config/zra.php',
            'zra'
        );

        // Register the service
        $this->app->singleton('zra', function ($app) {
            return new ZraService();
        });
    }
}
