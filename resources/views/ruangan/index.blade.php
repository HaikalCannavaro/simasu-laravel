@extends('layouts.app')

@section('title', 'Sewa Ruangan - SIMASU')

@section('content')
<div class="container-fluid px-4">

    {{-- Header Section --}}
    <div class="d-flex justify-content-between align-items-center my-4">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1">Sewa Ruangan</h1>
            <p class="text-muted mb-0">Kelola pemesanan dan penyewaan ruang masjid</p>
        </div>

        @if(session('user.role') == 'admin')
        {{-- Tombol Tambah dengan Warna Hijau (Sesuai Gambar) --}}
        <button type="button" class="btn btn-success" onclick="openModal('add')">
            <i class="fas fa-plus me-1"></i> + Tambah Ruangan
        </button>
        @endif
    </div>

    {{-- Grid Ruangan --}}
    <div class="row g-4">
        @forelse($rooms as $room)
        <div class="col-xl-4 col-md-6">
            <div class="card h-100 border-0 shadow-sm" style="border-radius: 12px;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h5 class="card-title fw-bold text-dark mb-1">{{ $room['name'] }}</h5>
                            <span class="text-muted small">
                                <i class="fas fa-layer-group me-1"></i> {{ $room['floor'] }}
                            </span>
                        </div>
                        {{-- Badge Status --}}
                        <span class="badge bg-opacity-10 text-success bg-success px-3 py-2 rounded-pill">
                            {{ ucfirst($room['status']) }}
                        </span>
                    </div>

                    <p class="text-muted small mb-3" style="min-height: 40px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                        {{ $room['description'] }}
                    </p>

                    <div class="d-flex align-items-center text-muted small mb-4">
                        <i class="fas fa-users me-2"></i> Kapasitas:
                        <strong class="ms-1">{{ $room['capacity'] }} orang</strong>
                    </div>

                    <div class="d-flex gap-2">
                        @if(session('user.role') == 'admin')
                            {{-- Admin Actions --}}
        
                            {{-- Tombol Edit (Biru) --}}
                            <button class="btn btn-primary flex-fill" onclick='editRoomLocal(@json($room))'>
                                Edit
                            </button>
        
                            {{-- Tombol Delete (Merah) --}}
                            {{-- Tambahkan 'flex-fill' di sini agar ukurannya sama dengan Edit --}}
                            <button class="btn btn-danger flex-fill" onclick="deleteRoom({{ $room['id'] }})">
                                Delete <i class="fas fa-trash"></i>
                            </button>
        
                        @else
                            {{-- User Actions --}}
                            <button class="btn btn-outline-secondary flex-fill" onclick="lihatDetail({{ $room['id'] }})">
                                Detail
                            </button>
                            <button class="btn btn-success flex-fill" onclick="pesanRuangan({{ $room['id'] }})">
                                Pesan
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12 text-center py-5">
            <div class="mb-3">
                <i class="fas fa-building fa-3x text-muted opacity-25"></i>
            </div>
            <h5 class="text-muted">Belum ada ruangan tersedia</h5>
        </div>
        @endforelse
    </div>
</div>

