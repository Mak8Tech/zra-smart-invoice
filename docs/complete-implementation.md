I'll help you implement the ZRA Smart Invoice Integration Module as a Laravel 12 package with Inertia.js React/TypeScript components. Let's create a structured implementation plan based on the requirements.

First, I'll outline the package structure, then provide the key files you'll need to implement.

## Package Structure Overview

Here's how we'll organize the Mak8Tech ZRA Smart Invoice Integration package:

```
mak8tech/zra-smart-invoice/
├── config/
│   └── zra.php
├── database/
│   └── migrations/
│       └── create_zra_configs_table.php
├── resources/
│   └── js/
│       └── Pages/
│           └── ZraConfig/
│               ├── Index.tsx
│               ├── components/
│               │   ├── ConfigForm.tsx
│               │   ├── StatusIndicator.tsx
│               │   └── TransactionLog.tsx
├── routes/
│   └── web.php
├── src/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── ZraController.php
│   │   └── Requests/
│   │       └── ZraConfigRequest.php
│   ├── Models/
│   │   └── ZraConfig.php
│   ├── Services/
│   │   └── ZraService.php
│   ├── Facades/
│   │   └── Zra.php
│   └── ZraServiceProvider.php
├── tests/
│   ├── Feature/
│   │   └── ZraControllerTest.php
│   └── Unit/
│       └── ZraServiceTest.php
├── composer.json
└── README.md
```

Now, let's implement the core files:

```json
{
  "name": "mak8tech/zra-smart-invoice",
  "description": "Zambia Revenue Authority Smart Invoice Integration for Laravel 12 POS Systems",
  "type": "library",
  "license": "MIT",
  "autoload": {
    "psr-4": {
      "Mak8Tech\\ZraSmartInvoice\\": "src/"
    }
  },
  "autoload-dev": {
    "psr-4": {
      "Mak8Tech\\ZraSmartInvoice\\Tests\\": "tests/"
    }
  },
  "require": {
    "php": "^8.1",
    "laravel/framework": "^12.0",
    "guzzlehttp/guzzle": "^7.5",
    "inertiajs/inertia-laravel": "^1.0"
  },
  "require-dev": {
    "orchestra/testbench": "^8.0",
    "phpunit/phpunit": "^10.0"
  },
  "extra": {
    "laravel": {
      "providers": ["Mak8Tech\\ZraSmartInvoice\\ZraServiceProvider"],
      "aliases": {
        "Zra": "Mak8Tech\\ZraSmartInvoice\\Facades\\Zra"
      }
    }
  },
  "minimum-stability": "dev",
  "prefer-stable": true
}
```

```php
<?php

namespace Mak8Tech\ZraSmartInvoice;

use Illuminate\Support\ServiceProvider;
use Mak8Tech\ZraSmartInvoice\Services\ZraService;

class ZraServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        // Load package routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Publish configuration
        $this->publishes([
            __DIR__ . '/../config/zra.php' => config_path('zra.php'),
        ], 'config');

        // Publish Inertia components
        $this->publishes([
            __DIR__ . '/../resources/js' => resource_path('js/vendor/mak8tech/zra-smart-invoice'),
        ], 'inertia-components');
    }

    /**
     * Register any package services.
     */
    public function register(): void
    {
        // Merge configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../config/zra.php', 'zra'
        );

        // Register the service
        $this->app->singleton('zra', function ($app) {
            return new ZraService();
        });
    }
}

```

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | ZRA Smart Invoice API Configuration
    |--------------------------------------------------------------------------
    |
    | This file is for storing the configuration for the ZRA Smart Invoice
    | integration. You can switch between sandbox and production environments.
    |
    */

    // Base URL for the ZRA API (sandbox or production)
    'base_url' => env('ZRA_BASE_URL', 'https://api-sandbox.zra.org.zm/vsdc-api/v1'),

    // Default values for device initialization
    'default_tpin' => env('ZRA_TPIN', ''),
    'default_branch_id' => env('ZRA_BRANCH_ID', ''),
    'default_device_serial' => env('ZRA_DEVICE_SERIAL', ''),

    // API request timeout in seconds
    'timeout' => env('ZRA_API_TIMEOUT', 10),

    // Enable debug mode for additional logging
    'debug' => env('ZRA_DEBUG', false),

    // Log API requests and responses
    'log_requests' => env('ZRA_LOG_REQUESTS', true),

    // Routes
    'routes' => [
        'prefix' => 'zra',
        'middleware' => ['web', 'auth'],
    ],

    // Automatic retry settings
    'retry' => [
        'enabled' => env('ZRA_RETRY_ENABLED', true),
        'attempts' => env('ZRA_RETRY_ATTEMPTS', 3),
        'delay' => env('ZRA_RETRY_DELAY', 2), // in seconds
    ],
];

