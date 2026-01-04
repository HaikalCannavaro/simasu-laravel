@extends('layouts.app')

@section('title', 'Kalender Peminjaman & Sewa')

@section('content')
<div class="container-fluid px-4 py-3">
    
    <!-- Debug Information (Remove after testing) -->
    @if(config('app.debug'))
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        <strong>🔍 Debug Mode Active</strong><br>
        <small>
            Bookings by Date: {{ count($bookingsByDate) }} dates<br>
            Monthly Bookings: {{ count($monthlyBookings) }} items<br>
            Current Month: {{ $currentDate->format('F Y') }}<br>
            <details class="mt-2">
                <summary style="cursor: pointer;">View Bookings by Date</summary>
                <div style="font-size: 10px; max-height: 200px; overflow-y: auto; background: #fff; padding: 10px; border-radius: 4px; margin-top: 8px;">
                    @forelse($bookingsByDate as $date => $types)
                        <div style="margin-bottom: 8px; padding: 4px; border-left: 3px solid #4CAF50;">
                            <strong>{{ $date }}:</strong>
                            @if($types['ruangan'])
                                <span style="color: #2196F3;">🏢 Ruangan</span>
                            @endif
                            @if($types['barang'])
                                <span style="color: #EF5350;">📦 Barang</span>
                            @endif
                        </div>
                    @empty
                        <em>No bookings by date</em>
                    @endforelse
                </div>
            </details>
        </small>
    </div>
    @endif
    
    <div class="row mb-4">
        <div class="col-md-8">
            <!-- Header -->
            <div class="mb-4">
                <p class="text-muted small mb-1">Pantau ketersediaan inventaris dan ruangan</p>
                <h4 class="fw-bold mb-0">Kalender Peminjaman & Sewa</h4>
            </div>

            <!-- Calendar Card -->
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <!-- Calendar Header -->
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

                    <!-- Calendar Grid -->
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
                                        $hasRuangan = $hasBooking && ($bookingsByDate[$dateKey]['ruangan'] ?? false);
                                        $hasBarang = $hasBooking && ($bookingsByDate[$dateKey]['barang'] ?? false);
                                    @endphp
                                    
                                    <div class="p-2 h-100 d-flex flex-column position-relative" 
                                         style="cursor: pointer;" 
                                         onclick="openBookingModal({{ $currentDay }}, '{{ $dateKey }}')">
                                        <div class="d-flex justify-content-between align-items-start mb-auto">
                                            <span class="fw-semibold">{{ $currentDay }}</span>
                                        </div>
                                        @if($hasBooking)
                                            <div class="d-flex gap-1 justify-content-center mt-auto">
                                                @if($hasBarang)
                                                    <span class="d-inline-block rounded-circle" 
                                                          style="width: 6px; height: 6px; background-color: #EF5350;" 
                                                          title="Ada peminjaman barang"></span>
                                                @endif
                                                @if($hasRuangan)
                                                    <span class="d-inline-block rounded-circle" 
                                                          style="width: 6px; height: 6px; background-color: #2196F3;" 
                                                          title="Ada sewa ruangan"></span>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                    @php $currentDay++; @endphp
                                @endif
                            </div>

                            @if(($i + 1) % 7 == 0)
                                </div>
                            @endif
                        @endfor
                    </div>

                    <!-- Legend -->
                    <div class="mt-4 pt-3 border-top">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="d-inline-block rounded-circle" style="width: 10px; height: 10px; background-color: #EF5350;"></span>
                                    <small class="text-muted">Ada Peminjaman Barang</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="d-inline-block rounded-circle" style="width: 10px; height: 10px; background-color: #2196F3;"></span>
                                    <small class="text-muted">Ada Sewa Ruangan</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Sidebar - Booking List -->
        <div class="col-md-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="mb-0 fw-semibold">Daftar Peminjaman & Sewa</h6>
                </div>
                <div class="card-body p-0" style="max-height: 600px; overflow-y: auto;">
                    @if(empty($monthlyBookings))
                        <div class="p-4 text-center text-muted">
                            <i class="bi bi-calendar-x fs-1 mb-2 d-block"></i>
                            <p class="mb-0">Belum ada peminjaman bulan ini</p>
                            @if(config('app.debug'))
                                <div class="alert alert-info mt-3 text-start" style="font-size: 0.85rem;">
                                    <strong>🔍 Troubleshooting:</strong>
                                    <ol class="mb-0 mt-2">
                                        <li>Cek endpoint: <code>{{ config('api.base_url') }}/api/bookings</code></li>
                                        <li>Format tanggal API: <code>YYYY-MM-DD HH:mm</code></li>
                                        <li>Field yang dicari: <code>start_time</code>, <code>tanggal_mulai</code>, atau <code>start_date</code></li>
                                        <li>Field type: <code>room</code> atau <code>inventory</code></li>
                                        <li>Lihat log: <code>tail -f storage/logs/laravel.log</code></li>
                                    </ol>
                                </div>
                            @endif
                        </div>
                    @else
                        @foreach($monthlyBookings as $booking)
                            <div class="p-3 border-bottom booking-item">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <h6 class="mb-0 fw-semibold">{{ $booking['nama'] }}</h6>
                                            @if($booking['type'] === 'room')
                                                <span class="d-inline-block rounded-circle" 
                                                      style="width: 8px; height: 8px; background-color: #2196F3;"></span>
                                            @else
                                                <span class="d-inline-block rounded-circle" 
                                                      style="width: 8px; height: 8px; background-color: #EF5350;"></span>
                                            @endif
                                        </div>
                                        <small class="text-muted d-block mb-1">
                                            <i class="bi bi-person"></i> {{ $booking['peminjam'] }}
                                        </small>
                                        <small class="text-muted d-block">
                                            <i class="bi bi-clock"></i> {{ $booking['tanggal_mulai'] }}<br>
                                            <i class="bi bi-clock-fill"></i> {{ $booking['tanggal_selesai'] }}
                                        </small>
                                        @if($booking['type'] === 'inventory' && $booking['quantity'] > 1)
                                            <small class="text-muted d-block mt-1">
                                                <i class="bi bi-box"></i> Jumlah: {{ $booking['quantity'] }}
                                            </small>
                                        @endif
                                        @php
                                            // Calculate duration in days
                                            try {
                                                $start = \Carbon\Carbon::parse($booking['tanggal_mulai']);
                                                $end = \Carbon\Carbon::parse($booking['tanggal_selesai']);
                                                $days = $start->diffInDays($end);
                                                if ($days > 0) {
                                                    echo '<small class="text-muted d-block mt-1"><i class="bi bi-calendar-range"></i> Durasi: ' . ($days + 1) . ' hari</small>';
                                                }
                                            } catch (\Exception $e) {
                                                // Skip if date parsing fails
                                            }
                                        @endphp
                                    </div>
                                    <div class="text-center ms-2">
                                        <div class="bg-light rounded px-3 py-2 mb-2">
                                            <div class="fw-bold text-primary" style="font-size: 1.2rem;">
                                                {{ $booking['tanggal'] }}
                                            </div>
                                        </div>
                                        <span class="badge 
                                            @if($booking['status'] == 'approved') bg-success
                                            @elseif($booking['status'] == 'pending') bg-warning text-dark
                                            @elseif($booking['status'] == 'rejected') bg-danger
                                            @elseif($booking['status'] == 'completed') bg-info
                                            @else bg-secondary
                                            @endif">
                                            @if($booking['status'] == 'approved') Disetujui
                                            @elseif($booking['status'] == 'pending') Menunggu
                                            @elseif($booking['status'] == 'rejected') Ditolak
                                            @elseif($booking['status'] == 'completed') Selesai
                                            @else {{ ucfirst($booking['status']) }}
                                            @endif
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Booking Modal -->
<div class="modal fade" id="bookingModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('kalender.store') }}" method="POST" id="bookingForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Peminjaman/Sewa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Type Selection -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tipe <span class="text-danger">*</span></label>
                        <select class="form-select" name="type" id="bookingType" required onchange="loadItems()">
                            <option value="inventory">Pinjam Barang</option>
                            <option value="room">Sewa Ruangan</option>
                        </select>
                    </div>

                    <!-- Item Selection -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Pilih Item/Ruangan <span class="text-danger">*</span></label>
                        <select class="form-select" name="item_id" id="itemSelect" required onchange="updateItemName()">
                            <option value="">-- Pilih --</option>
                        </select>
                        <input type="hidden" name="item_name" id="itemName">
                    </div>

                    <!-- Quantity (only for inventory) -->
                    <div class="mb-3" id="quantityField" style="display: none;">
                        <label class="form-label fw-semibold">Jumlah</label>
                        <input type="number" class="form-control" name="quantity" id="quantityInput" min="1" value="1">
                        <small class="text-muted" id="stockInfo"></small>
                    </div>

                    <!-- User Name -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Peminjam/Penyewa <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="user_name" placeholder="Contoh: Acara Walimah" required>
                    </div>

                    <!-- Start Date & Time -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tanggal Mulai <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="start_date" id="startDate" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Jam Mulai <span class="text-danger">*</span></label>
                            <input type="time" class="form-control" name="start_time" value="08:00" required>
                        </div>
                    </div>

                    <!-- End Date & Time -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tanggal Selesai <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="end_date" id="endDate" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Jam Selesai <span class="text-danger">*</span></label>
                            <input type="time" class="form-control" name="end_time" value="17:00" required>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Catatan (Opsional)</label>
                        <textarea class="form-control" name="notes" rows="3" placeholder="Tambahkan catatan..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success" id="submitBookingBtn">
                        <i class="bi bi-save"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
