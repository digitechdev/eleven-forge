<?php

use App\Http\Controllers\Innovations\InnovationController;
use App\Http\Controllers\Innovations\InnovationFormulaController;
use App\Http\Controllers\Innovations\InnovationItemController;
use App\Http\Controllers\Innovations\InnovationMediaController;
use App\Http\Controllers\Innovations\InnovationTechnologyController;
use App\Models\Innovation;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('innovations', [InnovationController::class, 'index'])
        ->middleware('can:viewAny,'.Innovation::class)->name('innovations.index');
    Route::get('innovations/create', [InnovationController::class, 'create'])->name('innovations.create');
    Route::post('innovations', [InnovationController::class, 'store'])->name('innovations.store');
    Route::get('innovations/{innovation}', [InnovationController::class, 'show'])->name('innovations.show');
    Route::get('innovations/{innovation}/edit', [InnovationController::class, 'edit'])->name('innovations.edit');
    Route::put('innovations/{innovation}', [InnovationController::class, 'update'])->name('innovations.update');
    Route::delete('innovations/{innovation}', [InnovationController::class, 'destroy'])->name('innovations.destroy');
    Route::patch('innovations/{innovation}/restore', [InnovationController::class, 'restore'])
        ->withTrashed()->name('innovations.restore');

    Route::prefix('innovations/{innovation}')->name('innovations.')->scopeBindings()->group(function () {
        Route::post('formulas', [InnovationFormulaController::class, 'store'])->name('formulas.store');
        Route::put('formulas/{formula}', [InnovationFormulaController::class, 'update'])->name('formulas.update');
        Route::patch('formulas/{formula}/activate', [InnovationFormulaController::class, 'activate'])->name('formulas.activate');

        Route::post('items', [InnovationItemController::class, 'store'])->name('items.store');
        Route::put('items/{innovationItem}', [InnovationItemController::class, 'update'])->name('items.update');
        Route::delete('items/{innovationItem}', [InnovationItemController::class, 'destroy'])->name('items.destroy');

        Route::post('technologies', [InnovationTechnologyController::class, 'store'])->name('technologies.store');
        Route::put('technologies/{innovationTechnology}', [InnovationTechnologyController::class, 'update'])->name('technologies.update');
        Route::delete('technologies/{innovationTechnology}', [InnovationTechnologyController::class, 'destroy'])->name('technologies.destroy');

        Route::post('media', [InnovationMediaController::class, 'store'])->name('media.store');
        Route::put('media/{media}', [InnovationMediaController::class, 'update'])->name('media.update');
        Route::delete('media/{media}', [InnovationMediaController::class, 'destroy'])->name('media.destroy');
    });
});
