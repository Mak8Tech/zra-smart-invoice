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

    /*
    |--------------------------------------------------------------------------
    | Invoice Types Configuration
    |--------------------------------------------------------------------------
    |
    | Define the available invoice types and transaction types for ZRA compliance.
    | These values are used when sending data to the ZRA API.
    |
    */

    // Available invoice types
    'invoice_types' => [
        'NORMAL' => 'Normal Invoice',
        'COPY' => 'Copy of Invoice',
        'TRAINING' => 'Training Invoice',
        'PROFORMA' => 'Proforma Invoice',
    ],

    // Default invoice type
    'default_invoice_type' => env('ZRA_DEFAULT_INVOICE_TYPE', 'NORMAL'),

    // Available transaction types
    'transaction_types' => [
        'SALE' => 'Sale',
        'CREDIT_NOTE' => 'Credit Note',
        'DEBIT_NOTE' => 'Debit Note',
        'ADJUSTMENT' => 'Adjustment',
        'REFUND' => 'Refund',
    ],

    // Default transaction type
    'default_transaction_type' => env('ZRA_DEFAULT_TRANSACTION_TYPE', 'SALE'),
];
