<?php

use App\Http\Controllers\Inventory\InventoryItemController;
use App\Http\Controllers\Inventory\UnitController;
use App\Models\InventoryItem;
use App\Models\Unit;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('inventory')->name('inventory.')->group(function () {
    Route::get('items', [InventoryItemController::class, 'index'])
        ->middleware('can:viewAny,'.InventoryItem::class)->name('items.index');
    Route::post('items', [InventoryItemController::class, 'store'])->name('items.store');
    Route::get('items/{item}', [InventoryItemController::class, 'show'])->name('items.show');
    Route::put('items/{item}', [InventoryItemController::class, 'update'])->name('items.update');
    Route::delete('items/{item}', [InventoryItemController::class, 'destroy'])->name('items.destroy');

    Route::get('units', [UnitController::class, 'index'])
        ->middleware('can:viewAny,'.Unit::class)->name('units.index');
    Route::post('units', [UnitController::class, 'store'])->name('units.store');
    Route::put('units/{unit}', [UnitController::class, 'update'])->name('units.update');
    Route::delete('units/{unit}', [UnitController::class, 'destroy'])->name('units.destroy');
});
