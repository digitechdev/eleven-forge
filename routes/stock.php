<?php

use App\Http\Controllers\Stock\LocationController;
use App\Http\Controllers\Stock\StockLedgerController;
use App\Models\Location;
use App\Models\StockLedgerEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('inventory')->name('inventory.')->group(function () {
    Route::get('locations', [LocationController::class, 'index'])
        ->middleware('can:viewAny,'.Location::class)->name('locations.index');
    Route::post('locations', [LocationController::class, 'store'])->name('locations.store');
    Route::put('locations/{location}', [LocationController::class, 'update'])->name('locations.update');
    Route::delete('locations/{location}', [LocationController::class, 'destroy'])->name('locations.destroy');

    // Stock now lives on the Items list; old links keep their filters, with "status" read as stock status.
    Route::get('stock', fn (Request $request): RedirectResponse => to_route('inventory.items.index', array_filter([
        ...$request->only(['search', 'type', 'category', 'location']),
        'stock' => $request->query('status'),
    ], fn (mixed $value): bool => filled($value))))->name('stock.index');

    Route::get('ledger', [StockLedgerController::class, 'index'])
        ->middleware('can:viewAny,'.StockLedgerEntry::class)->name('ledger.index');
    Route::post('ledger', [StockLedgerController::class, 'store'])->name('ledger.store');
    Route::post('ledger/{entry}/reverse', [StockLedgerController::class, 'reverse'])->name('ledger.reverse');
});
