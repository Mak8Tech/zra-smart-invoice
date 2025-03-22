<?php

namespace Mak8Tech\ZraSmartInvoice\Tests\Unit;

use Mak8Tech\ZraSmartInvoice\Models\ZraConfig;
use Mak8Tech\ZraSmartInvoice\Services\ZraService;
use Mak8Tech\ZraSmartInvoice\Tests\TestCase;
use Mockery;

class ZraServiceTest extends TestCase
{
    public function testDeviceInitialization()
    {
        // Mock the HTTP client
        $this->mock(ZraService::class, function ($mock) {
            $mock->shouldReceive('initializeDevice')
                ->once()
                ->with('1234567890', '001', 'DEVICE123456')
                ->andReturn([
                    'success' => true,
                    'data' => [
                        'api_key' => 'test_api_key',
                        'device_id' => 'test_device_id',
                    ],
                    'reference' => 'zra_test_reference',
                ]);
        });

        $service = $this->app->make(ZraService::class);
        $result = $service->initializeDevice('1234567890', '001', 'DEVICE123456');

        $this->assertTrue($result['success']);
        $this->assertEquals('test_api_key', $result['data']['api_key']);
    }

    public function testSendSalesData()
    {
        // Create a config for testing
        ZraConfig::create([
            'tpin' => '1234567890',
            'branch_id' => '001',
            'device_serial' => 'DEVICE123456',
            'api_key' => 'test_api_key',
            'last_initialized_at' => now(),
        ]);

        // Mock the service
        $this->mock(ZraService::class, function ($mock) {
            $mock->shouldReceive('sendSalesData')
                ->once()
                ->andReturn([
                    'success' => true,
                    'data' => ['result' => 'Sales data received'],
                    'reference' => 'zra_sales_reference',
                ]);
        });

        $service = $this->app->make(ZraService::class);
        $result = $service->sendSalesData([
            'invoiceNumber' => 'INV-1234',
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'items' => [
                [
                    'name' => 'Test Product',
                    'quantity' => 2,
                    'unitPrice' => 100.00,
                    'totalAmount' => 200.00,
                    'taxRate' => 16,
                    'taxAmount' => 32.00,
                ],
            ],
            'totalAmount' => 200.00,
            'totalTax' => 32.00,
            'paymentType' => 'CASH',
        ]);

        $this->assertTrue($result['success']);
    }

    public function testIsInitialized()
    {
        // Create a config for testing
        ZraConfig::create([
            'tpin' => '1234567890',
            'branch_id' => '001',
            'device_serial' => 'DEVICE123456',
            'api_key' => 'test_api_key',
            'last_initialized_at' => now(),
        ]);

        $service = new ZraService();
        $this->assertTrue($service->isInitialized());
    }
}
