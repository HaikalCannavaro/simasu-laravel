<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class RuanganController extends Controller
{
    /**
     * Display a listing of the rooms.
     */
    public function index()
    {
        $baseUrl = config('api.base_url');
        $http = Http::withoutVerifying()->timeout(5);

        // Get rooms from API
        $roomsResponse = $http->get($baseUrl . '/api/rooms');

        $rooms = collect(
            $roomsResponse->successful()
                ? $roomsResponse->json()
                : $this->getDefaultRooms()
        )->map(function ($item) {
            return [
                'id' => $item['id'] ?? 0,
                'name' => $item['name'] ?? '',
                'floor' => $item['floor'] ?? '',
                'description' => $item['description'] ?? '',
                'capacity' => $item['capacity'] ?? 0,
                'status' => $item['status'] ?? 'tersedia'
            ];
        });

        return view('ruangan.index', [
            'rooms' => $rooms
        ]);
    }

    /**
     * Get default rooms data (fallback)
     */
    private function getDefaultRooms()
    {
        return [
            [
                'id' => 1,
                'name' => 'Aula Utama',
                'floor' => 'Lantai 1',
                'description' => 'Ruang utama untuk acara besar dan pengajian',
                'capacity' => 200,
                'status' => 'tersedia'
            ],
            [
                'id' => 2,
                'name' => 'Ruang Pertemuan',
                'floor' => 'Lantai 2',
                'description' => 'Ruang pertemuan dengan AC dan proyektor',
                'capacity' => 50,
                'status' => 'disewa'
            ],
            [
                'id' => 3,
                'name' => 'Kamar Tidur Jamaah',
                'floor' => 'Lantai 2',
                'description' => 'Kamar tidur untuk jamaah yang menginap',
                'capacity' => 20,
                'status' => 'tersedia'
            ],
            [
                'id' => 4,
                'name' => 'Dapur Masjid',
                'floor' => 'Lantai 1',
                'description' => 'Dapur lengkap dengan peralatan memasak',
                'capacity' => 0,
                'status' => 'pemeliharaan'
            ],
            [
                'id' => 5,
                'name' => 'Ruang Anak-anak',
                'floor' => 'Lantai 1',
                'description' => 'Ruang bermain dan belajar untuk anak-anak',
                'capacity' => 30,
                'status' => 'tersedia'
            ],
            [
                'id' => 6,
                'name' => 'Perpustakaan',
                'floor' => 'Lantai 2',
                'description' => 'Perpustakaan dengan koleksi buku islami',
                'capacity' => 25,
                'status' => 'tersedia'
            ]
        ];
    }

    /**
     * Store a newly created room.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'floor' => 'required|string',
            'description' => 'required|string',
            'capacity' => 'required|integer|min:0',
            'status' => 'required|in:tersedia,disewa,pemeliharaan'
        ]);

        $baseUrl = config('api.base_url');
        $http = Http::withoutVerifying()->timeout(5);

        // Send POST request to API
        $response = $http->post($baseUrl . '/api/rooms', $validated);

        if ($response->successful()) {
            return response()->json([
                'success' => true,
                'message' => 'Ruangan berhasil ditambahkan!',
                'room' => $response->json()
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Gagal menambahkan ruangan'
        ], 500);
    }

    /**
     * Display the specified room.
     */
    public function show($id)
    {
        $baseUrl = config('api.base_url');
        $http = Http::withoutVerifying()->timeout(5);

        // Get room detail from API
        $response = $http->get($baseUrl . '/api/rooms/' . $id);

        if ($response->successful()) {
            return response()->json($response->json());
        }

        return response()->json(['error' => 'Ruangan tidak ditemukan'], 404);
    }

    /**
     * Update the specified room.
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'floor' => 'required|string',
            'description' => 'required|string',
            'capacity' => 'required|integer|min:0',
            'status' => 'required|in:tersedia,disewa,pemeliharaan'
        ]);

        $baseUrl = config('api.base_url');
        $http = Http::withoutVerifying()->timeout(5);

        // Send PUT request to API
        $response = $http->put($baseUrl . '/api/rooms/' . $id, $validated);

        if ($response->successful()) {
            return response()->json([
                'success' => true,
                'message' => 'Ruangan berhasil diperbarui!',
                'room' => $response->json()
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Gagal memperbarui ruangan'
        ], 500);
    }

    /**
     * Remove the specified room.
     */
    public function destroy($id)
    {
        $baseUrl = config('api.base_url');
        $http = Http::withoutVerifying()->timeout(5);

        // Send DELETE request to API
        $response = $http->delete($baseUrl . '/api/rooms/' . $id);

        if ($response->successful()) {
            return response()->json([
                'success' => true,
                'message' => 'Ruangan berhasil dihapus!'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Gagal menghapus ruangan'
        ], 500);
    }

    /**
     * Book a room
     */
    public function book($id)
    {
        $baseUrl = config('api.base_url');
        $http = Http::withoutVerifying()->timeout(5);

        // Get room detail first
        $roomResponse = $http->get($baseUrl . '/api/rooms/' . $id);

        if ($roomResponse->successful()) {
            $room = $roomResponse->json();
            
            return response()->json([
                'success' => true,
                'message' => 'Memproses pemesanan untuk ruangan: ' . ($room['name'] ?? '')
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Ruangan tidak ditemukan'
        ], 404);
    }
}