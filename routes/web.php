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
        
        // New routes for enhanced features
        Route::get('/statistics', [ZraController::class, 'statistics'])->name('zra.statistics');
        Route::get('/health', [ZraController::class, 'checkHealth'])->name('zra.health');
        Route::post('/queue-transaction', [ZraController::class, 'queueTransaction'])->name('zra.queue-transaction');
    });
