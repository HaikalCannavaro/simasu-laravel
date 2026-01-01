@extends('layouts.app')

@section('title', 'Kalender Peminjaman & Sewa')

@section('content')
<div class="container-fluid px-4 py-3">
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="mb-4">
                <p class="text-muted small mb-1">Pantau ketersediaan inventaris dan ruangan</p>
                <h4 class="fw-bold mb-0">Kalender Peminjaman & Sewa</h4>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="mb-0 fw-semibold">
                            {{ $currentDate->locale('id')->translatedFormat('F Y') }}
                        </h5>
                        <div class="btn-group">
                            <a href="?month={{ $currentDate->copy()->subMonth()->month }}&year={{ $currentDate->copy()->subMonth()->year }}" 
                               class="btn btn-sm btn-outline-secondary">
                                ← Sebelumnya
                            </a>
                            <a href="?month={{ $currentDate->copy()->addMonth()->month }}&year={{ $currentDate->copy()->addMonth()->year }}" 
                               class="btn btn-sm btn-outline-secondary">
                                Berikutnya →
                            </a>
                        </div>
                    </div>

                    <div class="calendar-container">
                        <div class="row g-0 border-bottom bg-light">
                            @foreach(['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'] as $day)
                            <div class="col text-center py-2 small fw-semibold text-muted">{{ $day }}</div>
                            @endforeach
                        </div>

                        @php
                            $firstDay = $currentDate->copy()->startOfMonth();
                            $lastDay = $currentDate->copy()->endOfMonth();
                            $startDayOfWeek = $firstDay->dayOfWeek; // 0=Sunday, 6=Saturday
                            $daysInMonth = $lastDay->day;
                            $currentDay = 1;
                            $totalCells = ceil(($daysInMonth + $startDayOfWeek) / 7) * 7;
                        @endphp

                        @for($i = 0; $i < $totalCells; $i++)
                            @if($i % 7 == 0)
                                <div class="row g-0 border-bottom">
                            @endif

                            <div class="col calendar-day border-end position-relative" style="min-height: 80px;">
                                @if($i >= $startDayOfWeek && $currentDay <= $daysInMonth)
                                    @php
                                        $dateKey = $currentDate->copy()->day($currentDay)->format('Y-m-d');
                                        $hasBooking = isset($bookingsByDate[$dateKey]);
                                        $hasRuangan = $hasBooking && $bookingsByDate[$dateKey]['ruangan'];
                                        $hasBarang = $hasBooking && $bookingsByDate[$dateKey]['barang'];
                                    @endphp
                                    
                                    <div class="p-2 h-100 d-flex flex-column" 
                                         style="cursor: pointer;" 
                                         onclick="openBookingModal({{ $currentDay }}, '{{ $dateKey }}')">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <span class="fw-semibold">{{ $currentDay }}</span>
                                            @if($hasBooking)
                                                <div class="d-flex gap-1">
                                                    @if($hasBarang)
                                                        <span class="badge bg-success bg-opacity-50" style="width: 8px; height: 8px; padding: 0; border-radius: 50%;"></span>
                                                    @endif
                                                    @if($hasRuangan)
                                                        <span class="badge bg-primary" style="width: 8px; height: 8px; padding: 0; border-radius: 50%;"></span>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    @php $currentDay++; @endphp
                                @endif
                            </div>

                            @if(($i + 1) % 7 == 0)
                                </div>
                            @endif
                        @endfor
                    </div>

                    <div class="mt-4 pt-3 border-top">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="badge bg-success bg-opacity-50" style="width: 12px; height: 12px; padding: 0; border-radius: 50%;"></span>
                                    <small class="text-muted">Ada Peminjaman</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="badge bg-primary" style="width: 12px; height: 12px; padding: 0; border-radius: 50%;"></span>
                                    <small class="text-muted">Ada Sewa Ruang</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="mb-0 fw-semibold">Daftar Peminjaman & Sewa</h6>
                </div>
                <div class="card-body p-0" style="max-height: 600px; overflow-y: auto;">
                    @forelse($monthlyBookings as $booking)
                        <div class="p-3 border-bottom booking-item">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 fw-semibold">{{ $booking['nama'] }}</h6>
                                    <small class="text-muted d-block mb-1">{{ $booking['peminjam'] }}</small>
                                    <small class="text-muted d-block">
                                        Mulai: {{ $booking['tanggal_mulai'] }}<br>
                                        Selesai: {{ $booking['tanggal_selesai'] }}
                                    </small>
                                </div>
                                <div class="text-center ms-2">
                                    <div class="bg-light rounded px-3 py-2">
                                        <div class="fw-bold text-primary" style="font-size: 1.2rem;">
                                            {{ $booking['tanggal'] }}
                                        </div>
                                    </div>
                                    <span class="badge mt-2 
                                        @if($booking['status'] == 'approved') bg-success
                                        @elseif($booking['status'] == 'pending') bg-warning
                                        @elseif($booking['status'] == 'rejected') bg-danger
                                        @else bg-secondary
                                        @endif">
                                        @if($booking['status'] == 'approved') Disetujui
                                        @elseif($booking['status'] == 'pending') Menunggu
                                        @elseif($booking['status'] == 'rejected') Ditolak
                                        @else {{ ucfirst($booking['status']) }}
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-4 text-center text-muted">
                            <i class="bi bi-calendar-x fs-1 mb-2 d-block"></i>
                            <p class="mb-0">Belum ada peminjaman bulan ini</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="bookingModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('kalender.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Peminjaman/Sewa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tipe</label>
                        <select class="form-select" name="type" id="bookingType" required onchange="loadItems()">
                            <option value="inventory">Pinjam Barang</option>
                            <option value="room">Sewa Ruangan</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Pilih Item/Ruangan</label>
                        <select class="form-select" name="item_id" id="itemSelect" required onchange="updateItemName()">
                            <option value="">-- Pilih --</option>
                        </select>
                        <input type="hidden" name="item_name" id="itemName">
                    </div>

                    <div class="mb-3" id="quantityField" style="display: none;">
                        <label class="form-label fw-semibold">Jumlah</label>
                        <input type="number" class="form-control" name="quantity" min="1" value="1">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Peminjam/Penyewa</label>
                        <input type="text" class="form-control" name="user_name" placeholder="Contoh: Acara Walimah" required>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tanggal Mulai</label>
                            <input type="date" class="form-control" name="start_date" id="startDate" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Jam Mulai</label>
                            <input type="time" class="form-control" name="start_time" value="08:00" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tanggal Selesai</label>
                            <input type="date" class="form-control" name="end_date" id="endDate" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Jam Selesai</label>
                            <input type="time" class="form-control" name="end_time" value="17:00" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Catatan (Opsional)</label>
                        <textarea class="form-control" name="notes" rows="3" placeholder="Tambahkan catatan..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
