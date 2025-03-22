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
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     */
    protected function defineEnvironment($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Set test environment for ZRA
        $app['config']->set('zra.base_url', 'https://api-sandbox.zra.org.zm/vsdc-api/v1');
        $app['config']->set('zra.retry.attempts', 1);
        $app['config']->set('zra.retry.delay', 1);
        $app['config']->set('zra.debug', true);
    }

    /**
     * Define database migrations.
     */
    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
