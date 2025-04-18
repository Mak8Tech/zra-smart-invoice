<?php

namespace Mak8Tech\ZraSmartInvoice\Services;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Mak8Tech\ZraSmartInvoice\Models\ZraConfig;
use Mak8Tech\ZraSmartInvoice\Models\ZraTransactionLog;
use Mak8Tech\ZraSmartInvoice\Services\ZraTaxService;
use Mak8Tech\ZraSmartInvoice\Services\ZraReportService;

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
     * @var ZraTaxService
     */
    protected $taxService;

    /**
     * @var string|null
     */
    protected $privateKeyPath;

    /**
     * @var string|null
     */
    protected $certificatePath;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->config = ZraConfig::getActive();
        $this->taxService = new ZraTaxService();

        // Set up paths for digital signature
        $this->privateKeyPath = config('zra.private_key_path');
        $this->certificatePath = config('zra.certificate_path');

        $this->httpClient = new Client([
            'base_uri' => config('zra.base_url'),
            'timeout' => config('zra.timeout'),
            'http_errors' => false,
            // Force TLS 1.2 or higher for security
            'curl' => [
                CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2,
            ],
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

        // Process tax calculations if auto-calculate is enabled and items are provided
        if (config('zra.auto_calculate_tax', true) && isset($salesData['items']) && is_array($salesData['items'])) {
            $taxCalculation = $this->taxService->calculateInvoiceTax($salesData['items']);
            $formattedTaxData = $this->taxService->formatTaxForApi($taxCalculation);

            // Merge the tax data with the sales data
            $salesData['items'] = $formattedTaxData['items'];
            $salesData['totalAmount'] = $formattedTaxData['totalAmount'];
            $salesData['totalTax'] = $formattedTaxData['totalTax'];
            $salesData['taxSummary'] = $formattedTaxData['taxSummary'];
        }

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

        // Process tax calculations if auto-calculate is enabled and items are provided
        if (config('zra.auto_calculate_tax', true) && isset($purchaseData['items']) && is_array($purchaseData['items'])) {
            $taxCalculation = $this->taxService->calculateInvoiceTax($purchaseData['items']);
            $formattedTaxData = $this->taxService->formatTaxForApi($taxCalculation);

            // Merge the tax data with the purchase data
            $purchaseData['items'] = $formattedTaxData['items'];
            $purchaseData['totalAmount'] = $formattedTaxData['totalAmount'];
            $purchaseData['totalTax'] = $formattedTaxData['totalTax'];
            $purchaseData['taxSummary'] = $formattedTaxData['taxSummary'];
        }

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

        // Process tax calculations if auto-calculate is enabled and items are provided
        if (config('zra.auto_calculate_tax', true) && isset($stockData['items']) && is_array($stockData['items'])) {
            $taxCalculation = $this->taxService->calculateInvoiceTax($stockData['items']);
            $formattedTaxData = $this->taxService->formatTaxForApi($taxCalculation);

            // Merge the tax data with the stock data
            $stockData['items'] = $formattedTaxData['items'];
            $stockData['totalAmount'] = $formattedTaxData['totalAmount'];
            $stockData['totalTax'] = $formattedTaxData['totalTax'];
            $stockData['taxSummary'] = $formattedTaxData['taxSummary'];
        }

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
     * @throws GuzzleException|Exception
     */
    protected function makeApiRequest(string $method, string $endpoint, array $data): array
    {
        $reference = uniqid('zra_', true);
        $options = ['json' => $data];

        // Prepare headers
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        // Add API key if we have it
        if ($this->config && $this->config->api_key) {
            $headers['X-API-KEY'] = $this->config->api_key;
        }

        // Add digital signature if enabled
        if (config('zra.use_digital_signatures', false)) {
            $signature = $this->createDigitalSignature($data);
            if ($signature) {
                $headers['X-ZRA-Signature'] = $signature;
            }
        }

        $options['headers'] = $headers;

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
                    'message' => $responseData['message'] ?? 'Operation successful',
                    'data' => $responseData['data'] ?? $responseData,
                    'reference' => $reference,
                ];
            }

            // Handle errors
            return [
                'success' => false,
                'message' => $responseData['message'] ?? 'API request failed',
                'error' => $responseData['error'] ?? "HTTP Error: {$statusCode}",
                'reference' => $reference,
            ];
        } catch (GuzzleException $e) {
            $errorMessage = $e->getMessage();

            // Log the error
            Log::error("ZRA API Request Error [{$reference}]", [
                'method' => $method,
                'endpoint' => $endpoint,
                'error' => $errorMessage,
            ]);

            // Create failure log
            ZraTransactionLog::createLog(
                $transactionType ?? $this->determineTransactionType($endpoint),
                $reference,
                $data,
                ['error' => $errorMessage],
                'failed',
                $errorMessage
            );

            return [
                'success' => false,
                'message' => 'API connection error',
                'error' => $errorMessage,
                'reference' => $reference,
            ];
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

    /**
     * Get all available tax categories
     *
     * @return array
     */
    public function getTaxCategories(): array
    {
        return $this->taxService->getTaxCategories();
    }

    /**
     * Get all exemption categories
     *
     * @return array
     */
    public function getExemptionCategories(): array
    {
        return $this->taxService->getExemptionCategories();
    }

    /**
     * Calculate tax for an invoice
     *
     * @param array $items
     * @return array
     */
    public function calculateTax(array $items): array
    {
        return $this->taxService->calculateInvoiceTax($items);
    }

    /**
     * Check if a tax category is zero-rated
     *
     * @param string $taxCategory
     * @return bool
     */
    public function isZeroRated(string $taxCategory): bool
    {
        return $this->taxService->isZeroRated($taxCategory);
    }

    /**
     * Check if a tax category is exempt
     *
     * @param string $taxCategory
     * @return bool
     */
    public function isExempt(string $taxCategory): bool
    {
        return $this->taxService->isExempt($taxCategory);
    }

    /**
     * Generate a report
     *
     * @param string $type Type of report (x, z, daily, monthly)
     * @param \Illuminate\Support\Carbon|null $date Date for the report (defaults to today)
     * @return array
     * @throws Exception
     */
    public function generateReport(string $type, ?\Illuminate\Support\Carbon $date = null): array
    {
        $this->ensureInitialized();

        // Use the ZraReportService to generate the report
        $reportService = app(ZraReportService::class);
        return $reportService->generateReport($type, $date ?? now());
    }

    /**
     * Generate an X report (interim report for current day)
     *
     * @return array
     * @throws Exception
     */
    public function generateXReport(): array
    {
        return $this->generateReport('x');
    }

    /**
     * Generate a Z report (finalized report for a day)
     *
     * @param \Illuminate\Support\Carbon|null $date
     * @return array
     * @throws Exception
     */
    public function generateZReport(?\Illuminate\Support\Carbon $date = null): array
    {
        return $this->generateReport('z', $date);
    }

    /**
     * Generate a daily report
     *
     * @param \Illuminate\Support\Carbon|null $date
     * @return array
     * @throws Exception
     */
    public function generateDailyReport(?\Illuminate\Support\Carbon $date = null): array
    {
        return $this->generateReport('daily', $date);
    }

    /**
     * Generate a monthly report
     *
     * @param \Illuminate\Support\Carbon|null $date
     * @return array
     * @throws Exception
     */
    public function generateMonthlyReport(?\Illuminate\Support\Carbon $date = null): array
    {
        return $this->generateReport('monthly', $date);
    }

    /**
     * Create a digital signature for the provided data
     *
     * @param array $data The data to sign
     * @return string|null The digital signature or null if signing failed
     */
    protected function createDigitalSignature(array $data): ?string
    {
        // Skip if digital signatures are not configured
        if (!$this->isDigitalSignatureConfigured()) {
            Log::warning('Digital signature requested but not configured. Define private_key_path in config/zra.php');
            return null;
        }

        try {
            // Convert data to a canonical JSON string (to ensure consistent signing)
            $canonicalData = $this->canonicalizeData($data);

            // Create signature
            $privateKey = openssl_pkey_get_private('file://' . $this->privateKeyPath);
            if ($privateKey === false) {
                Log::error('Failed to load private key for digital signature', [
                    'path' => $this->privateKeyPath,
                    'error' => openssl_error_string(),
                ]);
                return null;
            }

            $signature = null;
            if (!openssl_sign($canonicalData, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
                Log::error('Failed to create digital signature', [
                    'error' => openssl_error_string(),
                ]);
                return null;
            }

            // Free the key from memory
            openssl_free_key($privateKey);

            // Return base64 encoded signature
            return base64_encode($signature);
        } catch (\Exception $e) {
            Log::error('Exception while creating digital signature', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Verify a digital signature against provided data
     *
     * @param array $data The data to verify
     * @param string $signature The signature to verify (base64 encoded)
     * @return bool Whether the signature is valid
     */
    public function verifyDigitalSignature(array $data, string $signature): bool
    {
        // Skip if digital signatures are not configured
        if (!$this->isDigitalSignatureConfigured()) {
            Log::warning('Digital signature verification requested but not configured');
            return false;
        }

        try {
            // Convert data to a canonical JSON string (same as during signing)
            $canonicalData = $this->canonicalizeData($data);

            // Decode the base64 signature
            $binarySignature = base64_decode($signature);
            if ($binarySignature === false) {
                Log::error('Failed to decode base64 signature');
                return false;
            }

            // Get the certificate
            $certificate = openssl_x509_read('file://' . $this->certificatePath);
            if ($certificate === false) {
                Log::error('Failed to load certificate for signature verification', [
                    'path' => $this->certificatePath,
                    'error' => openssl_error_string(),
                ]);
                return false;
            }

            // Get public key from certificate
            $publicKey = openssl_pkey_get_public($certificate);
            if ($publicKey === false) {
                Log::error('Failed to extract public key from certificate', [
                    'error' => openssl_error_string(),
                ]);
                return false;
            }

            // Verify signature
            $result = openssl_verify($canonicalData, $binarySignature, $publicKey, OPENSSL_ALGO_SHA256);

            // Free resources
            openssl_free_key($publicKey);

            // Check result
            if ($result === 1) {
                return true;
            } elseif ($result === 0) {
                Log::warning('Digital signature verification failed - invalid signature');
                return false;
            } else {
                Log::error('Error during signature verification', [
                    'error' => openssl_error_string(),
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Exception while verifying digital signature', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    /**
     * Check if digital signature is configured
     *
     * @return bool
     */
    protected function isDigitalSignatureConfigured(): bool
    {
        return !empty($this->privateKeyPath) &&
            !empty($this->certificatePath) &&
            file_exists($this->privateKeyPath) &&
            file_exists($this->certificatePath);
    }

    /**
     * Canonicalize data for consistent signing
     *
     * @param array $data
     * @return string
     */
    protected function canonicalizeData(array $data): string
    {
        // Sort keys recursively and convert to JSON with no whitespace
        $this->ksortRecursive($data);
        return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Sort array keys recursively
     *
     * @param array &$array
     * @return void
     */
    protected function ksortRecursive(array &$array): void
    {
        ksort($array);
        foreach ($array as &$value) {
            if (is_array($value)) {
                $this->ksortRecursive($value);
            }
        }
    }
}
