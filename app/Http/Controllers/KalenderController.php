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

        // Log data for debugging
        \Log::info('Bookings API Response:', [
            'count' => count($bookings), 
            'raw_data' => $bookings,
            'api_status' => $bookingsResponse->status()
        ]);
        \Log::info('Current Date:', ['date' => $currentDate->format('Y-m-d')]);

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
            return [
                'id' => $item['id'] ?? 0,
                'name' => $item['name'] ?? $item['nama_ruangan'] ?? '-',
                'status' => $item['status'] ?? 'tersedia',
                'is_available' => isset($item['is_available']) ? $item['is_available'] : ($item['status'] ?? 'tersedia') === 'tersedia',
                'capacity' => $item['capacity'] ?? $item['kapasitas'] ?? 0,
            ];
        })->toArray();

        // Process bookings by date
        $bookingsByDate = $this->processBookingsByDate($bookings, $currentDate);

        // Get bookings for current month
        $monthlyBookings = $this->getMonthlyBookings($bookings, $currentDate);

        \Log::info('Processed Data:', [
            'bookingsByDate' => $bookingsByDate,
            'monthlyBookings' => count($monthlyBookings)
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
                $startTime = $booking['start_time'] ?? 
                            $booking['tanggal_mulai'] ?? 
                            $booking['start_date'] ?? 
                            null;
                
                if (!$startTime) {
                    continue;
                }
                
                $startDate = Carbon::parse($startTime);
                
                // Only include bookings in current month
                if ($startDate->month == $currentDate->month && $startDate->year == $currentDate->year) {
                    $dateKey = $startDate->format('Y-m-d');
                    
                    if (!isset($bookingsByDate[$dateKey])) {
                        $bookingsByDate[$dateKey] = [
                            'ruangan' => false,
                            'barang' => false,
                        ];
                    }

                    // Determine type - check multiple possible field names
                    $type = $booking['type'] ?? $booking['tipe'] ?? 'inventory';
                    
                    // Mark as ruangan if type is 'room' or has room_id
                    if ($type === 'room' || isset($booking['room_id']) || isset($booking['ruangan_id'])) {
                        $bookingsByDate[$dateKey]['ruangan'] = true;
                    } else {
                        $bookingsByDate[$dateKey]['barang'] = true;
                    }
                }
            } catch (\Exception $e) {
                // Log error for debugging
                \Log::error('Error processing booking: ' . $e->getMessage(), ['booking' => $booking]);
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
                $startTime = $booking['start_time'] ?? 
                            $booking['tanggal_mulai'] ?? 
                            $booking['start_date'] ?? 
                            null;
                
                if (!$startTime) {
                    \Log::warning('Booking without start time:', $booking);
                    continue;
                }
                
                $startDate = Carbon::parse($startTime);
                
                \Log::info('Processing booking:', [
                    'start_date' => $startDate->format('Y-m-d'),
                    'current_month' => $currentDate->format('Y-m'),
                    'booking_month' => $startDate->format('Y-m'),
                    'match' => $startDate->month == $currentDate->month && $startDate->year == $currentDate->year
                ]);
                
                if ($startDate->month == $currentDate->month && $startDate->year == $currentDate->year) {
                    // Try multiple field names for end time
                    $endTime = $booking['end_time'] ?? 
                              $booking['tanggal_selesai'] ?? 
                              $booking['end_date'] ?? 
                              $startTime;
                    
                    $monthlyBookings[] = [
                        'id' => $booking['id'] ?? 0,
                        'nama' => $booking['item_name'] ?? 
                                 $booking['nama_item'] ?? 
                                 $booking['name'] ?? '-',
                        'peminjam' => $booking['user_name'] ?? 
                                     $booking['nama_peminjam'] ?? 
                                     $booking['peminjam'] ?? '-',
                        'tanggal' => $startDate->format('d'),
                        'tanggal_lengkap' => $startDate->format('d-m-Y'),
                        'tanggal_mulai' => $startDate->format('d-m-Y H:i'),
                        'tanggal_selesai' => Carbon::parse($endTime)->format('d-m-Y H:i'),
                        'status' => $booking['status'] ?? 'pending',
                        'type' => $booking['type'] ?? 'inventory',
                    ];
                }
            } catch (\Exception $e) {
                \Log::error('Error processing monthly booking: ' . $e->getMessage(), ['booking' => $booking]);
                continue;
            }
        }

        // Sort by date descending
        usort($monthlyBookings, function($a, $b) {
            return strcmp($b['tanggal'], $a['tanggal']);
        });

        \Log::info('Total monthly bookings found:', ['count' => count($monthlyBookings)]);

        return $monthlyBookings;
    }

    /**
     * Get dummy bookings for testing
     */
    private function getDummyBookings()
    {
        $now = now();
        return [
            [
                'id' => 1,
                'type' => 'room',
                'item_name' => 'Aula Utama',
                'user_name' => 'Acara Walimah - Keluarga Budi',
                'start_time' => $now->copy()->day(10)->format('Y-m-d 08:00'),
                'end_time' => $now->copy()->day(10)->format('Y-m-d 17:00'),
                'status' => 'approved',
            ],
            [
                'id' => 2,
                'type' => 'inventory',
                'item_name' => 'Kursi Plastik',
                'user_name' => 'Pengajian Rutin',
                'start_time' => $now->copy()->day(15)->format('Y-m-d 14:00'),
                'end_time' => $now->copy()->day(15)->format('Y-m-d 18:00'),
                'status' => 'pending',
                'quantity' => 50,
            ],
            [
                'id' => 3,
                'type' => 'room',
                'item_name' => 'Ruang Pertemuan',
                'user_name' => 'Rapat Takmir',
                'start_time' => $now->copy()->day(20)->format('Y-m-d 19:00'),
                'end_time' => $now->copy()->day(20)->format('Y-m-d 21:00'),
                'status' => 'approved',
            ],
        ];
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
        $http = Http::withoutVerifying()->timeout(10);

        // Combine date and time
        $startDateTime = Carbon::parse($validated['start_date'] . ' ' . $validated['start_time'])->format('Y-m-d H:i');
        $endDateTime = Carbon::parse($validated['end_date'] . ' ' . $validated['end_time'])->format('Y-m-d H:i');

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

        // Send POST request to API
        $response = $http->post($baseUrl . '/api/bookings', $bookingData);

        if ($response->successful()) {
            // Get the month and year from start date
            $startDate = Carbon::parse($validated['start_date']);
            
            return redirect()->route('kalender', [
                'month' => $startDate->month,
                'year' => $startDate->year
            ])->with('success', 'Peminjaman/Sewa berhasil ditambahkan!');
        }

        // Get error message from API if available
        $errorMessage = 'Gagal menambahkan peminjaman. Silakan coba lagi.';
        try {
            $responseData = $response->json();
            if (isset($responseData['message'])) {
                $errorMessage = $responseData['message'];
            }
        } catch (\Exception $e) {
            // Use default error message
        }

        return redirect()->route('kalender')
            ->with('error', $errorMessage);
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
        $http = Http::withoutVerifying()->timeout(10);

        $response = $http->put($baseUrl . '/api/bookings/' . $id, [
            'status' => $validated['status']
        ]);

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
        $http = Http::withoutVerifying()->timeout(10);

        $response = $http->delete($baseUrl . '/api/bookings/' . $id);

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