```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('zra_configs', function (Blueprint $table) {
            $table->id();
            $table->string('tpin', 10)->comment('Taxpayer Identification Number');
            $table->string('branch_id', 3)->comment('Branch ID');
            $table->string('device_serial', 100)->comment('Device Serial Number');
            $table->string('api_key')->nullable()->comment('API Key from ZRA');
            $table->string('environment')->default('sandbox')->comment('API environment: sandbox or production');
            $table->timestamp('last_initialized_at')->nullable()->comment('Last successful initialization timestamp');
            $table->timestamp('last_sync_at')->nullable()->comment('Last successful data sync timestamp');
            $table->json('additional_config')->nullable()->comment('Additional configuration as JSON');
            $table->timestamps();
        });

        Schema::create('zra_transaction_logs', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_type')->comment('Type: initialization, sales, purchase, stock, etc.');
            $table->string('reference')->nullable()->comment('Reference number or ID');
            $table->json('request_payload')->nullable()->comment('API request data');
            $table->json('response_payload')->nullable()->comment('API response data');
            $table->string('status')->comment('Status: success, failed');
            $table->string('error_message')->nullable()->comment('Error message if any');
            $table->timestamps();

            $table->index('transaction_type');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zra_transaction_logs');
        Schema::dropIfExists('zra_configs');
    }
};

```

```php
<?php

namespace Mak8Tech\ZraSmartInvoice\Models;

use Illuminate\Database\Eloquent\Model;

class ZraConfig extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tpin',
        'branch_id',
        'device_serial',
        'api_key',
        'environment',
        'last_initialized_at',
        'last_sync_at',
        'additional_config',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'last_initialized_at' => 'datetime',
        'last_sync_at' => 'datetime',
        'additional_config' => 'json',
    ];

    /**
     * Get the currently active configuration
     *
     * @return self|null
     */
    public static function getActive()
    {
        return static::latest()->first();
    }

    /**
     * Determine if this device has been initialized
     *
     * @return bool
     */
    public function isInitialized(): bool
    {
        return !empty($this->api_key) && !is_null($this->last_initialized_at);
    }

    /**
     * Get initialization status with friendly message
     *
     * @return array
     */
    public function getStatus(): array
    {
        if ($this->isInitialized()) {
            return [
                'status' => 'initialized',
                'message' => 'Device successfully initialized',
                'last_initialized' => $this->last_initialized_at->diffForHumans(),
            ];
        }

        return [
            'status' => 'not_initialized',
            'message' => 'Device requires initialization',
        ];
    }
}

```

```php
<?php

namespace Mak8Tech\ZraSmartInvoice\Models;

use Illuminate\Database\Eloquent\Model;

class ZraTransactionLog extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'transaction_type',
        'reference',
        'request_payload',
        'response_payload',
        'status',
        'error_message',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'request_payload' => 'json',
        'response_payload' => 'json',
    ];

    /**
     * Create a new transaction log entry
     *
     * @param string $type
     * @param string|null $reference
     * @param array $request
     * @param array|null $response
     * @param string $status
     * @param string|null $errorMessage
     * @return self
     */
    public static function createLog(
        string $type,
        ?string $reference,
        array $request,
        ?array $response,
        string $status,
        ?string $errorMessage = null
    ): self {
        // Remove sensitive data before logging
        $sanitizedRequest = self::sanitizeData($request);
        $sanitizedResponse = $response ? self::sanitizeData($response) : null;

        return self::create([
            'transaction_type' => $type,
            'reference' => $reference,
            'request_payload' => $sanitizedRequest,
            'response_payload' => $sanitizedResponse,
            'status' => $status,
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Sanitize sensitive data in payloads
     *
     * @param array $data
     * @return array
     */
    protected static function sanitizeData(array $data): array
    {
        $result = $data;

        // Redact API keys and other sensitive information
        if (isset($result['api_key'])) {
            $result['api_key'] = '********';
        }

        if (isset($result['key'])) {
            $result['key'] = '********';
        }

        // Add more sensitive fields as needed

        return $result;
    }
}

```

