@extends('layouts.app')

@section('title', 'Sewa Ruangan - SIMASU')

@section('content')
<div class="d-flex">
    @include('partials.sidebar')

    <div class="main-content flex-grow-1 p-4">
        <div class="content-header d-flex justify-content-between align-items-start mb-4">
            <div>
                <h1 class="page-title mb-1">Sewa Ruangan</h1>
                <p class="page-subtitle text-muted">Kelola pemesanan dan penyewaan ruang masjid</p>
            </div>
            <button class="btn btn-success" id="btnTambahRuangan">
                + Tambah Ruangan
            </button>
        </div>
        
        <div class="room-grid" id="roomGrid">
            @foreach($rooms as $room)
            <div class="room-card">
                <div class="room-header">
                    <div>
                        <h3 class="room-title">{{ $room['name'] }}</h3>
                    </div>
                    <span class="room-badge badge-{{ $room['status'] }}">
                        {{ $room['status'] == 'tersedia' ? 'Tersedia' : ($room['status'] == 'disewa' ? 'Disewa' : 'Pemeliharaan') }}
                    </span>
                </div>
                <div class="room-location">
                    <span>{{ $room['floor'] }}</span>
                </div>
                <p class="room-description">{{ $room['description'] }}</p>
                <div class="room-info">
                    <div class="info-item">
                        <span class="info-text">Kapasitas: {{ $room['capacity'] }} orang</span>
                    </div>
                </div>
                <div class="room-actions">
                    <button class="btn btn-outline" onclick="pesanRuangan({{ $room['id'] }})">
                        <span>Pesan</span>
                    </button>
                    <button class="btn btn-secondary" onclick="lihatDetail({{ $room['id'] }})">
                        Detail
                    </button>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// CSRF Token untuk Ajax
const csrfToken = '{{ csrf_token() }}';

function pesanRuangan(roomId) {
    fetch(`/ruangan/${roomId}/book`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Terjadi kesalahan saat memproses pemesanan');
    });
}

function lihatDetail(roomId) {
    fetch(`/ruangan/${roomId}`)
        .then(response => response.json())
        .then(room => {
            if (room) {
                showDetailModal(room);
            }
        })
        .catch(error => console.error('Error:', error));
}

