@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')

{{-- HEADER --}}
<div class="mb-4">
    <h2 class="fw-bold">Dashboard</h2>
    <p class="text-muted">
        Selamat datang di Sistem Manajemen Inventaris dan Ruangan Masjid
    </p>
</div>

{{-- STAT --}}
<div class="row g-3 mb-4">

    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted">Total Inventaris</small>
                    <h3 class="fw-bold">{{ $totalInventaris }}</h3>
                </div>
                <div class="bg-success-subtle p-3 rounded">
                    <img
                        src="{{ asset('icons/cube-green-1024.png') }}"
                        alt="Inventaris"
                        width="28"
                    >
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted">Ruangan Tersedia</small>
                    <h3 class="fw-bold">{{ $ruanganTersedia }}</h3>
                </div>
                <div class="bg-primary-subtle p-3 rounded">
                    <img
                        src="{{ asset('icons/door-green.png') }}"
                        alt="Ruangan"
                        width="28"
                    >
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted">Anggota Aktif</small>
                    <h3 class="fw-bold">{{ $anggotaAktif }}</h3>
                </div>
                <div class="bg-secondary-subtle p-3 rounded">
                    <img
                        src="{{ asset('icons/user-blue.png') }}"
                        alt="Anggota"
                        width="28"
                    >
                </div>
            </div>
        </div>
    </div>

</div>


{{-- Berita & Aktivitas --}}
<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">
                Berita & Pengumuman
            </div>

            <ul class="list-group list-group-flush">
                @foreach ($berita as $item)
                <li class="list-group-item">
                    <h6 class="fw-semibold">{{ $item->title }}</h6>
                    <p class="text-muted mb-2">
                        {{ $item->subtitle }}
                    </p>
                    <span class="badge bg-primary">
                        {{ $item->tag }}
                    </span>
                </li>
                @endforeach
            </ul>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">
                Aktivitas Terbaru
            </div>

            <div class="card-body">
                <div class="mb-3">
                    <h6 class="fw-semibold mb-0">Barang baru ditambahkan</h6>
                    <small class="text-muted">5 telekung</small>
                </div>

                <div class="mb-3">
                    <h6 class="fw-semibold mb-0">Ruangan disewa</h6>
                    <small class="text-muted">Aula utama</small>
                </div>

                <div>
                    <h6 class="fw-semibold mb-0">Peminjaman selesai</h6>
                    <small class="text-muted">10 kursi</small>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Agenda Mendatang --}}
<div class="card shadow-sm">
    <div class="card-header bg-white fw-semibold">
        Agenda Mendatang
    </div>

    <div class="card-body">
        <div class="row row-cols-1 row-cols-md-3 g-3">
            @forelse ($agenda as $item)
                <div class="col">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="fw-bold">
                                {{ $item->title }}
                            </h6>

                            <p class="text-muted small mb-2">
                                {{ $item->subtitle }}
                            </p>
                        </div>

                        <div class="card-footer bg-white border-0">
                            <span class="badge bg-success">
                                {{ $item->location }}
                            </span>

                            @if ($item->datetime)
                                <small class="text-muted float-end">
                                    {{ \Carbon\Carbon::parse($item->datetime)->translatedFormat('d F Y, H:i') }}
                                </small>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="col">
                    <p class="text-muted">Belum ada agenda mendatang.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>

@endsection
