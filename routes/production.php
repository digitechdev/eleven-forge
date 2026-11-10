<?php

use App\Http\Controllers\Production\BuildOrderController;
use App\Http\Controllers\Production\ProductionRunController;
use App\Models\BuildOrder;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('production')->name('production.')->group(function () {
    Route::get('build-orders', [BuildOrderController::class, 'index'])
        ->middleware('can:viewAny,'.BuildOrder::class)->name('build-orders.index');
    Route::post('build-orders', [BuildOrderController::class, 'store'])->name('build-orders.store');
    Route::get('build-orders/{buildOrder}', [BuildOrderController::class, 'show'])->name('build-orders.show');
    Route::patch('build-orders/{buildOrder}/plan', [BuildOrderController::class, 'plan'])->name('build-orders.plan');
    Route::patch('build-orders/{buildOrder}/start', [BuildOrderController::class, 'start'])->name('build-orders.start');
    Route::patch('build-orders/{buildOrder}/cancel', [BuildOrderController::class, 'cancel'])->name('build-orders.cancel');
    Route::post('build-orders/{buildOrder}/runs', [ProductionRunController::class, 'store'])->name('build-orders.runs.store');

    Route::redirect('runs', '/production/build-orders')->name('runs.index');
    Route::get('runs/{productionRun}', [ProductionRunController::class, 'show'])->name('runs.show');
});
