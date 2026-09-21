<?php

use App\AccessPermission;
use App\Http\Controllers\DemoLoginController;
use App\Models\Innovation;
use App\Models\InnovationCategory;
use App\Models\InventoryItem;
use App\Models\ItemCategory;
use App\Models\Technology;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');
Route::livewire('showcase', 'pages::showcase.index')->name('showcase.index');

if (app()->environment(['local', 'testing'])) {
    Route::post('demo-login/{role}', DemoLoginController::class)
        ->middleware(['guest', 'throttle:10,1'])
        ->name('demo.login');
}

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::livewire('innovations', 'pages::innovations.index')
        ->middleware('can:viewAny,'.Innovation::class)->name('innovations.index');
    Route::livewire('innovation-categories', 'pages::innovation-categories.index')
        ->middleware('can:viewAny,'.InnovationCategory::class)->name('innovation-categories.index');
    Route::livewire('inventory/items', 'pages::inventory.items.index')
        ->middleware('can:viewAny,'.InventoryItem::class)->name('inventory.items.index');
    Route::livewire('inventory/categories', 'pages::inventory.categories.index')
        ->middleware('can:viewAny,'.ItemCategory::class)->name('inventory.categories.index');
    Route::livewire('inventory/units', 'pages::inventory.units.index')
        ->middleware('can:viewAny,'.Unit::class)->name('inventory.units.index');
    Route::livewire('technologies', 'pages::technologies.index')
        ->middleware('can:viewAny,'.Technology::class)->name('technologies.index');
    Route::livewire('administration/users', 'pages::administration.users.index')
        ->middleware('can:viewAny,'.User::class)->name('administration.users.index');
    Route::livewire('administration/roles', 'pages::administration.roles.index')
        ->middleware('can:'.AccessPermission::RoleManage->value)->name('administration.roles.index');
    Route::livewire('administration/permissions', 'pages::administration.permissions.index')
        ->middleware('can:'.AccessPermission::RoleManage->value)->name('administration.permissions.index');
});

require __DIR__.'/settings.php';