{{-- Modal Form (Menggunakan Bootstrap Modal Standard) --}}
<div class="modal fade" id="roomModal" tabindex="-1" aria-labelledby="modalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title fw-bold" id="modalTitle">Tambah Ruangan Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="roomForm" onsubmit="handleSaveRoom(event)">
                <div class="modal-body">
                    <input type="hidden" id="roomId">

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Nama Ruangan <span class="text-danger">*</span></label>
                        <input type="text" id="name" class="form-control" required placeholder="Contoh: Aula Serbaguna">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Lantai <span class="text-danger">*</span></label>
                        <select id="floor" class="form-select" required>
                            <option value="">Pilih Lantai</option>
                            <option value="Lantai 1">Lantai 1</option>
                            <option value="Lantai 2">Lantai 2</option>
                            <option value="Lantai 3">Lantai 3</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Deskripsi <span class="text-danger">*</span></label>
                        <textarea id="description" class="form-control" rows="3" required placeholder="Deskripsi fasilitas ruangan..."></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Kapasitas (orang) <span class="text-danger">*</span></label>
                        <input type="number" id="capacity" class="form-control" required min="1" placeholder="0">
                    </div>

                    {{-- Note: Status dihilangkan karena Controller tidak menyimpan status (default 'tersedia' di backend) --}}
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success px-4" id="btnSave">Simpan Ruangan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Detail --}}
<div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Detail Ruangan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailContent">
                {{-- Content injected via JS --}}
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const csrfToken = '{{ csrf_token() }}';
    let isEditMode = false;

    let roomModal, detailModal;

    document.addEventListener('DOMContentLoaded', function() {
        roomModal = new bootstrap.Modal(document.getElementById('roomModal'));
        detailModal = new bootstrap.Modal(document.getElementById('detailModal'));
    });

    // 1. Buka Modal Form
    function openModal(mode) {
        const title = document.getElementById('modalTitle');
        const btn = document.getElementById('btnSave');
        const form = document.getElementById('roomForm');

        if (mode === 'add') {
            isEditMode = false;
            title.innerText = 'Tambah Ruangan Baru';
            btn.innerText = 'Simpan Ruangan';
            form.reset();
            document.getElementById('roomId').value = '';
        } else {
            isEditMode = true;
            title.innerText = 'Edit Ruangan';
            btn.innerText = 'Update Ruangan';
        }

        roomModal.show();
    }

    // 2. Simpan / Update Data
    function handleSaveRoom(e) {
        e.preventDefault();

        const id = document.getElementById('roomId').value;
        const btn = document.getElementById('btnSave');

        // Ambil data
        const formData = {
            name: document.getElementById('name').value,
            floor: document.getElementById('floor').value,
            capacity: parseInt(document.getElementById('capacity').value),
            description: document.getElementById('description').value
        };

        let url = isEditMode ? `/ruangan/${id}` : '/ruangan';
        let method = isEditMode ? 'PUT' : 'POST';

        // UI Loading
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';

        fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(formData)
            })
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    roomModal.hide();
                    window.location.reload();
                } else {
                    alert('Gagal: ' + (d.message || 'Periksa kembali inputan anda'));
                }
            })
            .catch(err => {
                console.error(err);
                alert('Terjadi kesalahan sistem');
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerText = isEditMode ? 'Update Ruangan' : 'Simpan Ruangan';
            });
    }

    // 3. Load Data untuk Edit
    function editRoomLocal(room) {
    // 1. Buka Modal dalam mode edit
    openModal('edit');

    // 2. Isi form langsung dari data yang dikirim tombol
    document.getElementById('roomId').value = room.id;
    document.getElementById('name').value = room.name;
    document.getElementById('floor').value = room.floor;
    document.getElementById('capacity').value = room.capacity;
    
    document.getElementById('description').value = room.description || room.facilities || '';
}
    // 4. Hapus Data
    function deleteRoom(id) {
        if (!confirm('Apakah Anda yakin ingin menghapus ruangan ini?')) return;

        fetch(`/ruangan/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            })
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    window.location.reload();
                } else {
                    alert('Gagal menghapus: ' + d.message);
                }
            })
            .catch(e => console.error(e));
    }

    // 5. Lihat Detail (Read Only)
    function lihatDetail(id) {
        fetch(`/ruangan/${id}`).then(r => r.json()).then(room => {
            const content = `
                <h4 class="fw-bold mb-3">${room.name}</h4>
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.7rem">Lantai</small>
                        <span class="fs-5">${room.floor}</span>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.7rem">Kapasitas</small>
                        <span class="fs-5">${room.capacity} Orang</span>
                    </div>
                </div>
                <div class="bg-light p-3 rounded border">
                    <small class="text-muted d-block mb-1 fw-bold">Fasilitas</small>
                    <p class="mb-0 text-secondary">${room.description || room.facilities || '-'}</p>
                </div>
            `;
            document.getElementById('detailContent').innerHTML = content;
            detailModal.show();
        });
    }

    // 6. Pesan Ruangan
    function pesanRuangan(id) {
        if (!confirm('Lanjutkan ke proses pemesanan?')) return;
        fetch(`/ruangan/${id}/book`)
            .then(r => r.json())
            .then(d => alert(d.message))
            .catch(e => console.error(e));
    }
</script>
@endpush