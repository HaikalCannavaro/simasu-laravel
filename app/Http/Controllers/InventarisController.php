<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class InventarisController extends Controller
{
    /**
     * Display a listing of the inventory.
     */
    public function index()
    {
        $baseUrl = config('api.base_url');
        
        $http = Http::withoutVerifying()->timeout(5);
        
        // Get inventory data from API
        $inventoryResponse = $http->get($baseUrl . '/api/inventory');
        
        $inventaris = collect(
            $inventoryResponse->successful()
                ? $inventoryResponse->json()
                : []
        )->map(function ($item) {
            return (object) [
                'id'           => $item['id'] ?? null,
                'nama_barang'  => $item['nama_barang'] ?? $item['name'] ?? '-',
                'kategori'     => $item['kategori'] ?? $item['category'] ?? '-',
                'jumlah'       => $item['jumlah'] ?? $item['quantity'] ?? 0,
                'updated_at'   => isset($item['updated_at']) 
                    ? \Carbon\Carbon::parse($item['updated_at']) 
                    : now(),
            ];
        })->sortByDesc('updated_at')->values();
        
        return view('inventaris.index', compact('inventaris'));
    }

    /**
     * Store a newly created inventory item.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_barang' => 'required|string|max:255',
            'kategori' => 'required|string',
            'jumlah' => 'required|integer|min:0',
        ]);
        
        $baseUrl = config('api.base_url');
        
        $http = Http::withoutVerifying()->timeout(10);
        
        // Send POST request to API
        $response = $http->post($baseUrl . '/api/inventory', [
            'nama_barang' => $validated['nama_barang'],
            'kategori'    => $validated['kategori'],
            'jumlah'      => $validated['jumlah'],
        ]);
        
        if ($response->successful()) {
            return redirect()->route('inventaris')
                ->with('success', 'Barang berhasil ditambahkan!');
        }
        
        return redirect()->route('inventaris')
            ->with('error', 'Gagal menambahkan barang. Silakan coba lagi.');
    }

    /**
     * Update the specified inventory item.
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'nama_barang' => 'required|string|max:255',
            'kategori' => 'required|string',
            'jumlah' => 'required|integer|min:0',
        ]);
        
        $baseUrl = config('api.base_url');
        
        $http = Http::withoutVerifying()->timeout(10);
        
        // Send PUT request to API
        $response = $http->put($baseUrl . '/api/inventory/' . $id, [
            'nama_barang' => $validated['nama_barang'],
            'kategori'    => $validated['kategori'],
            'jumlah'      => $validated['jumlah'],
        ]);
        
        if ($response->successful()) {
            return redirect()->route('inventaris')
                ->with('success', 'Barang berhasil diperbarui!');
        }
        
        return redirect()->route('inventaris')
            ->with('error', 'Gagal memperbarui barang. Silakan coba lagi.');
    }

    /**
     * Remove the specified inventory item.
     */
    public function destroy($id)
    {
        $baseUrl = config('api.base_url');
        
        $http = Http::withoutVerifying()->timeout(10);
        
        // Send DELETE request to API
        $response = $http->delete($baseUrl . '/api/inventory/' . $id);
        
        if ($response->successful()) {
            return redirect()->route('inventaris')
                ->with('success', 'Barang berhasil dihapus!');
        }
        
        return redirect()->route('inventaris')
            ->with('error', 'Gagal menghapus barang. Silakan coba lagi.');
    }
    
    /**
     * Get inventory details by ID (for AJAX/API calls)
     */
    public function show($id)
    {
        $baseUrl = config('api.base_url');
        
        $http = Http::withoutVerifying()->timeout(5);
        
        $response = $http->get($baseUrl . '/api/inventory/' . $id);
        
        if ($response->successful()) {
            return response()->json($response->json());
        }
        
        return response()->json(['error' => 'Data tidak ditemukan'], 404);
    }
}