<?php

namespace Mak8Tech\ZraSmartInvoice\Tests\Feature;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mak8Tech\ZraSmartInvoice\Models\ZraConfig;
use Mak8Tech\ZraSmartInvoice\Models\ZraTransactionLog;
use Mak8Tech\ZraSmartInvoice\Services\ZraService;
use Mak8Tech\ZraSmartInvoice\Tests\TestCase;

class ZraServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ZraService $zraService;
    protected MockHandler $mockHandler;

    public function setUp(): void
    {
        parent::setUp();
        
        // Create a mock handler
        $this->mockHandler = new MockHandler();
        $handlerStack = HandlerStack::create($this->mockHandler);
        $client = new Client(['handler' => $handlerStack]);
        
        // Create our ZraService with the mocked client
        $this->zraService = new ZraService();
        
        // Set the mocked client using reflection
        $reflection = new \ReflectionClass($this->zraService);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($this->zraService, $client);
        
        // Create a test ZRA configuration
        ZraConfig::create([
            'tpin' => '1234567890',
            'branch_id' => '001',
            'device_serial' => 'TEST123456',
            'environment' => 'sandbox',
            'device_id' => 'DEVICE12345678901234',
            'last_initialized_at' => now(),
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_can_initialize_a_device()
    {
        // Mock the API response
        $this->mockHandler->append(
            new Response(200, [], json_encode([
                'success' => true,
                'device_id' => 'DEVICE12345678901234',
                'message' => 'Device initialized successfully',
            ]))
        );
        
        // Call the service method
        $result = $this->zraService->initializeDevice('1234567890', '001', 'TEST123456');
        
        // Assert the result
        $this->assertTrue($result['success']);
        $this->assertEquals('DEVICE12345678901234', $result['device_id']);
        
        // Assert the log was created
        $this->assertDatabaseHas('zra_transaction_logs', [
            'transaction_type' => 'initialize_device',
            'status' => 'success',
        ]);
    }
    
    /** @test */
    public function it_can_send_sales_data()
    {
        // Mock the API response
        $this->mockHandler->append(
            new Response(200, [], json_encode([
                'success' => true,
                'reference' => 'INV-12345',
                'message' => 'Sales data received successfully',
            ]))
        );
        
        // Sample sales data
        $salesData = [
            'invoiceNumber' => 'INV-12345',
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'items' => [
                [
                    'name' => 'Product 1',
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
        ];
        
        // Call the service method
        $result = $this->zraService->sendSalesData($salesData);
        
        // Assert the result
        $this->assertTrue($result['success']);
        $this->assertEquals('INV-12345', $result['reference']);
        
        // Assert the log was created
        $this->assertDatabaseHas('zra_transaction_logs', [
            'transaction_type' => 'sales_data',
            'reference' => 'INV-12345',
            'status' => 'success',
        ]);
    }
    
    /** @test */
    public function it_can_queue_a_transaction()
    {
        // Sample sales data
        $salesData = [
            'invoiceNumber' => 'INV-67890',
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'items' => [
                [
                    'name' => 'Product 1',
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
        ];
        
        // Mock the job dispatch
        \Queue::fake();
        
        // Call the service method with queue=true
        $result = $this->zraService->sendSalesData($salesData, true);
        
        // Assert the result
        $this->assertTrue($result['success']);
        $this->assertStringContains('queued', $result['message']);
        
        // Assert the job was dispatched
        \Queue::assertPushed(\Mak8Tech\ZraSmartInvoice\Jobs\ProcessZraTransaction::class);
    }
    
    /** @test */
    public function it_handles_api_errors_correctly()
    {
        // Mock the API response for an error
        $this->mockHandler->append(
            new Response(400, [], json_encode([
                'success' => false,
                'message' => 'Invalid input data',
                'errors' => ['invoiceNumber' => 'The invoice number is required'],
            ]))
        );
        
        // Sample invalid sales data
        $salesData = [
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'items' => [],
            'totalAmount' => 0,
            'totalTax' => 0,
            'paymentType' => 'CASH',
        ];
        
        // Expect an exception
        $this->expectException(\Exception::class);
        
        try {
            // Call the service method
            $this->zraService->sendSalesData($salesData);
        } catch (\Exception $e) {
            // Assert the log was created with failed status
            $this->assertDatabaseHas('zra_transaction_logs', [
                'transaction_type' => 'sales_data',
                'status' => 'failed',
            ]);
            
            throw $e;
        }
    }
    
    /** @test */
    public function it_can_get_statistics()
    {
        // Create some test logs
        ZraTransactionLog::create([
            'transaction_type' => 'sales_data',
            'reference' => 'INV-1111',
            'status' => 'success',
            'request_data' => json_encode(['test' => 'data']),
            'response_data' => json_encode(['success' => true]),
        ]);
        
        ZraTransactionLog::create([
            'transaction_type' => 'purchase_data',
            'reference' => 'PO-2222',
            'status' => 'failed',
            'request_data' => json_encode(['test' => 'data']),
            'response_data' => json_encode(['success' => false]),
            'error_message' => 'Server error',
        ]);
        
        ZraTransactionLog::create([
            'transaction_type' => 'stock_data',
            'reference' => 'STK-3333',
            'status' => 'success',
            'request_data' => json_encode(['test' => 'data']),
            'response_data' => json_encode(['success' => true]),
        ]);
        
        // Get statistics
        $stats = $this->zraService->getStatistics();
        
        // Assert the statistics are correct
        $this->assertEquals(3, $stats['total_transactions']);
        $this->assertEquals(2, $stats['successful_transactions']);
        $this->assertEquals(1, $stats['failed_transactions']);
        $this->assertEquals(66.7, $stats['success_rate']);
    }
}
