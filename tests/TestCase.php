<?php

namespace Mak8Tech\ZraSmartInvoice\Tests;

use Mak8Tech\ZraSmartInvoice\ZraServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app)
    {
        return [
            ZraServiceProvider::class,
        ];
    }

    /**
     * Define database migrations.
     *
     * @return void
     */
    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function defineEnvironment($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        // Set ZRA config
        $app['config']->set('zra.environment', 'sandbox');
        $app['config']->set('zra.endpoints.sandbox', [
            'base_url' => 'https://sandbox-api.example.com',
            'initialize' => '/api/v1/initialize',
            'sales' => '/api/v1/sales',
        ]);
    }
}
