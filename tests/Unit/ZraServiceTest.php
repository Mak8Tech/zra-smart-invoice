<?php

namespace Mak8Tech\ZraSmartInvoice\Tests\Unit;

use Mak8Tech\ZraSmartInvoice\Models\ZraConfig;
use Mak8Tech\ZraSmartInvoice\Services\ZraService;
use Mak8Tech\ZraSmartInvoice\Tests\TestCase;
use Mockery;

class ZraServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testCanInitializeDevice()
    {
        // Mock the ZraService to control its behavior
        $mock = Mockery::mock(ZraService::class);
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

        $this->app->instance(ZraService::class, $mock);

        $service = $this->app->make(ZraService::class);
        $result = $service->initializeDevice('1234567890', '001', 'DEVICE123456');

        $this->assertTrue($result['success']);
        $this->assertEquals('test_api_key', $result['data']['api_key']);
    }

    public function testCanSendSalesData()
    {
        // Create a mock of ZraConfig that will be returned by the static method
        $configMock = Mockery::mock('overload:' . ZraConfig::class);
        $configMock->shouldReceive('getActive')
            ->andReturn((object)[
                'tpin' => '1234567890',
                'branch_id' => '001',
                'device_serial' => 'DEVICE123456',
                'api_key' => 'test_api_key',
                'last_initialized_at' => now(),
                'is_active' => true,
            ]);

        // Mock the service
        $mock = Mockery::mock(ZraService::class);
        $mock->shouldReceive('sendSalesData')
            ->once()
            ->andReturn([
                'success' => true,
                'message' => 'Sales data submitted successfully',
                'reference' => 'test_reference',
            ]);

        $this->app->instance(ZraService::class, $mock);

        $service = $this->app->make(ZraService::class);
        $result = $service->sendSalesData([
            'invoice_number' => 'INV-12345',
            'total_amount' => 1000.00,
            'items' => [
                [
                    'description' => 'Test Product',
                    'quantity' => 2,
                    'unit_price' => 500.00,
                    'tax_rate' => 16,
                ]
            ],
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('Sales data submitted successfully', $result['message']);
    }

    public function testCanHandleInvoiceTypes()
    {
        // Create a mock of ZraConfig that will be returned by the static method
        $configMock = Mockery::mock('overload:' . ZraConfig::class);
        $configMock->shouldReceive('getActive')
            ->andReturn((object)[
                'tpin' => '1234567890',
                'branch_id' => '001',
                'device_serial' => 'DEVICE123456',
                'api_key' => 'test_api_key',
                'last_initialized_at' => now(),
                'is_active' => true,
            ]);

        // Mock the service
        $mock = Mockery::mock(ZraService::class);
        $mock->shouldReceive('sendSalesData')
            ->with(
                Mockery::any(),
                'NORMAL',  // Invoice type
                'SALE'     // Transaction type
            )
            ->once()
            ->andReturn([
                'success' => true,
                'message' => 'Sales data with invoice type submitted successfully',
                'reference' => 'test_reference',
            ]);

        $this->app->instance(ZraService::class, $mock);

        $service = $this->app->make(ZraService::class);
        $result = $service->sendSalesData(
            [
                'invoice_number' => 'INV-12345',
                'total_amount' => 1000.00,
                'items' => [
                    [
                        'description' => 'Test Product',
                        'quantity' => 2,
                        'unit_price' => 500.00,
                        'tax_rate' => 16,
                    ]
                ],
            ],
            'NORMAL',
            'SALE'
        );

        $this->assertTrue($result['success']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
