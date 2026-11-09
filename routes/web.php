<?php

use App\Enums\AccessPermission;
use App\Http\Controllers\Administration\PermissionController;
use App\Http\Controllers\Administration\RoleController;
use App\Http\Controllers\DemoLoginController;
use App\Models\Innovation;
use App\Models\InnovationCategory;
use App\Models\InventoryItem;
use App\Models\ItemCategory;
use App\Models\Technology;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');
Route::inertia('showcase', 'showcase/Index')->name('showcase.index');

if (app()->environment(['local', 'testing'])) {
    Route::post('demo-login/{role}', DemoLoginController::class)
        ->middleware(['guest', 'throttle:10,1'])
        ->name('demo.login');
}

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::inertia('innovations', 'innovations/Index')
        ->middleware('can:viewAny,'.Innovation::class)->name('innovations.index');
    Route::inertia('innovation-categories', 'innovation-categories/Index')
        ->middleware('can:viewAny,'.InnovationCategory::class)->name('innovation-categories.index');
    Route::inertia('inventory/items', 'inventory/items/Index')
        ->middleware('can:viewAny,'.InventoryItem::class)->name('inventory.items.index');
    Route::inertia('inventory/categories', 'inventory/categories/Index')
        ->middleware('can:viewAny,'.ItemCategory::class)->name('inventory.categories.index');
    Route::inertia('inventory/units', 'inventory/units/Index')
        ->middleware('can:viewAny,'.Unit::class)->name('inventory.units.index');
    Route::inertia('technologies', 'technologies/Index')
        ->middleware('can:viewAny,'.Technology::class)->name('technologies.index');
    Route::inertia('administration/users', 'administration/users/Index')
        ->middleware('can:viewAny,'.User::class)->name('administration.users.index');

    Route::middleware('can:'.AccessPermission::RoleManage->value)->group(function () {
        Route::get('administration/roles', [RoleController::class, 'index'])->name('administration.roles.index');
        Route::post('administration/roles', [RoleController::class, 'store'])->name('administration.roles.store');
        Route::put('administration/roles/{role}/permissions', [RoleController::class, 'update'])->name('administration.roles.update');

        Route::get('administration/permissions', [PermissionController::class, 'index'])->name('administration.permissions.index');
        Route::post('administration/permissions', [PermissionController::class, 'store'])->name('administration.permissions.store');
    });
});

require __DIR__.'/settings.php';
