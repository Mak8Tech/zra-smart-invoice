<?php

namespace Mak8Tech\ZraSmartInvoice\Services;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Mak8Tech\ZraSmartInvoice\Models\ZraConfig;
use Mak8Tech\ZraSmartInvoice\Models\ZraTransactionLog;

class ZraService
{
    /**
     * @var Client
     */
    protected $httpClient;

    /**
     * @var ZraConfig|null
     */
    protected $config;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->config = ZraConfig::getActive();

        $this->httpClient = new Client([
            'base_uri' => config('zra.base_url'),
            'timeout' => config('zra.timeout'),
            'http_errors' => false,
        ]);
    }

    /**
     * Initialize the ZRA device
     *
     * @param string $tpin
     * @param string $branchId
     * @param string $deviceSerial
     * @return array
     * @throws Exception
     */
    public function initializeDevice(string $tpin, string $branchId, string $deviceSerial): array
    {
        $this->validateInitParams($tpin, $branchId, $deviceSerial);

        $endpoint = '/initializer/selectInitInfo';
        $requestData = [
            'tpin' => $tpin,
            'branchId' => $branchId,
            'deviceSerialNumber' => $deviceSerial,
        ];

        try {
            $response = $this->makeApiRequest('POST', $endpoint, $requestData);

            if ($response['success']) {
                // Store the configuration
                $this->storeConfig($tpin, $branchId, $deviceSerial, $response['data']);
            }

            return $response;
        } catch (Exception $e) {
            $this->logError('device_initialization', $e->getMessage(), $requestData);
            throw $e;
        }
    }

    /**
     * Send sales data to ZRA
     *
     * @param array $salesData
     * @param string|null $invoiceType Type of invoice (NORMAL, COPY, TRAINING, PROFORMA)
     * @param string|null $transactionType Type of transaction (SALE, CREDIT_NOTE, DEBIT_NOTE, etc)
     * @param bool $queue Whether to process the request in a queue
     * @return array
     * @throws Exception
     */
    public function sendSalesData(array $salesData, ?string $invoiceType = null, ?string $transactionType = null, bool $queue = false): array
    {
        $this->ensureInitialized();

        // Set invoice type and transaction type if not provided
        $invoiceType = $invoiceType ?? config('zra.default_invoice_type');
        $transactionType = $transactionType ?? config('zra.default_transaction_type');

        // Validate invoice type
        if (!array_key_exists($invoiceType, config('zra.invoice_types'))) {
            throw new Exception("Invalid invoice type: {$invoiceType}");
        }

        // Validate transaction type
        if (!array_key_exists($transactionType, config('zra.transaction_types'))) {
            throw new Exception("Invalid transaction type: {$transactionType}");
        }

        // Add invoice type and transaction type to sales data
        $salesData['invoice_type'] = $invoiceType;
        $salesData['transaction_type'] = $transactionType;

        if ($queue) {
            // Dispatch job to queue
            \Mak8Tech\ZraSmartInvoice\Jobs\ProcessZraTransaction::dispatch('sales', $salesData);

            return [
                'success' => true,
                'message' => 'Sales data queued for processing',
                'reference' => uniqid('zra_queued_', true),
            ];
        }

        $endpoint = '/sales/selectSaleInfo';

        try {
            $response = $this->makeApiRequest('POST', $endpoint, $salesData);
            return $response;
        } catch (Exception $e) {
            $this->logError('sales_data', $e->getMessage(), $salesData);
            throw $e;
        }
    }

    /**
     * Send purchase data to ZRA
     *
     * @param array $purchaseData
     * @param string|null $invoiceType Type of invoice (NORMAL, COPY, TRAINING, PROFORMA)
     * @param string|null $transactionType Type of transaction (SALE, CREDIT_NOTE, DEBIT_NOTE, etc)
     * @param bool $queue Whether to process the request in a queue
     * @return array
     * @throws Exception
     */
    public function sendPurchaseData(array $purchaseData, ?string $invoiceType = null, ?string $transactionType = null, bool $queue = false): array
    {
        $this->ensureInitialized();

        // Set invoice type and transaction type if not provided
        $invoiceType = $invoiceType ?? config('zra.default_invoice_type');
        $transactionType = $transactionType ?? config('zra.default_transaction_type');

        // Validate invoice type
        if (!array_key_exists($invoiceType, config('zra.invoice_types'))) {
            throw new Exception("Invalid invoice type: {$invoiceType}");
        }

        // Validate transaction type
        if (!array_key_exists($transactionType, config('zra.transaction_types'))) {
            throw new Exception("Invalid transaction type: {$transactionType}");
        }

        // Add invoice type and transaction type to purchase data
        $purchaseData['invoice_type'] = $invoiceType;
        $purchaseData['transaction_type'] = $transactionType;

        if ($queue) {
            // Dispatch job to queue
            \Mak8Tech\ZraSmartInvoice\Jobs\ProcessZraTransaction::dispatch('purchase', $purchaseData);

            return [
                'success' => true,
                'message' => 'Purchase data queued for processing',
                'reference' => uniqid('zra_queued_', true),
            ];
        }

        $endpoint = '/purchases/selectPurchaseInfo';

        try {
            $response = $this->makeApiRequest('POST', $endpoint, $purchaseData);
            return $response;
        } catch (Exception $e) {
            $this->logError('purchase_data', $e->getMessage(), $purchaseData);
            throw $e;
        }
    }

    /**
     * Send stock data to ZRA
     *
     * @param array $stockData
     * @param string|null $invoiceType Type of invoice (NORMAL, COPY, TRAINING, PROFORMA)
     * @param string|null $transactionType Type of transaction (SALE, CREDIT_NOTE, DEBIT_NOTE, etc)
     * @param bool $queue Whether to process the request in a queue
     * @return array
     * @throws Exception
     */
    public function sendStockData(array $stockData, ?string $invoiceType = null, ?string $transactionType = null, bool $queue = false): array
    {
        $this->ensureInitialized();

        // Set invoice type and transaction type if not provided
        $invoiceType = $invoiceType ?? config('zra.default_invoice_type');
        $transactionType = $transactionType ?? config('zra.default_transaction_type');

        // Validate invoice type
        if (!array_key_exists($invoiceType, config('zra.invoice_types'))) {
            throw new Exception("Invalid invoice type: {$invoiceType}");
        }

        // Validate transaction type
        if (!array_key_exists($transactionType, config('zra.transaction_types'))) {
            throw new Exception("Invalid transaction type: {$transactionType}");
        }

        // Add invoice type and transaction type to stock data
        $stockData['invoice_type'] = $invoiceType;
        $stockData['transaction_type'] = $transactionType;

        if ($queue) {
            // Dispatch job to queue
            \Mak8Tech\ZraSmartInvoice\Jobs\ProcessZraTransaction::dispatch('stock', $stockData);

            return [
                'success' => true,
                'message' => 'Stock data queued for processing',
                'reference' => uniqid('zra_queued_', true),
            ];
        }

        $endpoint = '/stock/selectStockInfo';

        try {
            $response = $this->makeApiRequest('POST', $endpoint, $stockData);
            return $response;
        } catch (Exception $e) {
            $this->logError('stock_data', $e->getMessage(), $stockData);
            throw $e;
        }
    }

    /**
     * Get current ZRA configuration
     *
     * @return ZraConfig|null
     */
    public function getConfig(): ?ZraConfig
    {
        return $this->config;
    }

    /**
     * Check if device is initialized
     *
     * @return bool
     */
    public function isInitialized(): bool
    {
        return $this->config && $this->config->isInitialized();
    }

    /**
     * Get recent transaction logs
     *
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRecentLogs(int $limit = 10)
    {
        return ZraTransactionLog::latest()->limit($limit)->get();
    }

    /**
     * Make an API request to the ZRA API
     *
     * @param string $method
     * @param string $endpoint
     * @param array $data
     * @return array
     * @throws Exception
     */
    protected function makeApiRequest(string $method, string $endpoint, array $data): array
    {
        $reference = uniqid('zra_', true);
        $options = ['json' => $data];

        // Add API key if we have it
        if ($this->config && $this->config->api_key) {
            $options['headers'] = [
                'X-API-KEY' => $this->config->api_key,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ];
        }

        try {
            // Log the request if enabled
            if (config('zra.log_requests', true)) {
                Log::info("ZRA API Request [{$reference}]", [
                    'method' => $method,
                    'endpoint' => $endpoint,
                    'data' => $this->sanitizeForLogs($data),
                ]);
            }

            // Make the request
            $response = $this->httpClient->request($method, $endpoint, $options);
            $statusCode = $response->getStatusCode();
            $responseData = json_decode($response->getBody()->getContents(), true) ?? [];

            // Log the response if enabled
            if (config('zra.log_requests', true)) {
                Log::info("ZRA API Response [{$reference}]", [
                    'status_code' => $statusCode,
                    'data' => $this->sanitizeForLogs($responseData),
                ]);
            }

            // Create transaction log
            $transactionType = $this->determineTransactionType($endpoint);
            ZraTransactionLog::createLog(
                $transactionType,
                $reference,
                $data,
                $responseData,
                $statusCode >= 200 && $statusCode < 300 ? 'success' : 'failed',
                $statusCode >= 400 ? "HTTP Error: {$statusCode}" : null
            );

            // Check for success
            if ($statusCode >= 200 && $statusCode < 300) {
                return [
                    'success' => true,
                    'data' => $responseData,
                    'reference' => $reference,
                ];
            }

            // Handle errors
            return [
                'success' => false,
                'error' => $responseData['message'] ?? "HTTP Error: {$statusCode}",
                'status_code' => $statusCode,
                'reference' => $reference,
            ];
        } catch (GuzzleException $e) {
            // Create error log
            $transactionType = $this->determineTransactionType($endpoint);
            ZraTransactionLog::createLog(
                $transactionType,
                $reference,
                $data,
                null,
                'failed',
                $e->getMessage()
            );

            // Log error
            Log::error("ZRA API Error [{$reference}]", [
                'message' => $e->getMessage(),
                'method' => $method,
                'endpoint' => $endpoint,
            ]);

            throw new Exception("ZRA API request failed: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Store configuration after successful initialization
     *
     * @param string $tpin
     * @param string $branchId
     * @param string $deviceSerial
     * @param array $apiResponse
     * @return ZraConfig
     */
    protected function storeConfig(string $tpin, string $branchId, string $deviceSerial, array $apiResponse): ZraConfig
    {
        $config = new ZraConfig();
        $config->tpin = $tpin;
        $config->branch_id = $branchId;
        $config->device_serial = $deviceSerial;
        $config->api_key = $apiResponse['api_key'] ?? null;
        $config->environment = config('zra.base_url') === 'https://api-sandbox.zra.org.zm/vsdc-api/v1' ? 'sandbox' : 'production';
        $config->last_initialized_at = now();
        $config->additional_config = [
            'device_id' => $apiResponse['device_id'] ?? null,
            'other_config' => $apiResponse['additional_config'] ?? null,
        ];
        $config->save();

        // Update the current config
        $this->config = $config;

        return $config;
    }

    /**
     * Ensure the device is initialized before making API calls
     *
     * @throws Exception
     */
    protected function ensureInitialized(): void
    {
        if (!$this->isInitialized()) {
            throw new Exception('ZRA device is not initialized. Please initialize the device first.');
        }
    }

    /**
     * Validate initialization parameters
     *
     * @param string $tpin
     * @param string $branchId
     * @param string $deviceSerial
     * @throws Exception
     */
    protected function validateInitParams(string $tpin, string $branchId, string $deviceSerial): void
    {
        if (strlen($tpin) !== 10) {
            throw new Exception('TPIN must be 10 characters long.');
        }

        if (strlen($branchId) !== 3) {
            throw new Exception('Branch ID must be 3 characters long.');
        }

        if (empty($deviceSerial) || strlen($deviceSerial) > 100) {
            throw new Exception('Device Serial Number is required and cannot exceed 100 characters.');
        }
    }

    /**
     * Log an error
     *
     * @param string $type
     * @param string $message
     * @param array $data
     * @return void
     */
    protected function logError(string $type, string $message, array $data): void
    {
        Log::error("ZRA {$type} error: {$message}", [
            'data' => $this->sanitizeForLogs($data),
        ]);
    }

    /**
     * Sanitize data for logging to remove sensitive information
     *
     * @param array $data
     * @return array
     */
    protected function sanitizeForLogs(array $data): array
    {
        $sanitized = $data;

        // Remove sensitive keys
        $sensitiveKeys = ['api_key', 'key', 'password'];
        foreach ($sensitiveKeys as $key) {
            if (isset($sanitized[$key])) {
                $sanitized[$key] = '********';
            }
        }

        return $sanitized;
    }

    /**
     * Determine transaction type from endpoint
     *
     * @param string $endpoint
     * @return string
     */
    protected function determineTransactionType(string $endpoint): string
    {
        if (strpos($endpoint, '/initializer/') !== false) {
            return 'initialization';
        }

        if (strpos($endpoint, '/sales/') !== false) {
            return 'sales';
        }

        if (strpos($endpoint, '/purchases/') !== false) {
            return 'purchase';
        }

        if (strpos($endpoint, '/stock/') !== false) {
            return 'stock';
        }

        return 'general';
    }

    /**
     * Get transaction statistics
     * 
     * @return array
     */
    public function getStatistics(): array
    {
        $totalCount = ZraTransactionLog::count();
        $successCount = ZraTransactionLog::where('status', 'success')->count();
        $failedCount = ZraTransactionLog::where('status', 'failed')->count();

        $successRate = $totalCount > 0 ? round(($successCount / $totalCount) * 100, 1) : 0;

        $lastTransaction = ZraTransactionLog::latest()->first();

        return [
            'total_transactions' => $totalCount,
            'successful_transactions' => $successCount,
            'failed_transactions' => $failedCount,
            'success_rate' => $successRate,
            'last_transaction_date' => $lastTransaction ? $lastTransaction->created_at->format('Y-m-d H:i:s') : null,
        ];
    }

    /**
     * Perform a health check on the ZRA API
     * 
     * @return array
     */
    public function healthCheck(): array
    {
        if (!$this->isInitialized()) {
            return [
                'success' => false,
                'message' => 'Device not initialized',
                'status' => 'not_initialized',
            ];
        }

        try {
            // Try to make a ping or simple API call to check if the connection works
            $baseUrl = config('zra.base_url');
            $client = new Client(['timeout' => 5]);
            $response = $client->request('GET', $baseUrl, ['http_errors' => false]);

            $statusCode = $response->getStatusCode();

            if ($statusCode >= 200 && $statusCode < 500) {
                return [
                    'success' => true,
                    'message' => 'API connection successful',
                    'status' => 'connected',
                    'status_code' => $statusCode,
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'API connection failed with status ' . $statusCode,
                    'status' => 'error',
                    'status_code' => $statusCode,
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'API connection failed: ' . $e->getMessage(),
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }
}
