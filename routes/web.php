<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventarisController;
use App\Http\Controllers\RuanganController;
use App\Http\Controllers\KalenderController;
use App\Http\Controllers\AuthController;
use App\Http\Middleware\CekLoginApi;

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.process');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware([CekLoginApi::class])->group(function () {
    Route::get('/', [DashboardController::class, 'index']);
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/inventaris', [InventarisController::class, 'index'])->name('inventaris');
    Route::get('/ruangan', [RuanganController::class, 'index'])->name('ruangan');
    Route::get('/kalender', [KalenderController::class, 'index'])->name('kalender');
    Route::get('/profil', [InventarisController::class, 'index'])->name('profil');
});