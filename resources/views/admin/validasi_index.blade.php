@extends('layouts.app')
@section('content')
<div>
    <h2>Validasi Berkas Pegawai</h2>
    <p class="text-muted">Pilih pegawai untuk memvalidasi detail berkas.</p>
    <div class="row g-3">
        @foreach($uploads as $upload)
        <div class="col-md-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title mb-1">{{ $upload->user->name }}</h5>
                    <small class="text-muted">Upload #{{ $upload->id }} • {{ $upload->tanggal_upload }} • Jenis: {{ $upload->jenis ?? '-' }} • Periode: {{ $upload->periode ?? '-' }}</small>
                    <div class="mt-2 mb-2 small">
                        <span class="badge bg-secondary">Total: {{ $upload->detailUploads->count() }}</span>
                        <span class="badge bg-success">Valid: {{ $upload->detailUploads->where('status','disetujui')->count() }}</span>
                        <span class="badge bg-warning text-dark">Pending: {{ $upload->detailUploads->where('status','pending')->count() }}</span>
                        <span class="badge bg-danger">Ditolak: {{ $upload->detailUploads->whereIn('status', ['ditolak', 'tidak_disetujui'])->count() }}</span>
                    </div>
                    <div class="mt-auto">
                        <a href="{{ route('admin.upload.detail', $upload->id) }}" class="btn btn-primary w-100">Validasi</a>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection
