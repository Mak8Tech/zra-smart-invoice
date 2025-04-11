<?php

namespace Mak8Tech\ZraSmartInvoice\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Mak8Tech\ZraSmartInvoice\ZraServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class TestCase extends Orchestra
{
    use RefreshDatabase;

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create database tables directly for testing
        $this->createTestTables();
    }

    /**
     * Create the database tables directly for testing
     */
    protected function createTestTables(): void
    {
        // Drop tables first to ensure clean state
        Schema::dropIfExists('zra_inventory_movements');
        Schema::dropIfExists('zra_inventory');
        Schema::dropIfExists('zra_transaction_logs');
        Schema::dropIfExists('zra_configs');

        // Create ZRA configs table
        Schema::create('zra_configs', function (Blueprint $table) {
            $table->id();
            $table->string('tpin', 10)->comment('Taxpayer Identification Number');
            $table->string('branch_id', 3)->comment('Branch ID');
            $table->string('device_serial', 100)->comment('Device Serial Number');
            $table->string('api_key')->nullable()->comment('API Key from ZRA');
            $table->string('environment')->default('sandbox')->comment('API environment: sandbox or production');
            $table->boolean('is_active')->default(false)->comment('Whether this config is active');
            $table->timestamp('last_initialized_at')->nullable()->comment('Last successful initialization timestamp');
            $table->timestamp('last_sync_at')->nullable()->comment('Last successful data sync timestamp');
            $table->json('additional_config')->nullable()->comment('Additional configuration as JSON');
            $table->timestamps();

            // Add indexes 
            $table->index('tpin');
            $table->index('branch_id');
            $table->index('device_serial');
            $table->index('environment');
            $table->index('is_active');
        });

        // Create ZRA transaction logs table
        Schema::create('zra_transaction_logs', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_type')->comment('Type: initialization, sales, purchase, stock, etc.');
            $table->string('reference')->nullable()->comment('Reference number or ID');
            $table->json('request_payload')->nullable()->comment('API request data');
            $table->json('response_payload')->nullable()->comment('API response data');
            $table->string('status')->comment('Status: success, failed');
            $table->string('error_message')->nullable()->comment('Error message if any');
            $table->timestamps();

            // Add indexes
            $table->index('transaction_type');
            $table->index('reference');
            $table->index('status');
            $table->index('created_at');
        });

        // Create ZRA inventory table
        Schema::create('zra_inventory', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->comment('Product code');
            $table->string('name')->comment('Product name');
            $table->string('description')->nullable()->comment('Product description');
            $table->decimal('unit_price', 15, 2)->comment('Unit price');
            $table->decimal('tax_rate', 8, 2)->default(0)->comment('Tax rate percentage');
            $table->string('tax_category')->default('VAT')->comment('Tax category');
            $table->integer('stock_quantity')->default(0)->comment('Current stock quantity');
            $table->timestamps();

            $table->index('code');
            $table->index('tax_category');
        });

        // Create ZRA inventory movements table
        Schema::create('zra_inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')->comment('Foreign key to zra_inventory table');
            $table->string('movement_type')->comment('Type: PURCHASE, SALE, ADJUSTMENT, etc.');
            $table->integer('quantity')->comment('Quantity changed (positive for in, negative for out)');
            $table->string('reference')->nullable()->comment('Reference document');
            $table->text('notes')->nullable()->comment('Additional notes');
            $table->timestamps();

            $table->index('movement_type');
            $table->index('reference');
        });
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