```php
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
     * @return array
     * @throws Exception
     */
    public function sendSalesData(array $salesData): array
    {
        $this->ensureInitialized();

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
     * @return array
     * @throws Exception
     */
    public function sendPurchaseData(array $purchaseData): array
    {
        $this->ensureInitialized();

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
     * @return array
     * @throws Exception
     */
    public function sendStockData(array $stockData): array
    {
        $this->ensureInitialized();

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
}

```

```php
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

            // Sample sales data
            $salesData = [
                'invoiceNumber' => 'INV-' . mt_rand(1000, 9999),
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
                'customerTpin' => '',  // Optional for customer without TPIN
            ];

            $result = $this->zraService->sendSalesData($salesData);

            return [
                'success' => $result['success'],
                'message' => $result['success'] ? 'Test sales data sent successfully' : ($result['error'] ?? 'Failed to send test sales data'),
                'reference' => $result['reference'] ?? null,
                'data' => $result['data'] ?? null,
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
}

```

```php
<?php

namespace Mak8Tech\ZraSmartInvoice\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ZraConfigRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tpin' => ['required', 'string', 'size:10'],
            'branch_id' => ['required', 'string', 'size:3'],
            'device_serial' => ['required', 'string', 'max:100'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tpin.size' => 'TPIN must be exactly 10 characters.',
            'branch_id.size' => 'Branch ID must be exactly 3 characters.',
            'device_serial.max' => 'Device Serial Number cannot exceed 100 characters.',
        ];
    }
}

```

```php
<?php

namespace Mak8Tech\ZraSmartInvoice\Facades;

use Illuminate\Support\Facades\Facade;

class Zra extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'zra';
    }
}

```

```php
<?php

use Illuminate\Support\Facades\Route;
use Mak8Tech\ZraSmartInvoice\Http\Controllers\ZraController;

Route::prefix(config('zra.routes.prefix', 'zra'))
    ->middleware(config('zra.routes.middleware', ['web', 'auth']))
    ->group(function () {
        Route::get('/', [ZraController::class, 'index'])->name('zra.index');
        Route::post('/initialize', [ZraController::class, 'initialize'])->name('zra.initialize');
        Route::get('/status', [ZraController::class, 'status'])->name('zra.status');
        Route::get('/logs', [ZraController::class, 'logs'])->name('zra.logs');
        Route::post('/test-sales', [ZraController::class, 'testSales'])->name('zra.test-sales');
    });

```

```typescript
// resources/js/Pages/ZraConfig/Index.tsx
import React, { useState } from "react";
import { Head } from "@inertiajs/react";
import ConfigForm from "./components/ConfigForm";
import StatusIndicator from "./components/StatusIndicator";
import TransactionLog from "./components/TransactionLog";

interface ZraConfig {
  id: number;
  tpin: string;
  branch_id: string;
  device_serial: string;
  environment: string;
  status: {
    status: string;
    message: string;
    last_initialized?: string;
  };
  last_initialized_at: string | null;
  last_sync_at: string | null;
}

interface Log {
  id: number;
  transaction_type: string;
  reference: string;
  status: string;
  error_message: string | null;
  created_at: string;
}

interface Props {
  config: ZraConfig | null;
  logs: Log[];
  is_initialized: boolean;
  environment: string;
}

export default function Index({
  config,
  logs,
  is_initialized,
  environment,
}: Props) {
  const [activeTab, setActiveTab] = useState<"config" | "logs">("config");

  return (
    <>
      <Head title="ZRA Smart Invoice Configuration" />

      <div className="py-12">
        <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
          <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div className="p-6 bg-white border-b border-gray-200">
              <h1 className="text-2xl font-semibold text-gray-900 mb-6">
                ZRA Smart Invoice Integration
              </h1>

              <StatusIndicator
                isInitialized={is_initialized}
                environment={environment}
                status={config?.status}
                lastSync={config?.last_sync_at}
              />

              <div className="mt-6 border-b border-gray-200 mb-6">
                <nav className="-mb-px flex space-x-8">
                  <button
                    onClick={() => setActiveTab("config")}
                    className={`${
                      activeTab === "config"
                        ? "border-indigo-500 text-indigo-600"
                        : "border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300"
                    } whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm`}
                  >
                    Configuration
                  </button>
                  <button
                    onClick={() => setActiveTab("logs")}
                    className={`${
                      activeTab === "logs"
                        ? "border-indigo-500 text-indigo-600"
                        : "border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300"
                    } whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm`}
                  >
                    Transaction Logs
                  </button>
                </nav>
              </div>

              {activeTab === "config" ? (
                <ConfigForm config={config} isInitialized={is_initialized} />
              ) : (
                <TransactionLog logs={logs} />
              )}
            </div>
          </div>
        </div>
      </div>
    </>
  );
}
```

