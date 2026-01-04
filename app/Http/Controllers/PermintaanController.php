<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PermintaanController extends Controller
{
    private $apiBaseUrl;

    public function __construct()
    {
        $this->apiBaseUrl = config('api.base_url');
    }

    public function index()
    {
        $token = session('api_token'); 

        $response = Http::withToken($token)->get($this->apiBaseUrl . '/api/bookings');

        $bookings = [];
        if ($response->successful()) {
            $data = $response->json();

            $bookings = collect($data)->sortByDesc(function ($item) {
                return $item['status'] === 'pending' ? 1 : 0;
            })->values();
        }

        return view('permintaan.index', compact('bookings'));
    }

    public function updateStatus(Request $request, $id)
    {
        $token = session('api_token');
        
        $request->validate([
            'status' => 'required|in:approved,rejected'
        ]);

        $response = Http::withToken($token)->put($this->apiBaseUrl . '/api/bookings/' . $id . '/status', [
            'status' => $request->status
        ]);

        if ($response->successful()) {
            return redirect()->back()->with('success', 'Status berhasil diubah ke ' . $request->status);
        } else {
            return redirect()->back()->with('error', 'Gagal mengubah status: ' . $response->body());
        }
    }
}