.calendar-day {
    transition: background-color 0.2s ease;
}

.calendar-day:hover {
    background-color: #f8f9fa;
}

.booking-item {
    transition: background-color 0.2s ease;
}

.booking-item:hover {
    background-color: #f8f9fa;
}

/* Ensure bullets are visible */
.badge.rounded-circle {
    display: inline-block;
    border: none;
}
</style>
@endpush

@push('scripts')
<script>
// Pass data from Laravel to JavaScript
const inventory = @json($inventory);
const rooms = @json($rooms);

console.log('📦 Inventory data:', inventory);
console.log('🏢 Rooms data:', rooms);
console.log('📅 Bookings by date:', @json($bookingsByDate));
console.log('📋 Monthly bookings:', @json($monthlyBookings));

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
    const stockInfo = document.getElementById('stockInfo');
    
    // Clear previous options
    itemSelect.innerHTML = '<option value="">-- Pilih --</option>';
    
    if (type === 'inventory') {
        quantityField.style.display = 'block';
        
        if (inventory && inventory.length > 0) {
            let hasAvailable = false;
            inventory.forEach(item => {
                const stock = parseInt(item.stock) || 0;
                if (stock > 0) {
                    hasAvailable = true;
                    const option = document.createElement('option');
                    option.value = item.id;
                    option.textContent = `${item.name} (Stok: ${stock})`;
                    option.dataset.name = item.name;
                    option.dataset.stock = stock;
                    itemSelect.appendChild(option);
                }
            });
            
            if (!hasAvailable) {
                const option = document.createElement('option');
                option.value = '';
                option.textContent = 'Tidak ada barang dengan stok tersedia';
                option.disabled = true;
                itemSelect.appendChild(option);
            }
        } else {
            const option = document.createElement('option');
            option.value = '';
            option.textContent = 'Tidak ada barang tersedia';
            option.disabled = true;
            itemSelect.appendChild(option);
        }
    } else {
        quantityField.style.display = 'none';
        stockInfo.textContent = '';
        
        if (rooms && rooms.length > 0) {
            let hasAvailable = false;
            rooms.forEach(room => {
                const isAvailable = room.is_available === true || 
                                  room.is_available === 1 || 
                                  room.is_available === '1' ||
                                  room.status === 'tersedia' || 
                                  room.status === 'available';
                
                if (isAvailable) {
                    hasAvailable = true;
                    const option = document.createElement('option');
                    option.value = room.id;
                    const capacityText = room.capacity ? ` (Kapasitas: ${room.capacity} orang)` : '';
                    option.textContent = `${room.name}${capacityText}`;
                    option.dataset.name = room.name;
                    itemSelect.appendChild(option);
                }
            });
            
            if (!hasAvailable) {
                const option = document.createElement('option');
                option.value = '';
                option.textContent = 'Tidak ada ruangan tersedia';
                option.disabled = true;
                itemSelect.appendChild(option);
            }
        } else {
            const option = document.createElement('option');
            option.value = '';
            option.textContent = 'Tidak ada ruangan tersedia';
            option.disabled = true;
            itemSelect.appendChild(option);
        }
    }
}