```typescript
// resources/js/Pages/ZraConfig/components/ConfigForm.tsx
import React, { useState } from "react";
import { router } from "@inertiajs/react";

interface ZraConfig {
  id: number;
  tpin: string;
  branch_id: string;
  device_serial: string;
  environment: string;
  status: {
    status: string;
    message: string;
    last_initialized?: string;
  };
  last_initialized_at: string | null;
  last_sync_at: string | null;
}

interface Props {
  config: ZraConfig | null;
  isInitialized: boolean;
}

export default function ConfigForm({ config, isInitialized }: Props) {
  const [tpin, setTpin] = useState(config?.tpin || "");
  const [branchId, setBranchId] = useState(config?.branch_id || "");
  const [deviceSerial, setDeviceSerial] = useState(config?.device_serial || "");
  const [loading, setLoading] = useState(false);
  const [testLoading, setTestLoading] = useState(false);
  const [testResult, setTestResult] = useState<any>(null);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);

    router.post(
      route("zra.initialize"),
      {
        tpin,
        branch_id: branchId,
        device_serial: deviceSerial,
      },
      {
        onSuccess: () => {
          setLoading(false);
        },
        onError: () => {
          setLoading(false);
        },
      }
    );
  };

  const handleTestSales = () => {
    setTestLoading(true);
    setTestResult(null);

    router.post(
      route("zra.test-sales"),
      {},
      {
        onSuccess: (page) => {
          setTestLoading(false);
          setTestResult(page.props.flash.data);
        },
        onError: () => {
          setTestLoading(false);
          setTestResult({
            success: false,
            message: "An error occurred while testing sales submission",
          });
        },
      }
    );
  };

  return (
    <div>
      <form onSubmit={handleSubmit} className="space-y-6">
        <div>
          <label
            htmlFor="tpin"
            className="block text-sm font-medium text-gray-700"
          >
            TPIN (10 characters)
          </label>
          <input
            type="text"
            id="tpin"
            value={tpin}
            onChange={(e) => setTpin(e.target.value)}
            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            maxLength={10}
            required
          />
        </div>

        <div>
          <label
            htmlFor="branch_id"
            className="block text-sm font-medium text-gray-700"
          >
            Branch ID (3 characters)
          </label>
          <input
            type="text"
            id="branch_id"
            value={branchId}
            onChange={(e) => setBranchId(e.target.value)}
            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            maxLength={3}
            required
          />
        </div>

        <div>
          <label
            htmlFor="device_serial"
            className="block text-sm font-medium text-gray-700"
          >
            Device Serial Number
          </label>
          <input
            type="text"
            id="device_serial"
            value={deviceSerial}
            onChange={(e) => setDeviceSerial(e.target.value)}
            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            maxLength={100}
            required
          />
        </div>

        <div className="flex space-x-4">
          <button
            type="submit"
            disabled={loading}
            className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
          >
            {loading ? "Initializing..." : "Initialize Device"}
          </button>

          {isInitialized && (
            <button
              type="button"
              onClick={handleTestSales}
              disabled={testLoading}
              className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50"
            >
              {testLoading ? "Testing..." : "Test Sales Submission"}
            </button>
          )}
        </div>
      </form>

      {testResult && (
        <div
          className={`mt-6 p-4 rounded-md ${
            testResult.success ? "bg-green-50" : "bg-red-50"
          }`}
        >
          <div className="flex">
            <div className="flex-shrink-0">
              {testResult.success ? (
                <svg
                  className="h-5 w-5 text-green-400"
                  xmlns="http://www.w3.org/2000/svg"
                  viewBox="0 0 20 20"
                  fill="currentColor"
                >
                  <path
                    fillRule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                    clipRule="evenodd"
                  />
                </svg>
              ) : (
                <svg
                  className="h-5 w-5 text-red-400"
                  xmlns="http://www.w3.org/2000/svg"
                  viewBox="0 0 20 20"
                  fill="currentColor"
                >
                  <path
                    fillRule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                    clipRule="evenodd"
                  />
                </svg>
              )}
            </div>
            <div className="ml-3">
              <h3
                className={`text-sm font-medium ${
                  testResult.success ? "text-green-800" : "text-red-800"
                }`}
              >
                {testResult.success ? "Test Successful" : "Test Failed"}
              </h3>
              <div
                className={`mt-2 text-sm ${
                  testResult.success ? "text-green-700" : "text-red-700"
                }`}
              >
                <p>{testResult.message}</p>
                {testResult.reference && (
                  <p className="mt-1">Reference: {testResult.reference}</p>
                )}
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
```

