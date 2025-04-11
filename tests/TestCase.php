<?php

namespace Mak8Tech\ZraSmartInvoice\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Mak8Tech\ZraSmartInvoice\ZraServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    use RefreshDatabase;

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create required tables for testing
        $this->artisan('migrate', ['--database' => 'testing'])->run();
    }

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
        $app['config']->set('zra.base_url', 'https://sandbox-api.example.com');
        $app['config']->set('zra.timeout', 10);
        $app['config']->set('zra.debug', true);
        $app['config']->set('zra.log_requests', true);

        // Set invoice and tax configuration
        $app['config']->set('zra.invoice_types', [
            'NORMAL' => 'Normal Invoice',
            'COPY' => 'Copy of Invoice',
            'TRAINING' => 'Training Invoice',
            'PROFORMA' => 'Proforma Invoice',
        ]);

        $app['config']->set('zra.tax_categories', [
            'VAT' => [
                'name' => 'Value Added Tax',
                'code' => 'VAT',
                'default_rate' => 16.0,
                'applies_to' => 'goods_and_services',
            ],
            'ZERO_RATED' => [
                'name' => 'Zero Rated',
                'code' => 'ZR',
                'default_rate' => 0.0,
                'applies_to' => 'zero_rated_goods',
            ],
        ]);
    }
}