function updateItemName() {
    const itemSelect = document.getElementById('itemSelect');
    const selectedOption = itemSelect.options[itemSelect.selectedIndex];
    const itemName = selectedOption.dataset.name || '';
    const stock = selectedOption.dataset.stock;
    
    document.getElementById('itemName').value = itemName;
    
    // Update stock info for inventory items
    if (stock) {
        const stockInfo = document.getElementById('stockInfo');
        const quantityInput = document.getElementById('quantityInput');
        stockInfo.textContent = `Stok tersedia: ${stock}`;
        quantityInput.max = stock;
    }
}

// Handle form submission
document.addEventListener('DOMContentLoaded', function() {
    loadItems();
    
    const bookingForm = document.getElementById('bookingForm');
    if (bookingForm) {
        bookingForm.addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submitBookingBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...';
        });
    }
});

// Show success/error messages with SweetAlert if available
@if(session('success'))
    @if(class_exists('SweetAlert'))
    Swal.fire({
        icon: 'success',
        title: 'Berhasil!',
        text: '{{ session('success') }}',
        timer: 2000,
        showConfirmButton: false
    }).then(() => {
        window.location.reload();
    });
    @else
    alert('{{ session('success') }}');
    window.location.reload();
    @endif
@endif

@if(session('error'))
    @if(class_exists('SweetAlert'))
    Swal.fire({
        icon: 'error',
        title: 'Gagal!',
        text: '{{ session('error') }}',
        showConfirmButton: true
    });
    @else
    alert('{{ session('error') }}');
    @endif
@endif
</script>
@endpush