# Mak8Tech ZRA Smart Invoice Integration

[![Latest Version on Packagist](https://img.shields.io/packagist/v/mak8tech/zra-smart-invoice.svg?style=flat-square&label=Latest%20Version)](https://packagist.org/packages/mak8tech/zra-smart-invoice)
[![Build Status](https://github.com/mak8tech/zra-smart-invoice/workflows/PHP%20Tests/badge.svg?branch=main)](https://github.com/mak8tech/zra-smart-invoice/actions?query=workflow%3A%22PHP+Tests%22+branch%3Amain)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D%208.2-8892BF.svg?style=flat-square)](https://php.net/)
[![Laravel Version](https://img.shields.io/badge/laravel-%3E%3D%2012.0-FF2D20.svg?style=flat-square)](https://laravel.com/)
[![Total Downloads](https://img.shields.io/packagist/dt/mak8tech/zra-smart-invoice.svg?style=flat-square)](https://packagist.org/packages/mak8tech/zra-smart-invoice)
[![License](https://img.shields.io/packagist/l/mak8tech/zra-smart-invoice.svg?style=flat-square)](https://packagist.org/packages/mak8tech/zra-smart-invoice)

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

## Deployment to Packagist

For full instructions on deploying this package to Packagist.org, please see the [Deployment Guide](DEPLOYMENT.md).

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
