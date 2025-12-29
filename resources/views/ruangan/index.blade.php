@extends('layouts.app')

@section('title', 'Sewa Ruangan - SIMASU')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/sewa.css') }}">
@endpush

@section('content')
<aside class="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <img src="{{ asset('assets/img/logo.png') }}" alt="Logo icon" class="logo-icon">
            <span class="logo-text">SIMASU</span>
        </div>
    </div>
    <nav class="sidebar-nav">
        <a href="{{ route('dashboard') }}" class="nav-item">
            <img src="{{ asset('assets/img/icons/home.png') }}" alt="dashboard icon" class="nav-icon">
            <span class="nav-text">Dashboard</span>
        </a>
        <a href="{{ route('inventaris.index') }}" class="nav-item">
            <img src="{{ asset('assets/img/icons/cube.png') }}" alt="Inventaris icon" class="nav-icon">
            <span class="nav-text">Inventaris</span>
        </a>
        <a href="{{ route('ruangan.index') }}" class="nav-item active">
            <img src="{{ asset('assets/img/icons/door.png') }}" alt="Ruangan Logo" class="nav-icon">
            <span class="nav-text">Sewa Ruangan</span>
        </a>
        <a href="{{ route('calendar') }}" class="nav-item">
            <img src="{{ asset('assets/img/icons/calendar.png') }}" alt="Calendar Logo" class="nav-icon">
            <span class="nav-text">Kalender</span>
        </a>
        <a href="{{ route('profile') }}" class="nav-item">
            <img src="{{ asset('assets/img/icons/user.png') }}" alt="Profil Logo" class="nav-icon">
            <span class="nav-text">Profil</span>
        </a>
    </nav>
    <button class="logout-btn" id="logoutBtn">
        <img src="{{ asset('assets/img/icons/logout.png') }}" alt="logout Logo" width="20" height="20">
        <span>Logout</span>
    </button>
</aside>

<div class="main-content">
    <div class="content-header">
        <div>
            <h1 class="page-title">Sewa Ruangan</h1>
            <p class="page-subtitle">Kelola pemesanan dan penyewaan ruang masjid</p>
        </div>
        <button class="btn-primary" id="btnTambahRuangan">
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
@endsection

@push('scripts')
<script>
// CSRF Token untuk Ajax ####
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
        <div class="modal-overlay" id="detailModal" onclick="closeModal(event)">
            <div class="modal-content" onclick="event.stopPropagation()">
                <div class="modal-header">
                    <h2 class="modal-title">Detail Ruangan</h2>
                    <button class="modal-close" onclick="closeModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="detail-row">
                        <span class="detail-label">Nama Ruangan:</span>
                        <span class="detail-value">${room.name}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Lokasi:</span>
                        <span class="detail-value">${room.floor}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Status:</span>
                        <span class="room-badge badge-${room.status}">${statusText}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Kapasitas:</span>
                        <span class="detail-value">${room.capacity} orang</span>
                    </div>
                    <div class="detail-row full">
                        <span class="detail-label">Deskripsi:</span>
                        <p class="detail-description">${room.description}</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" onclick="closeModal()">Tutup</button>
                    <button class="btn btn-primary" onclick="pesanRuangan(${room.id}); closeModal();">
                        Pesan Ruangan
                    </button>
                </div>
            </div>
        </div>
    `;
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    document.body.style.overflow = 'hidden';
}

function showAddRoomModal() {
    const modalHTML = `
        <div class="modal-overlay" id="addRoomModal" onclick="closeAddModal(event)">
            <div class="modal-content" onclick="event.stopPropagation()">
                <div class="modal-header">
                    <h2 class="modal-title">Tambah Ruangan Baru</h2>
                    <button class="modal-close" onclick="closeAddModal()">&times;</button>
                </div>
                <form id="addRoomForm" onsubmit="handleAddRoom(event)">
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="form-label">Nama Ruangan <span class="required">*</span></label>
                            <input type="text" id="roomName" class="form-input" placeholder="Contoh: Aula Serbaguna" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Lantai <span class="required">*</span></label>
                            <select id="roomFloor" class="form-input" required>
                                <option value="">Pilih Lantai</option>
                                <option value="Lantai 1">Lantai 1</option>
                                <option value="Lantai 2">Lantai 2</option>
                                <option value="Lantai 3">Lantai 3</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Deskripsi <span class="required">*</span></label>
                            <textarea id="roomDescription" class="form-input" rows="3" placeholder="Deskripsi ruangan..." required></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Kapasitas (orang) <span class="required">*</span></label>
                            <input type="number" id="roomCapacity" class="form-input" min="0" placeholder="0" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Status <span class="required">*</span></label>
                            <select id="roomStatus" class="form-input" required>
                                <option value="">Pilih Status</option>
                                <option value="tersedia">Tersedia</option>
                                <option value="disewa">Disewa</option>
                                <option value="pemeliharaan">Pemeliharaan</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeAddModal()">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Ruangan</button>
                    </div>
                </form>
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
        <div class="notification" id="notification">
            <span>✓ ${message}</span>
        </div>
    `;
    document.body.insertAdjacentHTML('beforeend', notificationHTML);
    
    setTimeout(() => {
        const notif = document.getElementById('notification');
        if (notif) {
            notif.classList.add('fade-out');
            setTimeout(() => notif.remove(), 300);
        }
    }, 3000);
}

function closeModal(event) {
    if (event && event.target.className !== 'modal-overlay') return;
    const modal = document.getElementById('detailModal');
    if (modal) {
        modal.remove();
        document.body.style.overflow = 'auto';
    }
}

function closeAddModal(event) {
    if (event && event.target.className !== 'modal-overlay') return;
    const modal = document.getElementById('addRoomModal');
    if (modal) {
        modal.remove();
        document.body.style.overflow = 'auto';
    }
}

// Event listener untuk tombol tambah ruangan ####
document.getElementById('btnTambahRuangan').addEventListener('click', () => {
    showAddRoomModal();
});

// Logout handler ####
const logoutBtn = document.getElementById('logoutBtn');
if (logoutBtn) {
    logoutBtn.addEventListener('click', () => {
        showConfirmPopup('Apakah Anda yakin ingin keluar?', () => {
            showNotification('Logout berhasil!');
            setTimeout(() => {
                window.location.href = '{{ route('logout') }}';
            }, 1000);
        });
    });
}

// Popup konfirmasi logout ####
function showConfirmPopup(message, onConfirm) {
    const overlay = document.createElement('div');
    overlay.className = 'confirm-overlay';

    const box = document.createElement('div');
    box.className = 'confirm-box';
    box.innerHTML = `
        <p class="confirm-message">${message}</p>
        <div class="confirm-buttons">
            <button id="yesBtn" class="confirm-btn confirm-btn-yes">Ya</button>
            <button id="noBtn" class="confirm-btn confirm-btn-no">Batal</button>
        </div>
    `;

    overlay.appendChild(box);
    document.body.appendChild(overlay);

    const fadeOut = () => {
        box.classList.add('fade-out');
        setTimeout(() => overlay.remove(), 300);
    };

    box.querySelector('#yesBtn').addEventListener('click', () => {
        fadeOut();
        onConfirm();
    });
    box.querySelector('#noBtn').addEventListener('click', fadeOut);
}
</script>
@endpush