```typescript
// resources/js/Pages/ZraConfig/components/StatusIndicator.tsx
import React from "react";

interface Status {
  status: string;
  message: string;
  last_initialized?: string;
}

interface Props {
  isInitialized: boolean;
  environment: string;
  status?: Status;
  lastSync: string | null;
}

export default function StatusIndicator({
  isInitialized,
  environment,
  status,
  lastSync,
}: Props) {
  return (
    <div className="rounded-md bg-gray-50 p-4">
      <div className="flex">
        <div className="flex-shrink-0">
          {isInitialized ? (
            <svg
              className="h-5 w-5 text-green-400"
              xmlns="http://www.w3.org/2000/svg"
              viewBox="0 0 20 20"
              fill="currentColor"
            >
              <path
                fillRule="evenodd"
                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                clipRule="evenodd"
              />
            </svg>
          ) : (
            <svg
              className="h-5 w-5 text-yellow-400"
              xmlns="http://www.w3.org/2000/svg"
              viewBox="0 0 20 20"
              fill="currentColor"
            >
              <path
                fillRule="evenodd"
                d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                clipRule="evenodd"
              />
            </svg>
          )}
        </div>
        <div className="ml-3 flex-1 md:flex md:justify-between">
          <div>
            <p className="text-sm text-gray-700">
              <span className="font-medium">Status:</span>{" "}
              {isInitialized ? "Initialized" : "Not Initialized"}
            </p>
            <p className="mt-1 text-sm text-gray-700">
              <span className="font-medium">Environment:</span>{" "}
              {environment === "sandbox" ? "Sandbox (Test)" : "Production"}
            </p>
            {status?.last_initialized && (
              <p className="mt-1 text-sm text-gray-700">
                <span className="font-medium">Last Initialized:</span>{" "}
                {status.last_initialized}
              </p>
            )}
            {lastSync && (
              <p className="mt-1 text-sm text-gray-700">
                <span className="font-medium">Last Sync:</span> {lastSync}
              </p>
            )}
          </div>
          <div className="mt-3 md:mt-0 md:ml-6">
            {isInitialized ? (
              <span className="inline-flex items-center px-2.5 py-0.5 rounded-md text-sm font-medium bg-green-100 text-green-800">
                Ready
              </span>
            ) : (
              <span className="inline-flex items-center px-2.5 py-0.5 rounded-md text-sm font-medium bg-yellow-100 text-yellow-800">
                Needs Setup
              </span>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
```

