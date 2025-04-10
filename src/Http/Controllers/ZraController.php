<?php

namespace Mak8Tech\ZraSmartInvoice\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Mak8Tech\ZraSmartInvoice\Http\Requests\ZraConfigRequest;
use Mak8Tech\ZraSmartInvoice\Models\ZraConfig;
use Mak8Tech\ZraSmartInvoice\Services\ZraService;

class ZraController extends Controller
{
    /**
     * @var ZraService
     */
    protected $zraService;

    /**
     * Constructor
     */
    public function __construct(ZraService $zraService)
    {
        $this->zraService = $zraService;
    }

    /**
     * Display the ZRA configuration page
     *
     * @return Response
     */
    public function index(): Response
    {
        $config = ZraConfig::getActive();
        $stats = $this->zraService->getStatistics();

        return Inertia::render('ZraConfig/Index', [
            'config' => $config ? [
                'id' => $config->id,
                'tpin' => $config->tpin,
                'branch_id' => $config->branch_id,
                'device_serial' => $config->device_serial,
                'environment' => $config->environment,
                'status' => $config->getStatus(),
                'last_initialized_at' => $config->last_initialized_at?->format('Y-m-d H:i:s'),
                'last_sync_at' => $config->last_sync_at?->format('Y-m-d H:i:s'),
            ] : null,
            'logs' => $this->zraService->getRecentLogs()->map(function ($log) {
                return [
                    'id' => $log->id,
                    'transaction_type' => $log->transaction_type,
                    'reference' => $log->reference,
                    'status' => $log->status,
                    'error_message' => $log->error_message,
                    'created_at' => $log->created_at->format('Y-m-d H:i:s'),
                ];
            }),
            'is_initialized' => $this->zraService->isInitialized(),
            'environment' => config('zra.base_url') === 'https://api-sandbox.zra.org.zm/vsdc-api/v1' ? 'sandbox' : 'production',
            'stats' => $stats,
        ]);
    }

