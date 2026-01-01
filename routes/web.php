<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventarisController;
use App\Http\Controllers\RuanganController;
use App\Http\Controllers\KalenderController;
use App\Http\Controllers\AuthController;
use App\Http\Middleware\CekLoginApi;

Route::get('/', function () {
    if (session()->has('api_token')) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.process');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware([CekLoginApi::class])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/inventaris', [InventarisController::class, 'index'])->name('inventaris');
    Route::post('/inventaris', [InventarisController::class, 'store'])->name('inventaris.store');
    Route::put('/inventaris/{id}', [InventarisController::class, 'update'])->name('inventaris.update');
    Route::delete('/inventaris/{id}', [InventarisController::class, 'destroy'])->name('inventaris.destroy');

    Route::get('/ruangan', [RuanganController::class, 'index'])->name('ruangan');
    Route::get('/kalender', [KalenderController::class, 'index'])->name('kalender');
    Route::get('/profil', [InventarisController::class, 'index'])->name('profil');
});