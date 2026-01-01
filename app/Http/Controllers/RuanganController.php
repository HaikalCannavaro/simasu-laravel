<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class RuanganController extends Controller
{
    public function index()
    {
        $baseUrl = config('api.base_url');
        $response = Http::withoutVerifying()->timeout(5)->get($baseUrl . '/api/rooms');

        $rooms = collect(
            $response->successful() ? $response->json() : []
        )->map(function ($item) {
            return [
                'id'          => $item['id'] ?? 0,
                'name'        => $item['name'] ?? '',
                'floor'       => $item['floor'] ?? '',
                'description' => $item['facilities'] ?? '-', 
                'capacity'    => $item['capacity'] ?? 0,
                'status'      => 'tersedia' 
            ];
        });

        return view('ruangan.index', ['rooms' => $rooms]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'floor'       => 'required|string',
            'description' => 'required|string', 
            'capacity'    => 'required|integer|min:0',
        ]);

        $baseUrl = config('api.base_url');
        $token = session('api_token');

        $response = Http::withoutVerifying()
            ->withToken($token) 
            ->post($baseUrl . '/api/rooms', [
                'name'       => $validated['name'],
                'floor'      => $validated['floor'],
                'capacity'   => (int) $validated['capacity'],
                'facilities' => $validated['description'] 
            ]);

        if ($response->successful()) {
            return response()->json(['success' => true, 'message' => 'Ruangan berhasil ditambahkan!']);
        }

        return response()->json(['success' => false, 'message' => 'Gagal menambahkan ruangan'], 500);
    }

    public function show($id)
    {
        $baseUrl = config('api.base_url');
        $token = session('api_token');

        $response = Http::withoutVerifying()
            ->withToken($token)
            ->get($baseUrl . '/api/rooms/' . $id);

        if ($response->successful()) {
            $data = $response->json();
            $data['description'] = $data['facilities'] ?? ''; 
            return response()->json($data);
        }

        return response()->json([
            'error' => 'API Error',
            'status_code' => $response->status(), 
            'server_message' => $response->body()
        ], $response->status());
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'floor'       => 'required|string',
            'description' => 'required|string',
            'capacity'    => 'required|integer|min:0',
        ]);

        $baseUrl = config('api.base_url');
        $token = session('api_token');

        $response = Http::withoutVerifying()
            ->withToken($token)
            ->put($baseUrl . '/api/rooms/' . $id, [
                'name'       => $validated['name'],
                'floor'      => $validated['floor'],
                'capacity'   => (int) $validated['capacity'],
                'facilities' => $validated['description']
            ]);

        if ($response->successful()) {
            return response()->json(['success' => true, 'message' => 'Ruangan berhasil diperbarui!']);
        }

        return response()->json(['success' => false, 'message' => 'Gagal memperbarui ruangan'], 500);
    }

    public function destroy($id)
    {
        $baseUrl = config('api.base_url');
        $token = session('api_token');

        $response = Http::withoutVerifying()
            ->withToken($token)
            ->delete($baseUrl . '/api/rooms/' . $id);

        if ($response->successful()) {
            return response()->json(['success' => true, 'message' => 'Ruangan berhasil dihapus!']);
        }

        return response()->json(['success' => false, 'message' => 'Gagal menghapus ruangan'], 500);
    }

    public function book($id)
    {
        $baseUrl = config('api.base_url');
        $token = session('api_token'); 

        $response = Http::withoutVerifying()
            ->withToken($token) 
            ->get($baseUrl . '/api/rooms/' . $id);

        if ($response->successful()) {
            $room = $response->json();
            return response()->json([
                'success' => true,
                'message' => 'Memproses pemesanan untuk ruangan: ' . ($room['name'] ?? '')
            ]);
        }
        return response()->json(['success' => false, 'message' => 'Ruangan tidak ditemukan'], 404);
    }
}