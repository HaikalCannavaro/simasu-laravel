<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class KalenderController extends Controller
{
    public function index(Request $request)
    {
        $baseUrl = config('api.base_url');
        $http = Http::withoutVerifying()->timeout(5);

        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);
        
        $currentDate = Carbon::create($year, $month, 1);

        $bookingsResponse = $http->get($baseUrl . '/api/bookings');
        $inventoryResponse = $http->get($baseUrl . '/api/inventory');
        $roomsResponse = $http->get($baseUrl . '/api/rooms');

        $bookings = $bookingsResponse->successful() ? $bookingsResponse->json() : [];
        $inventory = $inventoryResponse->successful() ? $inventoryResponse->json() : [];
        $rooms = $roomsResponse->successful() ? $roomsResponse->json() : [];

        $bookingsByDate = $this->processBookingsByDate($bookings, $currentDate);

        $monthlyBookings = $this->getMonthlyBookings($bookings, $currentDate);

        return view('kalender.index', [
            'currentDate' => $currentDate,
            'bookingsByDate' => $bookingsByDate,
            'monthlyBookings' => $monthlyBookings,
            'inventory' => $inventory,
            'rooms' => $rooms,
        ]);
    }

    private function processBookingsByDate($bookings, $currentDate)
    {
        $bookingsByDate = [];

        foreach ($bookings as $booking) {
            try {
                $startDate = Carbon::parse($booking['start_time'] ?? $booking['tanggal_mulai']);
                
                if ($startDate->month == $currentDate->month && $startDate->year == $currentDate->year) {
                    $dateKey = $startDate->format('Y-m-d');
                    
                    if (!isset($bookingsByDate[$dateKey])) {
                        $bookingsByDate[$dateKey] = [
                            'ruangan' => false,
                            'barang' => false,
                        ];
                    }

                    $type = $booking['type'] ?? 'inventory';
                    if ($type === 'room' || isset($booking['room_id'])) {
                        $bookingsByDate[$dateKey]['ruangan'] = true;
                    } else {
                        $bookingsByDate[$dateKey]['barang'] = true;
                    }
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return $bookingsByDate;
    }

    private function getMonthlyBookings($bookings, $currentDate)
    {
        $monthlyBookings = [];

        foreach ($bookings as $booking) {
            try {
                $startDate = Carbon::parse($booking['start_time'] ?? $booking['tanggal_mulai']);
                
                if ($startDate->month == $currentDate->month && $startDate->year == $currentDate->year) {
                    $monthlyBookings[] = [
                        'id' => $booking['id'] ?? 0,
                        'nama' => $booking['item_name'] ?? $booking['nama_item'] ?? '-',
                        'peminjam' => $booking['user_name'] ?? $booking['nama_peminjam'] ?? '-',
                        'tanggal' => $startDate->format('d'),
                        'tanggal_lengkap' => $startDate->format('d-m-Y'),
                        'tanggal_mulai' => $startDate->format('d-m-Y H:i'),
                        'tanggal_selesai' => isset($booking['end_time']) 
                            ? Carbon::parse($booking['end_time'])->format('d-m-Y H:i') 
                            : '-',
                        'status' => $booking['status'] ?? 'pending',
                        'type' => $booking['type'] ?? 'inventory',
                    ];
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        usort($monthlyBookings, function($a, $b) {
            return strcmp($b['tanggal'], $a['tanggal']);
        });

        return $monthlyBookings;
    }

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

        $startDateTime = $validated['start_date'] . ' ' . $validated['start_time'];
        $endDateTime = $validated['end_date'] . ' ' . $validated['end_time'];

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

        $response = $http->post($baseUrl . '/api/bookings', $bookingData);

        if ($response->successful()) {
            return redirect()->route('kalender')
                ->with('success', 'Peminjaman/Sewa berhasil ditambahkan!');
        }

        return redirect()->route('kalender')
            ->with('error', 'Gagal menambahkan peminjaman. Silakan coba lagi.');
    }

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