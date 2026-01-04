<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class KalenderController extends Controller
{
    /**
     * Display the calendar with bookings
     */
    public function index(Request $request)
    {
        $baseUrl = config('api.base_url');
        $http = Http::withoutVerifying()->timeout(5);

        // Get current month or from request
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);
        
        $currentDate = Carbon::create($year, $month, 1);

        // Fetch bookings from API
        $bookingsResponse = $http->get($baseUrl . '/api/bookings');
        $inventoryResponse = $http->get($baseUrl . '/api/inventory');
        $roomsResponse = $http->get($baseUrl . '/api/rooms');

        $bookings = $bookingsResponse->successful() ? $bookingsResponse->json() : [];
        $inventory = $inventoryResponse->successful() ? $inventoryResponse->json() : [];
        $rooms = $roomsResponse->successful() ? $roomsResponse->json() : [];

        // Debug log
        \Log::info('Raw Bookings Data:', ['bookings' => $bookings]);

        // Normalize inventory data
        $inventory = collect($inventory)->map(function($item) {
            return [
                'id' => $item['id'] ?? 0,
                'name' => $item['name'] ?? $item['nama_barang'] ?? '-',
                'stock' => $item['stock'] ?? $item['jumlah'] ?? 0,
                'category' => $item['category'] ?? $item['kategori'] ?? '-',
            ];
        })->toArray();

        // Normalize rooms data
        $rooms = collect($rooms)->map(function($item) {
            $status = $item['status'] ?? 'tersedia';
            $isAvailable = isset($item['is_available']) 
                ? $item['is_available'] 
                : ($status === 'tersedia' || $status === 'available');
            
            return [
                'id' => $item['id'] ?? 0,
                'name' => $item['name'] ?? $item['nama_ruangan'] ?? '-',
                'status' => $status,
                'is_available' => $isAvailable,
                'capacity' => $item['capacity'] ?? $item['kapasitas'] ?? 0,
            ];
        })->toArray();

        // Process bookings by date
        $bookingsByDate = $this->processBookingsByDate($bookings, $currentDate);

        // Get bookings for current month
        $monthlyBookings = $this->getMonthlyBookings($bookings, $currentDate);

        // Debug log
        \Log::info('Processed Bookings:', [
            'bookingsByDate' => $bookingsByDate,
            'monthlyBookings_count' => count($monthlyBookings)
        ]);

        return view('kalender.index', [
            'currentDate' => $currentDate,
            'bookingsByDate' => $bookingsByDate,
            'monthlyBookings' => $monthlyBookings,
            'inventory' => $inventory,
            'rooms' => $rooms,
        ]);
    }

    /**
     * Process bookings and group by date
     */
    private function processBookingsByDate($bookings, $currentDate)
    {
        $bookingsByDate = [];

        foreach ($bookings as $booking) {
            try {
                // Try multiple date field names
                $startDateStr = $booking['start_time'] 
                    ?? $booking['tanggal_mulai'] 
                    ?? $booking['start_date'] 
                    ?? null;

                if (!$startDateStr) {
                    \Log::warning('No start date found in booking:', $booking);
                    continue;
                }

                $startDate = Carbon::parse($startDateStr);
                
                // Only include bookings in current month
                if ($startDate->month == $currentDate->month && $startDate->year == $currentDate->year) {
                    $dateKey = $startDate->format('Y-m-d');
                    
                    if (!isset($bookingsByDate[$dateKey])) {
                        $bookingsByDate[$dateKey] = [
                            'ruangan' => false,
                            'barang' => false,
                        ];
                    }

                    // Determine booking type
                    $type = $booking['type'] ?? 'inventory';
                    
                    // Check multiple ways to determine if it's a room booking
                    $isRoomBooking = ($type === 'room') || 
                                   isset($booking['room_id']) || 
                                   (isset($booking['item_type']) && $booking['item_type'] === 'room');

                    if ($isRoomBooking) {
                        $bookingsByDate[$dateKey]['ruangan'] = true;
                    } else {
                        $bookingsByDate[$dateKey]['barang'] = true;
                    }

                    \Log::info('Booking processed:', [
                        'date' => $dateKey,
                        'type' => $type,
                        'isRoom' => $isRoomBooking
                    ]);
                }
            } catch (\Exception $e) {
                \Log::error('Error processing booking date:', [
                    'error' => $e->getMessage(),
                    'booking' => $booking
                ]);
                continue;
            }
        }

        return $bookingsByDate;
    }

    /**
     * Get bookings for the current month
     */
    private function getMonthlyBookings($bookings, $currentDate)
    {
        $monthlyBookings = [];

        foreach ($bookings as $booking) {
            try {
                // Try multiple date field names
                $startDateStr = $booking['start_time'] 
                    ?? $booking['tanggal_mulai'] 
                    ?? $booking['start_date'] 
                    ?? null;

                if (!$startDateStr) {
                    continue;
                }

                $startDate = Carbon::parse($startDateStr);
                
                if ($startDate->month == $currentDate->month && $startDate->year == $currentDate->year) {
                    // Parse end date
                    $endDateStr = $booking['end_time'] 
                        ?? $booking['tanggal_selesai'] 
                        ?? $booking['end_date'] 
                        ?? null;
                    
                    $endDate = $endDateStr ? Carbon::parse($endDateStr) : null;

                    // Determine type
                    $type = $booking['type'] ?? 'inventory';
                    $isRoomBooking = ($type === 'room') || 
                                   isset($booking['room_id']) || 
                                   (isset($booking['item_type']) && $booking['item_type'] === 'room');

                    $monthlyBookings[] = [
                        'id' => $booking['id'] ?? 0,
                        'nama' => $booking['item_name'] 
                            ?? $booking['nama_item'] 
                            ?? $booking['name'] 
                            ?? '-',
                        'peminjam' => $booking['user_name'] 
                            ?? $booking['nama_peminjam'] 
                            ?? $booking['borrower'] 
                            ?? '-',
                        'tanggal' => $startDate->format('d'),
                        'tanggal_lengkap' => $startDate->format('d-m-Y'),
                        'tanggal_mulai' => $startDate->format('d-m-Y H:i'),
                        'tanggal_selesai' => $endDate ? $endDate->format('d-m-Y H:i') : '-',
                        'status' => $booking['status'] ?? 'pending',
                        'type' => $isRoomBooking ? 'room' : 'inventory',
                        'quantity' => $booking['quantity'] ?? 1,
                    ];
                }
            } catch (\Exception $e) {
                \Log::error('Error processing monthly booking:', [
                    'error' => $e->getMessage(),
                    'booking' => $booking
                ]);
                continue;
            }
        }

        // Sort by date descending
        usort($monthlyBookings, function($a, $b) {
            return strcmp($b['tanggal'], $a['tanggal']);
        });

        return $monthlyBookings;
    }

    /**
     * Store a new booking
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:room,inventory',
            'item_id' => 'required|integer',
            'item_name' => 'required|string|max:255',
            'user_name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'start_time' => 'required',
            'end_date' => 'required|date',
            'end_time' => 'required',
            'quantity' => 'nullable|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        $baseUrl = config('api.base_url');
        $token = session('api_token');
        $http = Http::withoutVerifying()->timeout(10);

        // Combine date and time
        $startDateTime = $validated['start_date'] . ' ' . $validated['start_time'];
        $endDateTime = $validated['end_date'] . ' ' . $validated['end_time'];

        // Prepare data for API
        $bookingData = [
            'type' => $validated['type'],
            'item_id' => $validated['item_id'],
            'item_name' => $validated['item_name'],
            'user_name' => $validated['user_name'],
            'start_time' => $startDateTime,
            'end_time' => $endDateTime,
            'quantity' => $validated['quantity'] ?? 1,
            'notes' => $validated['notes'] ?? '',
            'status' => 'pending',
        ];

        \Log::info('Creating booking:', $bookingData);

        // Send POST request to API with token
        $response = $token 
            ? $http->withToken($token)->post($baseUrl . '/api/bookings', $bookingData)
            : $http->post($baseUrl . '/api/bookings', $bookingData);

        if ($response->successful()) {
            return redirect()->route('kalender')
                ->with('success', 'Peminjaman/Sewa berhasil ditambahkan!');
        }

        \Log::error('Booking creation failed:', [
            'status' => $response->status(),
            'body' => $response->body()
        ]);

        return redirect()->route('kalender')
            ->with('error', 'Gagal menambahkan peminjaman: ' . ($response->json()['message'] ?? 'Silakan coba lagi'));
    }

    /**
     * Update booking status
     */
    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,approved,rejected,completed'
        ]);

        $baseUrl = config('api.base_url');
        $token = session('api_token');
        $http = Http::withoutVerifying()->timeout(10);

        $response = $token
            ? $http->withToken($token)->put($baseUrl . '/api/bookings/' . $id, ['status' => $validated['status']])
            : $http->put($baseUrl . '/api/bookings/' . $id, ['status' => $validated['status']]);

        if ($response->successful()) {
            return response()->json([
                'success' => true,
                'message' => 'Status berhasil diperbarui!'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Gagal memperbarui status'
        ], 500);
    }

    /**
     * Delete a booking
     */
    public function destroy($id)
    {
        $baseUrl = config('api.base_url');
        $token = session('api_token');
        $http = Http::withoutVerifying()->timeout(10);

        $response = $token
            ? $http->withToken($token)->delete($baseUrl . '/api/bookings/' . $id)
            : $http->delete($baseUrl . '/api/bookings/' . $id);

        if ($response->successful()) {
            return redirect()->route('kalender')
                ->with('success', 'Peminjaman berhasil dihapus!');
        }

        return redirect()->route('kalender')
            ->with('error', 'Gagal menghapus peminjaman.');
    }

    /**
     * Get booking details
     */
    public function show($id)
    {
        $baseUrl = config('api.base_url');
        $http = Http::withoutVerifying()->timeout(5);

        $response = $http->get($baseUrl . '/api/bookings/' . $id);

        if ($response->successful()) {
            return response()->json($response->json());
        }

        return response()->json(['error' => 'Data tidak ditemukan'], 404);
    }
}