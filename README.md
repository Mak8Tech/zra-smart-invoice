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
