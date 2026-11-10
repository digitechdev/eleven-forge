<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DemoLoginController;
use App\Http\Controllers\ShowcaseController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');
Route::get('showcase', [ShowcaseController::class, 'index'])->name('showcase.index');
Route::get('showcase/{innovation:slug}', [ShowcaseController::class, 'show'])->name('showcase.show');

if (app()->environment(['local', 'testing'])) {
    Route::post('demo-login/{role}', DemoLoginController::class)
        ->middleware(['guest', 'throttle:10,1'])
        ->name('demo.login');
}

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
});

require __DIR__.'/innovations.php';
require __DIR__.'/catalog.php';
require __DIR__.'/inventory.php';
require __DIR__.'/administration.php';
require __DIR__.'/settings.php';