.calendar-day:hover {
    background-color: #f8f9fa;
}

.booking-item:hover {
    background-color: #f8f9fa;
}
</style>
@endpush

@push('scripts')
<script>
const inventory = @json($inventory);
const rooms = @json($rooms);

function openBookingModal(day, dateKey) {
    const modal = new bootstrap.Modal(document.getElementById('bookingModal'));
    document.getElementById('startDate').value = dateKey;
    document.getElementById('endDate').value = dateKey;
    loadItems();
    modal.show();
}

function loadItems() {
    const type = document.getElementById('bookingType').value;
    const itemSelect = document.getElementById('itemSelect');
    const quantityField = document.getElementById('quantityField');
    
    itemSelect.innerHTML = '<option value="">-- Pilih --</option>';
    
    if (type === 'inventory') {
        quantityField.style.display = 'block';
        inventory.forEach(item => {
            if (item.stock > 0 || item.jumlah > 0) {
                const option = document.createElement('option');
                option.value = item.id;
                option.textContent = item.name || item.nama_barang;
                option.dataset.name = item.name || item.nama_barang;
                itemSelect.appendChild(option);
            }
        });
    } else {
        quantityField.style.display = 'none';
        rooms.forEach(room => {
            if (room.status === 'tersedia' || room.is_available) {
                const option = document.createElement('option');
                option.value = room.id;
                option.textContent = room.name;
                option.dataset.name = room.name;
                itemSelect.appendChild(option);
            }
        });
    }
}

function updateItemName() {
    const itemSelect = document.getElementById('itemSelect');
    const selectedOption = itemSelect.options[itemSelect.selectedIndex];
    document.getElementById('itemName').value = selectedOption.dataset.name || '';
}

document.addEventListener('DOMContentLoaded', function() {
    loadItems();
});
</script>
@endpush