@extends('layouts.app')

@section('content')
<div class="container-fluid px-4">
    <h2 class="mt-4">Daftar Permintaan</h2>
    <p class="text-muted">Kelola permintaan peminjaman ruangan dan inventaris.</p>

    {{-- Alert Pesan Sukses/Gagal --}}
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Peminjam</th>
                            <th>Tipe</th>
                            <th>Item</th>
                            <th>Waktu</th>
                            <th>Catatan</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bookings as $booking)
                            <tr>
                                <td>{{ $booking['id'] }}</td>
                                <td>
                                    <div class="fw-bold">{{ $booking['user_name'] }}</div>
                                    <small class="text-muted">Qty: {{ $booking['quantity'] }}</small>
                                </td>
                                <td>
                                    @if($booking['type'] == 'room')
                                        <span class="badge bg-info text-dark">Ruangan</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Barang</span>
                                    @endif
                                </td>
                                <td>{{ $booking['item_name'] }}</td>
                                <td>
                                    <small>
                                        Mulai: {{ \Carbon\Carbon::parse($booking['start_time'])->format('d M Y H:i') }}<br>
                                        Selesai: {{ \Carbon\Carbon::parse($booking['end_time'])->format('d M Y H:i') }}
                                    </small>
                                </td>
                                <td><small>{{ Str::limit($booking['notes'], 50) }}</small></td>
                                <td>
                                    @if($booking['status'] == 'pending')
                                        <span class="badge bg-secondary">Pending</span>
                                    @elseif($booking['status'] == 'approved')
                                        <span class="badge bg-success">Disetujui</span>
                                    @elseif($booking['status'] == 'rejected')
                                        <span class="badge bg-danger">Ditolak</span>
                                    @else
                                        <span class="badge bg-dark">{{ $booking['status'] }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($booking['status'] == 'pending')
                                        <div class="d-flex gap-2">
                                            <form action="{{ route('permintaan.update', $booking['id']) }}" method="POST" onsubmit="return confirm('Setujui permintaan ini?');">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="status" value="approved">
                                                <button type="submit" class="btn btn-sm btn-success" title="Setujui">
                                                    <i class="bi bi-check-lg"></i> Setuju
                                                </button>
                                            </form>

                                            <form action="{{ route('permintaan.update', $booking['id']) }}" method="POST" onsubmit="return confirm('Tolak permintaan ini?');">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="status" value="rejected">
                                                <button type="submit" class="btn btn-sm btn-danger" title="Tolak">
                                                    <i class="bi bi-x-lg"></i> Tolak
                                                </button>
                                            </form>
                                        </div>
                                    @else
                                        <span class="text-muted small">Selesai</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4">Tidak ada data permintaan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection