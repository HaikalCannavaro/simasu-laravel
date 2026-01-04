<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /*HELPER*/
    private function http()
    {
        return Http::withoutVerifying()
            ->timeout(10)
            ->withToken(session('api_token'));
    }

    private function baseUrl()
    {
        return config('api.base_url');
    }

    /* DASHBOARD */
    public function index()
    {
        $baseUrl = $this->baseUrl();
        $http = Http::withoutVerifying()->timeout(10);

        /*ANNOUNCEMENTS*/
        $berita = collect(
            $http->get($baseUrl . '/api/announcements')->json() ?? []
        )->map(fn ($item) => (object) [
            'id'       => $item['id'] ?? null,
            'title'    => $item['title'] ?? '',
            'subtitle' => $item['subtitle'] ?? '',
            'tag'      => $item['tag'] ?? 'Informasi',
        ]);

        /* EVENTS*/
        $agenda = collect(
            $http->get($baseUrl . '/api/events')->json() ?? []
        )->map(fn ($item) => (object) [
            'id'       => $item['id'] ?? null,
            'title'    => $item['title'] ?? '',
            'subtitle' => $item['subtitle'] ?? '',
            'datetime' => $item['event_date'] ?? null,
            'location' => $item['location'] ?? '',
        ])
        ->filter(fn ($item) => $item->datetime)
        ->sortBy('datetime')
        ->values();

        /* COUNTS*/
        $totalInventaris = count(
            $http->get($baseUrl . '/api/inventory')->json() ?? []
        );

        $ruanganTersedia = count(
            $http->get($baseUrl . '/api/rooms')->json() ?? []
        );

        $anggotaAktif = count(
            $http->get($baseUrl . '/api/profile')->json() ?? []
        );

        //AKTIVITAS TERBARU

        // INVENTARIS
        $inventory = collect(
            $http->get($baseUrl . '/api/inventory')->json() ?? []
        )->map(function ($item) {
            return [
                'type'        => 'barang',
                'title'       => 'Barang baru ditambahkan',
                'description' => ($item['stock'] ?? 0) . ' ' . ($item['name'] ?? ''),
                'sort_key'    => $item['id'] ?? 0, // 👈 pakai ID
            ];
        });

        // RUANGAN
        $rooms = collect(
            $http->get($baseUrl . '/api/rooms')->json() ?? []
        )->map(function ($item) {
            return [
                'type'        => 'ruangan',
                'title'       => 'Ruangan ditambahkan',
                'description' => $item['name'] ?? '',
                'sort_key'    => $item['id'] ?? 0,
            ];
        });

        // GABUNG + SORT
        $activities = $inventory
            ->merge($rooms)
            ->sortByDesc('sort_key')
            ->take(5)
            ->values();


        return view('dashboard.index', compact(
            'totalInventaris',
            'ruanganTersedia',
            'anggotaAktif',
            'berita',
            'agenda',
            'activities'
        ));
    }


    /* ANNOUNCEMENTS*/
    public function storeAnnouncement(Request $request)
    {
        $data = $request->validate([
            'title'    => 'required|string|max:255',
            'subtitle' => 'required|string',
            'tag'      => 'required|string|max:100',
        ]);

        $response = $this->http()->post(
            $this->baseUrl() . '/api/announcements',
            $data
        );

        return $response->successful()
            ? redirect()->route('dashboard')->with('success', 'Pengumuman berhasil ditambahkan')
            : back()->withInput()->with('error', 'Gagal menambahkan pengumuman');
    }

    public function updateAnnouncement(Request $request, $id)
    {
        $data = $request->validate([
            'title'    => 'required|string|max:255',
            'subtitle' => 'required|string',
            'tag'      => 'required|string|max:100',
        ]);

        $response = $this->http()->put(
            $this->baseUrl() . "/api/announcements/{$id}",
            $data
        );

        return $response->successful()
            ? redirect()->route('dashboard')->with('success', 'Pengumuman berhasil diperbarui')
            : back()->withInput()->with('error', 'Gagal memperbarui pengumuman');
    }

    public function deleteAnnouncement($id)
    {
        $response = $this->http()->delete(
            $this->baseUrl() . "/api/announcements/{$id}"
        );

        return in_array($response->status(), [200, 204])
            ? redirect()->route('dashboard')->with('success', 'Pengumuman berhasil dihapus')
            : back()->with('error', 'Gagal menghapus pengumuman');
    }

    /* EVENTS */
    public function storeEvent(Request $request)
    {
        $data = $request->validate([
            'title'    => 'required|string|max:255',
            'subtitle' => 'required|string',
            'datetime' => 'required',
            'location' => 'required|string|max:255',
        ]);

        $payload = [
            'title'    => $data['title'],
            'subtitle' => $data['subtitle'],
            'location' => $data['location'],
            'datetime' => Carbon::parse($data['datetime'])->format('Y-m-d H:i'),
        ];

        $response = $this->http()->post(
            $this->baseUrl() . '/api/events',
            $payload
        );

        return $response->successful()
            ? redirect()->route('dashboard')->with('success', 'Event berhasil ditambahkan')
            : back()->withInput()->with('error', 'Gagal menambahkan event');
    }

    public function updateEvent(Request $request, $id)
    {
        $data = $request->validate([
            'title'    => 'required|string|max:255',
            'subtitle' => 'required|string',
            'datetime' => 'required',
            'location' => 'required|string|max:255',
        ]);

        $payload = [
            'title'    => $data['title'],
            'subtitle' => $data['subtitle'],
            'location' => $data['location'],
            'datetime' => Carbon::parse($data['datetime'])->format('Y-m-d H:i'),
        ];

        $response = $this->http()->put(
            $this->baseUrl() . "/api/events/{$id}",
            $payload
        );

        return $response->successful()
            ? redirect()->route('dashboard')->with('success', 'Event berhasil diperbarui')
            : back()->withInput()->with('error', 'Gagal memperbarui event');
    }

    public function deleteEvent($id)
    {
        $response = $this->http()->delete(
            $this->baseUrl() . "/api/events/{$id}"
        );

        return $response->successful()
            ? redirect()->route('dashboard')->with('success', 'Event berhasil dihapus')
            : back()->with('error', 'Gagal menghapus event');
    }
}
