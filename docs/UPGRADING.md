# Upgrading Guide

This document provides step-by-step instructions for upgrading ZRA Smart Invoice between versions.

## Upgrading from 0.2.x to 0.3.0

### Prerequisites

- PHP 8.0 or higher
- Laravel 9.0 or higher
- Composer 2.0 or higher
- npm 8.0 or higher
- Node.js 16.0 or higher

### Step 1: Update your dependencies

Update your composer.json file to require the new version:

```bash
composer require mak8tech/zra-smart-invoice:^0.3.0
```

### Step 2: Publish the new migrations

The new version includes database optimizations with indexes. Publish and run the new migrations:

```bash
php artisan vendor:publish --tag=zra-migrations
php artisan migrate
```

### Step 3: Update your configuration

Publish the updated configuration to access new features:

```bash
php artisan vendor:publish --tag=zra-config --force
```

Review the updated configuration file and set new options as needed, especially:

- Alert email settings for failure notifications
- Rate limiting options
- Role-based access control settings

### Step 4: Register new middleware (optional)

If you want to use the new rate-limiting and role-based access control middleware, register them in your `app/Http/Kernel.php` file:

```php
protected $routeMiddleware = [
    // ... other middleware
    'zra.role' => \Mak8Tech\ZraSmartInvoice\Http\Middleware\ZraRoleMiddleware::class,
    'zra.ratelimit' => \Mak8Tech\ZraSmartInvoice\Http\Middleware\ZraRateLimitMiddleware::class,
];
```

### Step 5: Republish assets (if using package frontend)

```bash
php artisan vendor:publish --tag=zra-assets --force
```

### Step 6: Run database optimization command (recommended)

```bash
php artisan zra:optimize-db --analyze --optimize
```

### Step 7: Clear caches

```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

## Upgrading from 0.1.x to 0.2.0

### Prerequisites

- PHP 8.0 or higher
- Laravel 9.0 or higher
- Composer 2.0 or higher

### Step 1: Update your dependencies

Update your composer.json file to require the new version:

```bash
composer require mak8tech/zra-smart-invoice:^0.2.0
```

### Step 2: Publish the new frontend assets

The new version includes React/TypeScript frontend components:

```bash
php artisan vendor:publish --tag=zra-assets
```

### Step 3: Install frontend dependencies

```bash
npm install
```

### Step 4: Build frontend assets

```bash
npm run build
```

### Step 5: Update your routes and middleware

Check the documentation for any changes to routes or middleware that may be required.

### Step 6: Clear caches

```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

## Upgrading from 0.0.x to 0.1.0

### Step 1: Install initial package

```bash
composer require mak8tech/zra-smart-invoice:^0.1.0
```

### Step 2: Publish configuration

```bash
php artisan vendor:publish --tag=zra-config
```

### Step 3: Run migrations

```bash
php artisan migrate
```

### Step 4: Configure environment variables

Add the following to your `.env` file:

```php
ZRA_ENVIRONMENT=sandbox
ZRA_BASE_URL=https://api-sandbox.zra.org.zm/
ZRA_TIMEOUT=30
```

### Step 5: Enable the service provider

Ensure the service provider is registered in your `config/app.php`:

```php
'providers' => [
    // ...
    Mak8Tech\ZraSmartInvoice\ZraServiceProvider::class,
],
```

### Step 6: Register the facade (optional)

```php
'aliases' => [
    // ...
    'Zra' => Mak8Tech\ZraSmartInvoice\Facades\Zra::class,
],
```
