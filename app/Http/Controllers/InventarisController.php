<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class InventarisController extends Controller
{
    public function index()
    {
        $baseUrl = config('api.base_url');
        
        // GET Public Endpoint (Tidak butuh token)
        $http = Http::withoutVerifying()->timeout(5);
        $response = $http->get($baseUrl . '/api/inventory');
        
        $inventaris = collect(
            $response->successful() ? $response->json() : []
        )->map(function ($item) {
            return (object) [
                'id'           => $item['id'],
                // Mapping field dari API (name) ke tampilan Laravel
                'nama_barang'  => $item['name'] ?? '-', 
                'kategori'     => $item['category'] ?? '-',
                'jumlah'       => $item['stock'] ?? 0,
                'status'       => $item['status'] ?? 'Tersedia',
                'updated_at'   => isset($item['updatedAt']) ? \Carbon\Carbon::parse($item['updatedAt']) : now(),
            ];
        })->sortByDesc('updated_at')->values();
        
        return view('inventaris.index', compact('inventaris'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_barang' => 'required|string|max:255',
            'kategori'    => 'required|string',
            'jumlah'      => 'required|integer|min:0',
            'deskripsi'   => 'nullable|string' // Tambahan
        ]);
        
        $baseUrl = config('api.base_url');
        $token = session('api_token'); // Ambil token dari session login

        // POST Secure Endpoint (Butuh Token)
        $response = Http::withoutVerifying()->withToken($token)->post($baseUrl . '/api/inventory', [
            'name'        => $validated['nama_barang'],
            'category'    => $validated['kategori'],
            'stock'       => (int) $validated['jumlah'],
            'description' => $request->deskripsi ?? '-' // Mapping ke API
        ]);
        
        if ($response->successful()) {
            return redirect()->route('inventaris')->with('success', 'Barang berhasil ditambahkan!');
        }
        
        return redirect()->route('inventaris')
            ->with('error', 'Gagal menambahkan barang: ' . ($response->json()['message'] ?? 'Server Error'));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'nama_barang' => 'required|string|max:255',
            'jumlah'      => 'required|integer|min:0',
        ]);
        
        $baseUrl = config('api.base_url');
        $token = session('api_token');

        // PUT Secure Endpoint (Butuh Token)
        $response = Http::withoutVerifying()
            ->withToken($token)
            ->put($baseUrl . '/api/inventory/' . $id, [
                // Sesuai swagger inventoryRoutes: hanya name & stock yang di-update di endpoint ini
                'name'  => $validated['nama_barang'],
                'stock' => (int) $validated['jumlah'],
            ]);
        
        if ($response->successful()) {
            return redirect()->route('inventaris')->with('success', 'Barang berhasil diperbarui!');
        }
        
        return redirect()->route('inventaris')
            ->with('error', 'Gagal update: ' . ($response->json()['message'] ?? 'Error'));
    }

    public function destroy($id)
    {
        $baseUrl = config('api.base_url');
        $token = session('api_token');
        
        // DELETE Secure Endpoint (Butuh Token)
        $response = Http::withoutVerifying()
            ->withToken($token)
            ->delete($baseUrl . '/api/inventory/' . $id);
        
        if ($response->successful()) {
            return redirect()->route('inventaris')->with('success', 'Barang berhasil dihapus!');
        }
        
        return redirect()->route('inventaris')->with('error', 'Gagal menghapus barang.');
    }
    
    // Untuk keperluan AJAX jika ada
    public function show($id)
    {
        $baseUrl = config('api.base_url');
        $response = Http::withoutVerifying()->get($baseUrl . '/api/inventory/' . $id);
        
        if ($response->successful()) {
            // Mapping ulang agar frontend JS menerima field yang konsisten
            $data = $response->json();
            $mapped = [
                'id' => $data['id'],
                'nama_barang' => $data['name'],
                'kategori' => $data['category'],
                'jumlah' => $data['stock'],
                'deskripsi' => $data['description']
            ];
            return response()->json($mapped);
        }
        return response()->json(['error' => 'Data tidak ditemukan'], 404);
    }
}