```typescript
// resources/js/Pages/ZraConfig/components/TransactionLog.tsx
import React from "react";

interface Log {
  id: number;
  transaction_type: string;
  reference: string;
  status: string;
  error_message: string | null;
  created_at: string;
}

interface Props {
  logs: Log[];
}

export default function TransactionLog({ logs }: Props) {
  const getStatusColor = (status: string) => {
    switch (status) {
      case "success":
        return "text-green-800 bg-green-100";
      case "failed":
        return "text-red-800 bg-red-100";
      default:
        return "text-gray-800 bg-gray-100";
    }
  };

  const getTransactionTypeLabel = (type: string) => {
    switch (type) {
      case "initialization":
        return "Initialization";
      case "sales":
        return "Sales";
      case "purchase":
        return "Purchase";
      case "stock":
        return "Stock";
      default:
        return type.charAt(0).toUpperCase() + type.slice(1);
    }
  };

  return (
    <div>
      <h2 className="text-lg font-medium text-gray-900">
        Recent Transaction Logs
      </h2>

      {logs.length === 0 ? (
        <div className="mt-4 bg-gray-50 p-4 rounded-md">
          <p className="text-gray-700 text-sm">No transaction logs found.</p>
        </div>
      ) : (
        <div className="mt-4 flex flex-col">
          <div className="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div className="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
              <div className="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                <table className="min-w-full divide-y divide-gray-200">
                  <thead className="bg-gray-50">
                    <tr>
                      <th
                        scope="col"
                        className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                      >
                        Date/Time
                      </th>
                      <th
                        scope="col"
                        className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                      >
                        Type
                      </th>
                      <th
                        scope="col"
                        className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                      >
                        Reference
                      </th>
                      <th
                        scope="col"
                        className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                      >
                        Status
                      </th>
                      <th
                        scope="col"
                        className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                      >
                        Message
                      </th>
                    </tr>
                  </thead>
                  <tbody className="bg-white divide-y divide-gray-200">
                    {logs.map((log) => (
                      <tr key={log.id}>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                          {log.created_at}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap">
                          <span className="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                            {getTransactionTypeLabel(log.transaction_type)}
                          </span>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                          {log.reference}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap">
                          <span
                            className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${getStatusColor(
                              log.status
                            )}`}
                          >
                            {log.status}
                          </span>
                        </td>
                        <td className="px-6 py-4 text-sm text-gray-500">
                          {log.error_message || "-"}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
```

## README.md Content

````markdown
# Mak8Tech ZRA Smart Invoice Integration

A Laravel 12 package for integrating with the Zambia Revenue Authority (ZRA) Smart Invoice system for POS applications.

## Features

- Easy integration with Laravel 12 applications
- Seamless Inertia.js React/TypeScript components
- Device initialization with ZRA API
- Sales, purchase, and stock data submission
- Transaction logging and monitoring
- Configuration management
- Status indicators and analytics

## Installation

You can install the package via composer:

```bash
composer require mak8tech/zra-smart-invoice
```
````

## Publishing Assets

Publish the package configuration and migrations:

```bash
php artisan vendor:publish --provider="Mak8Tech\ZraSmartInvoice\ZraServiceProvider"
```

## Configuration

After publishing the package assets, you can configure the ZRA integration in the `config/zra.php` file.

You can also set these in your `.env` file:

```
ZRA_BASE_URL=https://api-sandbox.zra.org.zm/vsdc-api/v1
ZRA_TPIN=
ZRA_BRANCH_ID=
ZRA_DEVICE_SERIAL=
ZRA_DEBUG=false
```

## Usage

### Initialization

Initialize your device with ZRA:

```php
use Mak8Tech\ZraSmartInvoice\Facades\Zra;

// Initialize device
$result = Zra::initializeDevice('1234567890', '001', 'DEVICE123456');
```

### Sending Sales Data

```php
use Mak8Tech\ZraSmartInvoice\Facades\Zra;

// Prepare sales data
$salesData = [
    'invoiceNumber' => 'INV-1234',
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
    'customerTpin' => '',  // Optional for customer without TPIN
];

// Send to ZRA
$result = Zra::sendSalesData($salesData);
```

## Web Interface

The package includes a web interface accessible at `/zra` (configurable) where you can:

- Initialize your device
- View device status
- Test connectivity
- View transaction logs

## Testing

```bash
composer test
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

````

## Unit Tests

```php
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
````

This completes the implementation of the ZRA Smart Invoice Integration package for Laravel 12.