    /**
     * Initialize the ZRA device
     *
     * @param ZraConfigRequest $request
     * @return array
     */
    public function initialize(ZraConfigRequest $request): array
    {
        try {
            $result = $this->zraService->initializeDevice(
                $request->input('tpin'),
                $request->input('branch_id'),
                $request->input('device_serial')
            );

            if ($result['success']) {
                return [
                    'success' => true,
                    'message' => 'Device successfully initialized',
                    'result' => $result['data'],
                ];
            }

            return [
                'success' => false,
                'message' => $result['error'] ?? 'Initialization failed',
            ];
        } catch (Exception $e) {
            Log::error('ZRA device initialization failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get the current ZRA configuration status
     *
     * @return array
     */
    public function status(): array
    {
        $config = ZraConfig::getActive();

        return [
            'initialized' => $this->zraService->isInitialized(),
            'config' => $config ? [
                'status' => $config->getStatus(),
                'environment' => $config->environment,
                'last_initialized_at' => $config->last_initialized_at?->format('Y-m-d H:i:s'),
                'last_sync_at' => $config->last_sync_at?->format('Y-m-d H:i:s'),
            ] : null,
        ];
    }

    /**
     * Get recent transaction logs
     *
     * @param Request $request
     * @return array
     */
    public function logs(Request $request): array
    {
        $limit = $request->input('limit', 10);

        return [
            'logs' => $this->zraService->getRecentLogs($limit)->map(function ($log) {
                return [
                    'id' => $log->id,
                    'transaction_type' => $log->transaction_type,
                    'reference' => $log->reference,
                    'status' => $log->status,
                    'error_message' => $log->error_message,
                    'created_at' => $log->created_at->format('Y-m-d H:i:s'),
                ];
            }),
        ];
    }

    /**
     * Test a sales data submission
     *
     * @param Request $request
     * @return array
     */
    public function testSales(Request $request): array
    {
        try {
            // Ensure device is initialized
            if (!$this->zraService->isInitialized()) {
                return [
                    'success' => false,
                    'message' => 'Device not initialized. Please initialize the device first.',
                ];
            }

            // Get invoice and transaction types from request or use defaults
            $invoiceType = $request->input('invoice_type', config('zra.default_invoice_type'));
            $transactionType = $request->input('transaction_type', config('zra.default_transaction_type'));
            $taxCategory = $request->input('tax_category', config('zra.default_tax_category'));

            // Sample sales data
            $salesData = [
                'invoiceNumber' => 'INV-' . mt_rand(1000, 9999),
                'timestamp' => now()->format('Y-m-d H:i:s'),
                'items' => [
                    [
                        'name' => 'Standard VAT Product',
                        'quantity' => 2,
                        'unitPrice' => 100.00,
                        'taxCategory' => 'VAT',
                    ],
                    [
                        'name' => 'Zero-Rated Product',
                        'quantity' => 1,
                        'unitPrice' => 50.00,
                        'taxCategory' => 'ZERO_RATED',
                    ],
                    [
                        'name' => 'Tourism Service',
                        'quantity' => 1,
                        'unitPrice' => 200.00,
                        'taxCategory' => 'TOURISM_LEVY',
                    ],
                ],
                'paymentType' => 'CASH',
                'customerTpin' => '',  // Optional for customer without TPIN
            ];

            $result = $this->zraService->sendSalesData($salesData, $invoiceType, $transactionType);

            return [
                'success' => $result['success'],
                'message' => $result['success']
                    ? 'Test sales data sent successfully with invoice type: ' . $invoiceType . ' and transaction type: ' . $transactionType
                    : ($result['error'] ?? 'Failed to send test sales data'),
                'reference' => $result['reference'] ?? null,
                'data' => $result['data'] ?? null,
                'invoice_type' => $invoiceType,
                'transaction_type' => $transactionType,
                'tax_details' => $result['data']['taxSummary'] ?? [],
            ];
        } catch (Exception $e) {
            Log::error('ZRA test sales submission failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get statistics
     *
     * @return array
     */
    public function statistics(): array
    {
        return [
            'stats' => $this->zraService->getStatistics(),
            'initialized' => $this->zraService->isInitialized(),
        ];
    }

    /**
     * Check the health of the ZRA API connection
     *
     * @return array
     */
    public function checkHealth(): array
    {
        return $this->zraService->healthCheck();
    }

    /**
     * Process a transaction in the queue
     *
     * @param Request $request
     * @return array
     */
    public function queueTransaction(Request $request): array
    {
        try {
            $type = $request->input('type');
            $data = $request->input('data', []);

            // Validate transaction type
            if (!in_array($type, ['sales', 'purchase', 'stock'])) {
                return [
                    'success' => false,
                    'message' => 'Invalid transaction type. Must be one of: sales, purchase, stock',
                ];
            }

            // Ensure device is initialized
            if (!$this->zraService->isInitialized()) {
                return [
                    'success' => false,
                    'message' => 'Device not initialized. Please initialize the device first.',
                ];
            }

            // Process the transaction in the queue based on type
            switch ($type) {
                case 'sales':
                    $result = $this->zraService->sendSalesData($data, true);
                    break;

                case 'purchase':
                    $result = $this->zraService->sendPurchaseData($data, true);
                    break;

                case 'stock':
                    $result = $this->zraService->sendStockData($data, true);
                    break;
            }

            return [
                'success' => true,
                'message' => ucfirst($type) . ' data has been queued for processing',
                'reference' => $result['reference'] ?? null,
            ];
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to queue ZRA transaction', [
                'error' => $e->getMessage(),
                'type' => $request->input('type'),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get tax categories
     *
     * @return array
     */
    public function taxCategories(): array
    {
        return [
            'tax_categories' => $this->zraService->getTaxCategories(),
            'exemption_categories' => $this->zraService->getExemptionCategories(),
        ];
    }

    /**
     * Calculate tax for items
     *
     * @param Request $request
     * @return array
     */
    public function calculateTax(Request $request): array
    {
        try {
            $items = $request->input('items', []);

            if (empty($items)) {
                return [
                    'success' => false,
                    'message' => 'No items provided for tax calculation',
                ];
            }

            $taxResult = $this->zraService->calculateTax($items);

            return [
                'success' => true,
                'data' => $taxResult,
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
