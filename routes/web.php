<?php

use Illuminate\Support\Facades\Route;
use Mak8Tech\ZraSmartInvoice\Http\Controllers\ZraController;
use Mak8Tech\ZraSmartInvoice\Http\Controllers\ZraInventoryController;
use Mak8Tech\ZraSmartInvoice\Http\Controllers\ZraTaxController;

Route::prefix(config('zra.routes.prefix', 'zra'))
    ->middleware(config('zra.routes.middleware', ['web', 'auth']))
    ->group(function () {
        Route::get('/', [ZraController::class, 'index'])->name('zra.index');
        Route::post('/initialize', [ZraController::class, 'initialize'])->name('zra.initialize');
        Route::get('/status', [ZraController::class, 'status'])->name('zra.status');
        Route::get('/logs', [ZraController::class, 'logs'])->name('zra.logs');
        Route::post('/test-sales', [ZraController::class, 'testSales'])->name('zra.test-sales');

        // New routes for enhanced features
        Route::get('/statistics', [ZraController::class, 'statistics'])->name('zra.statistics');
        Route::get('/health', [ZraController::class, 'checkHealth'])->name('zra.health');
        Route::post('/queue-transaction', [ZraController::class, 'queueTransaction'])->name('zra.queue-transaction');
        Route::post('/report', [ZraController::class, 'generateReport'])->name('zra.generate-report');

        // API prefix for AJAX requests
        Route::prefix('api')->name('zra.api')->group(function () {
            // Tax API routes
            Route::get('/tax/categories', [ZraTaxController::class, 'categories'])->name('.tax.categories');
            Route::post('/tax/calculate', [ZraTaxController::class, 'calculate'])->name('.tax.calculate');

            // Inventory API routes
            Route::post('/inventory/search', [ZraInventoryController::class, 'search'])->name('.inventory.search');
            Route::get('/inventory/products', [ZraInventoryController::class, 'index'])->name('.inventory.index');
            Route::post('/inventory/products', [ZraInventoryController::class, 'store'])->name('.inventory.store');
            Route::get('/inventory/products/{id}', [ZraInventoryController::class, 'show'])->name('.inventory.show');
            Route::put('/inventory/products/{id}', [ZraInventoryController::class, 'update'])->name('.inventory.update');
            Route::delete('/inventory/products/{id}', [ZraInventoryController::class, 'destroy'])->name('.inventory.destroy');
            Route::post('/inventory/products/{id}/adjust', [ZraInventoryController::class, 'adjustStock'])->name('.inventory.adjust');
            Route::get('/inventory/products/{id}/movements', [ZraInventoryController::class, 'movements'])->name('.inventory.movements');
            Route::get('/inventory/low-stock', [ZraInventoryController::class, 'lowStock'])->name('.inventory.low-stock');
            Route::get('/inventory/reports/{type}', [ZraInventoryController::class, 'generateReport'])->name('.inventory.report');
        });
    });
