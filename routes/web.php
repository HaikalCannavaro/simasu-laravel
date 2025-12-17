<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventarisController;

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->name('dashboard');

Route::get('/inventaris', [InventarisController::class, 'index'])
    ->name('inventaris');

Route::get('/ruangan', [DashboardController::class, 'index'])
    ->name('ruangan');

Route::get('/kalender', [InventarisController::class, 'index'])
    ->name('kalender');

Route::get('/profil', [InventarisController::class, 'index'])
    ->name('profil');    

Route::post('/logout', function () {
    return redirect('/dashboard');
})->name('logout');
