<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $baseUrl = config('api.base_url');

        $http = Http::withoutVerifying()->timeout(5);

        //ANNOUNCEMENTS
        $announcementsResponse = $http->get($baseUrl . '/api/announcements');

        $berita = collect(
            $announcementsResponse->successful()
                ? $announcementsResponse->json()
                : []
        )->map(function ($item) {
            return (object) [
                'title'    => $item['title'] ?? '',
                'subtitle' => $item['subtitle'] ?? '',
                'tag'      => $item['tag'] ?? 'Informasi',
            ];
        });

        //EVENTS / AGENDA
        $eventsResponse = $http->get($baseUrl . '/api/events');

        $agenda = collect(
            $eventsResponse->successful()
                ? $eventsResponse->json()
                : []
        )->map(fn ($item) => (object) [
            'title'    => $item['title'] ?? '',
            'subtitle' => $item['subtitle'] ?? '',
            'datetime' => $item['event_date'] ?? null,
            'location' => $item['location'] ?? '',
        ])->filter(function ($item) {
            if (!$item->datetime) {
                return false;
            }

            $eventTime = Carbon::parse($item->datetime);
            $oneHourAgo = now()->subHour();

            // tampilkan jika agenda >= 1 jam yang lalu
            return $eventTime->greaterThanOrEqualTo($oneHourAgo);
        })
        ->sortBy('datetime')
        ->values();

        //INVENTORY COUNT
        $inventoryResponse = $http->get($baseUrl . '/api/inventory');

        $totalInventaris = $inventoryResponse->successful()
            ? count($inventoryResponse->json())
            : 0;

        //ROOMS COUNT
        $roomsResponse = $http->get($baseUrl . '/api/rooms');

        $ruanganTersedia = $roomsResponse->successful()
            ? count($roomsResponse->json())
            : 0;

        return view('dashboard.index', [
            'totalInventaris' => $totalInventaris,
            'ruanganTersedia' => $ruanganTersedia,
            'anggotaAktif'    => 384,
            'berita'          => $berita,
            'agenda'          => $agenda,
        ]);
    }
}