function showDetailModal(room) {
    const statusText = getStatusText(room.status);
    const modalHTML = `
        <div class="modal fade show d-block" id="detailModal" style="background: rgba(0,0,0,0.5);" onclick="closeModal(event)">
            <div class="modal-dialog modal-dialog-centered" onclick="event.stopPropagation()">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Detail Ruangan</h5>
                        <button type="button" class="btn-close" onclick="closeModal()"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <strong>Nama Ruangan:</strong>
                            <p class="mb-0">${room.name}</p>
                        </div>
                        <div class="mb-3">
                            <strong>Lokasi:</strong>
                            <p class="mb-0">${room.floor}</p>
                        </div>
                        <div class="mb-3">
                            <strong>Status:</strong>
                            <span class="room-badge badge-${room.status}">${statusText}</span>
                        </div>
                        <div class="mb-3">
                            <strong>Kapasitas:</strong>
                            <p class="mb-0">${room.capacity} orang</p>
                        </div>
                        <div class="mb-3">
                            <strong>Deskripsi:</strong>
                            <p class="mb-0">${room.description}</p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeModal()">Tutup</button>
                        <button type="button" class="btn btn-success" onclick="pesanRuangan(${room.id}); closeModal();">
                            Pesan Ruangan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    document.body.style.overflow = 'hidden';
}

function showAddRoomModal() {
    const modalHTML = `
        <div class="modal fade show d-block" id="addRoomModal" style="background: rgba(0,0,0,0.5);" onclick="closeAddModal(event)">
            <div class="modal-dialog modal-dialog-centered" onclick="event.stopPropagation()">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Ruangan Baru</h5>
                        <button type="button" class="btn-close" onclick="closeAddModal()"></button>
                    </div>
                    <form id="addRoomForm" onsubmit="handleAddRoom(event)">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Nama Ruangan <span class="text-danger">*</span></label>
                                <input type="text" id="roomName" class="form-control" placeholder="Contoh: Aula Serbaguna" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Lantai <span class="text-danger">*</span></label>
                                <select id="roomFloor" class="form-select" required>
                                    <option value="">Pilih Lantai</option>
                                    <option value="Lantai 1">Lantai 1</option>
                                    <option value="Lantai 2">Lantai 2</option>
                                    <option value="Lantai 3">Lantai 3</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Deskripsi <span class="text-danger">*</span></label>
                                <textarea id="roomDescription" class="form-control" rows="3" placeholder="Deskripsi ruangan..." required></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Kapasitas (orang) <span class="text-danger">*</span></label>
                                <input type="number" id="roomCapacity" class="form-control" min="0" placeholder="0" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                <select id="roomStatus" class="form-select" required>
                                    <option value="">Pilih Status</option>
                                    <option value="tersedia">Tersedia</option>
                                    <option value="disewa">Disewa</option>
                                    <option value="pemeliharaan">Pemeliharaan</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="closeAddModal()">Batal</button>
                            <button type="submit" class="btn btn-success">Simpan Ruangan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    `;
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    document.body.style.overflow = 'hidden';
}

function handleAddRoom(event) {
    event.preventDefault();
    
    const formData = {
        name: document.getElementById('roomName').value,
        floor: document.getElementById('roomFloor').value,
        description: document.getElementById('roomDescription').value,
        capacity: parseInt(document.getElementById('roomCapacity').value),
        status: document.getElementById('roomStatus').value
    };
    
    fetch('/ruangan', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message);
            closeAddModal();
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Terjadi kesalahan saat menambahkan ruangan');
    });
}

function getStatusText(status) {
    const statusMap = {
        'tersedia': 'Tersedia',
        'disewa': 'Disewa',
        'pemeliharaan': 'Pemeliharaan'
    };
    return statusMap[status] || status;
}

function showNotification(message) {
    const notificationHTML = `
        <div class="position-fixed top-0 end-0 p-3" style="z-index: 9999;">
            <div class="toast show align-items-center text-white bg-success border-0" role="alert">
                <div class="d-flex">
                    <div class="toast-body">
                        ✓ ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        </div>
    `;
    document.body.insertAdjacentHTML('beforeend', notificationHTML);
    
    setTimeout(() => {
        const toast = document.querySelector('.toast');
        if (toast) {
            toast.classList.remove('show');
            setTimeout(() => toast.parentElement.remove(), 300);
        }
    }, 3000);
}

function closeModal(event) {
    if (event && event.target.className.indexOf('modal') === -1) return;
    const modal = document.getElementById('detailModal');
    if (modal) {
        modal.remove();
        document.body.style.overflow = 'auto';
    }
}

function closeAddModal(event) {
    if (event && event.target.className.indexOf('modal') === -1) return;
    const modal = document.getElementById('addRoomModal');
    if (modal) {
        modal.remove();
        document.body.style.overflow = 'auto';
    }
}

// Event listener untuk tombol tambah ruangan
document.getElementById('btnTambahRuangan').addEventListener('click', () => {
    showAddRoomModal();
});
</script>
@endpush

@push('styles')
<style>
.main-content {
    background: #f8f9fa;
    min-height: 100vh;
}
.content-header {
    margin-bottom: 2rem;
}
.page-title {
    font-size: 2rem;
    font-weight: 700;
    color: #212529;
    margin-bottom: 0.25rem;
}
.page-subtitle {
    font-size: 0.95rem;
    color: #6c757d;
}
.room-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
}
.room-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: transform 0.2s, box-shadow 0.2s;
}
.room-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 4px 16px rgba(0,0,0,0.12);
}
.room-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 0.75rem;
}
.room-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #212529;
    margin: 0;
}
.room-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 600;
}
.badge-tersedia {
    background: #d1f4e0;
    color: #15803d;
}
.badge-disewa {
    background: #fee2e2;
    color: #dc2626;
}
.badge-pemeliharaan {
    background: #fef3c7;
    color: #d97706;
}
.room-location {
    font-size: 0.875rem;
    color: #6c757d;
    margin-bottom: 0.75rem;
}
.room-description {
    color: #495057;
    font-size: 0.9rem;
    margin-bottom: 1rem;
    line-height: 1.5;
}
.room-info {
    margin-bottom: 1rem;
}
.info-text {
    font-size: 0.875rem;
    color: #6c757d;
}
.room-actions {
    display: flex;
    gap: 0.5rem;
}
.btn-outline {
    flex: 1;
    padding: 0.5rem 1rem;
    border: 2px solid #15803d;
    background: transparent;
    color: #15803d;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.2s;
}
.btn-outline:hover {
    background: #15803d;
    color: white;
}
</style>
@endpush