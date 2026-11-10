<?php

use App\Http\Controllers\Catalog\InnovationCategoryController;
use App\Http\Controllers\Catalog\ItemCategoryController;
use App\Http\Controllers\Catalog\TechnologyController;
use App\Models\InnovationCategory;
use App\Models\ItemCategory;
use App\Models\Technology;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('innovation-categories', [InnovationCategoryController::class, 'index'])
        ->middleware('can:viewAny,'.InnovationCategory::class)->name('innovation-categories.index');
    Route::post('innovation-categories', [InnovationCategoryController::class, 'store'])->name('innovation-categories.store');
    Route::put('innovation-categories/{innovation_category}', [InnovationCategoryController::class, 'update'])->name('innovation-categories.update');
    Route::delete('innovation-categories/{innovation_category}', [InnovationCategoryController::class, 'destroy'])->name('innovation-categories.destroy');

    Route::get('inventory/categories', [ItemCategoryController::class, 'index'])
        ->middleware('can:viewAny,'.ItemCategory::class)->name('inventory.categories.index');
    Route::post('inventory/categories', [ItemCategoryController::class, 'store'])->name('inventory.categories.store');
    Route::put('inventory/categories/{item_category}', [ItemCategoryController::class, 'update'])->name('inventory.categories.update');
    Route::delete('inventory/categories/{item_category}', [ItemCategoryController::class, 'destroy'])->name('inventory.categories.destroy');

    Route::get('technologies', [TechnologyController::class, 'index'])
        ->middleware('can:viewAny,'.Technology::class)->name('technologies.index');
    Route::post('technologies', [TechnologyController::class, 'store'])->name('technologies.store');
    Route::put('technologies/{technology}', [TechnologyController::class, 'update'])->name('technologies.update');
    Route::delete('technologies/{technology}', [TechnologyController::class, 'destroy'])->name('technologies.destroy');
});
