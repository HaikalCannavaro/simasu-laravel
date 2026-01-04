<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventarisController;
use App\Http\Controllers\RuanganController;
use App\Http\Controllers\KalenderController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfilController;
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

// ===== TAMBAHKAN ROUTE TEST API DI SINI (SEBELUM MIDDLEWARE) =====
Route::get('/test-api', function() {
    $baseUrl = config('api.base_url');
    
    try {
        $http = Http::withoutVerifying()->timeout(10);
        
        // Test bookings endpoint
        $bookingsResponse = $http->get($baseUrl . '/api/bookings');
        
        $result = [
            'api_base_url' => $baseUrl,
            'bookings_endpoint' => $baseUrl . '/api/bookings',
            'status' => $bookingsResponse->status(),
            'successful' => $bookingsResponse->successful(),
            'headers' => $bookingsResponse->headers(),
            'raw_body' => $bookingsResponse->body(),
        ];
        
        // Try to parse JSON
        if ($bookingsResponse->successful()) {
            try {
                $json = $bookingsResponse->json();
                $result['parsed_json'] = $json;
                $result['is_array'] = is_array($json);
                
                // Check if wrapped in data key
                if (isset($json['data'])) {
                    $result['has_data_wrapper'] = true;
                    $result['data_count'] = is_array($json['data']) ? count($json['data']) : 0;
                    $result['first_booking'] = $json['data'][0] ?? null;
                } else {
                    $result['has_data_wrapper'] = false;
                    $result['bookings_count'] = is_array($json) ? count($json) : 0;
                    $result['first_booking'] = $json[0] ?? null;
                }
                
                // Check fields in first booking
                if (isset($result['first_booking']) && is_array($result['first_booking'])) {
                    $result['available_fields'] = array_keys($result['first_booking']);
                }
            } catch (\Exception $e) {
                $result['parse_error'] = $e->getMessage();
            }
        }
        
        return response()->json($result, 200, [], JSON_PRETTY_PRINT);
        
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500, [], JSON_PRETTY_PRINT);
    }
});

Route::get('/test-api/inventory', function() {
    $baseUrl = config('api.base_url');
    
    try {
        $http = Http::withoutVerifying()->timeout(10);
        $response = $http->get($baseUrl . '/api/inventory');
        
        return response()->json([
            'status' => $response->status(),
            'successful' => $response->successful(),
            'data' => $response->json(),
        ], 200, [], JSON_PRETTY_PRINT);
        
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});

Route::get('/test-api/rooms', function() {
    $baseUrl = config('api.base_url');
    
    try {
        $http = Http::withoutVerifying()->timeout(10);
        $response = $http->get($baseUrl . '/api/rooms');
        
        return response()->json([
            'status' => $response->status(),
            'successful' => $response->successful(),
            'data' => $response->json(),
        ], 200, [], JSON_PRETTY_PRINT);
        
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});
// ===== AKHIR ROUTE TEST API =====

Route::middleware([CekLoginApi::class])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Announcements CRUD
    Route::get('/dashboard/announcements/create', [DashboardController::class, 'createAnnouncement'])->name('announcements.create');
    Route::post('/dashboard/announcements', [DashboardController::class, 'storeAnnouncement'])->name('announcements.store');
    Route::get('/dashboard/announcements/{id}/edit', [DashboardController::class, 'editAnnouncement'])->name('announcements.edit');
    Route::put('/dashboard/announcements/{id}', [DashboardController::class, 'updateAnnouncement'])->name('announcements.update');
    Route::delete('/dashboard/announcements/{id}', [DashboardController::class, 'deleteAnnouncement'])->name('announcements.delete');

    // Events CRUD
    Route::get('/dashboard/events/create', [DashboardController::class, 'createEvent'])->name('events.create');
    Route::post('/dashboard/events', [DashboardController::class, 'storeEvent'])->name('events.store');
    Route::get('/dashboard/events/{id}/edit', [DashboardController::class, 'editEvent'])->name('events.edit');
    Route::put('/dashboard/events/{id}', [DashboardController::class, 'updateEvent'])->name('events.update');
    Route::delete('/dashboard/events/{id}', [DashboardController::class, 'deleteEvent'])->name('events.delete');

    // Inventaris
    Route::get('/inventaris', [InventarisController::class, 'index'])->name('inventaris');
    Route::post('/inventaris', [InventarisController::class, 'store'])->name('inventaris.store');
    Route::put('/inventaris/{id}', [InventarisController::class, 'update'])->name('inventaris.update');
    Route::delete('/inventaris/{id}', [InventarisController::class, 'destroy'])->name('inventaris.destroy');

    // Ruangan
    Route::get('/ruangan', [RuanganController::class, 'index'])->name('ruangan');
    Route::post('/ruangan', [RuanganController::class, 'store'])->name('ruangan.store');
    Route::get('/ruangan/{id}', [RuanganController::class, 'show'])->name('ruangan.show'); 
    Route::put('/ruangan/{id}', [RuanganController::class, 'update'])->name('ruangan.update');
    Route::delete('/ruangan/{id}', [RuanganController::class, 'destroy'])->name('ruangan.destroy');
    Route::get('/ruangan/{id}/book', [RuanganController::class, 'book'])->name('ruangan.book');

    // Kalender
    Route::get('/kalender', [KalenderController::class, 'index'])->name('kalender');
    Route::post('/kalender', [KalenderController::class, 'store'])->name('kalender.store');
    Route::put('/kalender/{id}/status', [KalenderController::class, 'updateStatus'])->name('kalender.update-status');
    Route::delete('/kalender/{id}', [KalenderController::class, 'destroy'])->name('kalender.destroy');
    Route::get('/kalender/{id}', [KalenderController::class, 'show'])->name('kalender.show');

    // Profil
    Route::get('/profil', [ProfilController::class, 'index'])->name('profil');
    Route::put('/profil/update', [ProfilController::class, 'update'])->name('profil.update');
    Route::put('/profil/password', [ProfilController::class, 'updatePassword'])->name('profil.password');
    Route::post('/profil/photo', [ProfilController::class, 'updatePhoto'])->name('profil.photo');
});