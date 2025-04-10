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

    /*
    |--------------------------------------------------------------------------
    | Tax Configuration
    |--------------------------------------------------------------------------
    |
    | Define the available tax categories, rates, and settings for handling
    | zero-rated and exempt transactions as per ZRA requirements.
    |
    */

    // Tax categories
    'tax_categories' => [
        'VAT' => [
            'name' => 'Value Added Tax',
            'code' => 'VAT',
            'default_rate' => 16.0, // 16% default VAT rate
            'applies_to' => 'goods_and_services',
        ],
        'TOURISM_LEVY' => [
            'name' => 'Tourism Levy',
            'code' => 'TL',
            'default_rate' => 1.5, // 1.5% tourism levy
            'applies_to' => 'tourism_services',
        ],
        'EXCISE' => [
            'name' => 'Excise Duty',
            'code' => 'EXCISE',
            'default_rate' => 10.0, // Default excise duty
            'applies_to' => 'excise_goods',
        ],
        'ZERO_RATED' => [
            'name' => 'Zero Rated',
            'code' => 'ZR',
            'default_rate' => 0.0,
            'applies_to' => 'zero_rated_goods',
        ],
        'EXEMPT' => [
            'name' => 'Tax Exempt',
            'code' => 'EXEMPT',
            'default_rate' => 0.0,
            'applies_to' => 'exempt_goods',
        ],
    ],

    // Default tax category
    'default_tax_category' => env('ZRA_DEFAULT_TAX_CATEGORY', 'VAT'),

    // Tax exemption categories and codes
    'exemption_categories' => [
        'DIPLOMATIC' => 'Diplomatic Exemption',
        'GOVERNMENT' => 'Government Institution',
        'HEALTHCARE' => 'Healthcare Related',
        'EDUCATION' => 'Educational Institution',
        'NGO' => 'Non-Governmental Organization',
        'OTHER' => 'Other Exemption',
    ],

    // Enable automatic tax calculation
    'auto_calculate_tax' => env('ZRA_AUTO_CALCULATE_TAX', true),

    // Round tax amounts to nearest
    'tax_rounding' => env('ZRA_TAX_ROUNDING', 2), // 2 decimal places

    // Tax item category codes - for specific item categorization
    'tax_item_categories' => [
        'STANDARD' => 'Standard Rated Goods/Services',
        'ZERO_RATED' => 'Zero Rated Goods/Services',
        'EXEMPT' => 'Exempt Goods/Services',
        'SERVICE' => 'Services',
        'FOOD' => 'Food Items',
        'ALCOHOL' => 'Alcoholic Beverages',
        'TOBACCO' => 'Tobacco Products',
        'FUEL' => 'Fuel Products',
        'EXPORT' => 'Export Items',
    ],
];
