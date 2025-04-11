<?php

namespace Mak8Tech\ZraSmartInvoice\Tests;

use Mak8Tech\ZraSmartInvoice\ZraServiceProvider;
use Mak8Tech\ZraSmartInvoice\Models\ZraConfig;
use Orchestra\Testbench\TestCase as Orchestra;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class TestCase extends Orchestra
{
    // Using DatabaseTransactions instead of RefreshDatabase to prevent migration issues
    use DatabaseTransactions;

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create database tables directly for testing, no longer relying on migrations
        $this->createTestTables();

        // Create a default ZraConfig entry to prevent "no such table" errors
        ZraConfig::create([
            'tpin' => '1234567890',
            'branch_id' => '001',
            'device_serial' => 'TEST123456',
            'environment' => 'sandbox',
            'api_key' => null,
            'last_initialized_at' => null,
            'is_active' => false,
            'additional_config' => json_encode([
                'device_id' => null
            ])
        ]);
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
            $table->string('sku', 64)->unique()->comment('Stock Keeping Unit - unique identifier for the product');
            $table->string('name')->comment('Product name');
            $table->text('description')->nullable()->comment('Product description');
            $table->string('category')->nullable()->comment('Product category');
            $table->decimal('unit_price', 10, 2)->default(0)->comment('Base unit price without tax');
            $table->string('tax_category')->default('VAT')->comment('Tax category (VAT, ZERO_RATED, EXEMPT, etc.)');
            $table->decimal('tax_rate', 5, 2)->default(16.00)->comment('Current tax rate percentage');
            $table->string('unit_of_measure', 20)->default('EACH')->comment('Unit of measure (EACH, KG, LITER, etc.)');
            $table->integer('current_stock')->default(0)->comment('Current stock quantity');
            $table->integer('reorder_level')->default(10)->comment('Stock level that triggers reordering');
            $table->boolean('track_inventory')->default(true)->comment('Whether to track inventory for this item');
            $table->boolean('active')->default(true)->comment('Whether the product is active and can be sold');
            $table->timestamps();
            $table->softDeletes();

            // Add indexes for common queries
            $table->index('sku');
            $table->index('name');
            $table->index('category');
            $table->index('tax_category');
            $table->index('active');
        });

        Schema::create('zra_inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')->constrained('zra_inventory')->onDelete('cascade');
            $table->string('reference')->comment('Reference number (invoice, purchase order, etc.)');
            $table->string('movement_type')->comment('Type of movement (SALE, PURCHASE, ADJUSTMENT, RETURN, etc.)');
            $table->integer('quantity')->comment('Quantity moved (positive for in, negative for out)');
            $table->decimal('unit_price', 10, 2)->comment('Unit price at the time of movement');
            $table->json('metadata')->nullable()->comment('Additional information about the movement');
            $table->string('notes')->nullable()->comment('Notes about this movement');
            $table->timestamps();

            // Add indexes for common queries
            $table->index('reference');
            $table->index('movement_type');
            $table->index('created_at');
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
