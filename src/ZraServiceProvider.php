<?php

namespace Mak8Tech\ZraSmartInvoice;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;
use Mak8Tech\ZraSmartInvoice\Console\Commands\ZraHealthCheckCommand;
use Mak8Tech\ZraSmartInvoice\Console\Commands\ZraReportCommand;
use Mak8Tech\ZraSmartInvoice\Http\Middleware\ZraRoleMiddleware;
use Mak8Tech\ZraSmartInvoice\Http\Middleware\ZraRateLimitMiddleware;
use Mak8Tech\ZraSmartInvoice\Http\Middleware\ZraSecurityMiddleware;
use Mak8Tech\ZraSmartInvoice\Services\ZraReportService;
use Mak8Tech\ZraSmartInvoice\Services\ZraService;
use Mak8Tech\ZraSmartInvoice\Services\ZraTaxService;

class ZraServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        // Load package routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        // Load migrations - use absolute paths to ensure they're found
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Publish migrations to allow customization
        $this->publishes([
            __DIR__ . '/../database/migrations/' => database_path('migrations'),
        ], 'zra-migrations');

        // Publish configuration
        $this->publishes([
            __DIR__ . '/../config/zra.php' => config_path('zra.php'),
        ], 'config');

        // Publish Inertia components
        $this->publishes([
            __DIR__ . '/../resources/js' => resource_path('js/vendor/mak8tech/zra-smart-invoice'),
        ], 'inertia-components');

        // Register middleware
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('zra.role', ZraRoleMiddleware::class);
        $router->aliasMiddleware('zra.ratelimit', ZraRateLimitMiddleware::class);
        $router->aliasMiddleware('zra.security', ZraSecurityMiddleware::class);

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                ZraHealthCheckCommand::class,
                ZraReportCommand::class,
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

        // Register the tax service
        $this->app->singleton(ZraTaxService::class, function ($app) {
            return new ZraTaxService();
        });

        // Register the report service
        $this->app->singleton(ZraReportService::class, function ($app) {
            return new ZraReportService(
                $app->make(ZraService::class),
                $app->make(ZraTaxService::class)
            );
        });
    }
}
