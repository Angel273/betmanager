<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\GoogleController;
use App\Livewire\Dashboard;
use App\Livewire\Bets\BetRegistry;
use App\Livewire\BetPaths\BetPathManager;
use App\Livewire\Admin\AllowedEmailsManager;
use App\Livewire\Admin\CatalogManager;

// Guest Routes
Route::middleware('guest')->group(function () {
    Route::get('/', function () {
        return redirect()->route('login');
    });

    Route::get('/login', function () {
        return view('auth.login');
    })->name('login');

    Route::get('/auth/google', [GoogleController::class, 'redirectToGoogle'])->name('auth.google');
    Route::get('/auth/google/callback', [GoogleController::class, 'handleGoogleCallback'])->name('auth.google.callback');
});

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', Dashboard::class)->name('dashboard');
    Route::get('/bets/register', BetRegistry::class)->name('bets.register');
    Route::get('/bet-paths', BetPathManager::class)->name('bet-paths');
    Route::post('/logout', [GoogleController::class, 'logout'])->name('logout');

    // Admin Routes (Authorization checked in the mount() method of each component)
    Route::get('/admin/allowed-emails', AllowedEmailsManager::class)->name('admin.allowed-emails');
    Route::get('/admin/catalog', CatalogManager::class)->name('admin.catalog');
});

// Temporary Seeding Route (Visit once, then we can delete it)
Route::get('/setup-database-seed', function () {
    try {
        \Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);
        return 'Base de datos sembrada con éxito! Detalle:<br><pre>' . \Illuminate\Support\Facades\Artisan::output() . '</pre>';
    } catch (\Exception $e) {
        return 'Error al sembrar la base de datos: ' . $e->getMessage();
    }
});
