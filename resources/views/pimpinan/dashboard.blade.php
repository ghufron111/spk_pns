@extends('layouts.app')
@section('content')
<div class="mb-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
    <h2 class="mb-0">Dashboard Pimpinan</h2>
    <a href="{{ route('pimpinan.considerations') }}" class="btn btn-sm btn-outline-primary">Lihat Pengajuan Dipertimbangkan</a>
</div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="row g-3 mb-4">
    <div class="col-md-4 col-sm-6">
        <div class="card shadow-sm h-100 border-0">
            <div class="card-body py-3">
                <div class="text-muted small">Pegawai Terdaftar</div>
                <div class="display-6 fw-semibold">{{ $totalPegawai }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-sm-6">
        <div class="card shadow-sm h-100 border-0">
            <div class="card-body py-3">
                <div class="text-muted small">Upload Aktif (Belum Final)</div>
                <div class="display-6 fw-semibold">{{ $totalUploadAktif }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-sm-6">
        <div class="card shadow-sm h-100 border-0">
            <div class="card-body py-3">
                <div class="text-muted small">Dipertimbangkan</div>
                <div class="display-6 fw-semibold text-warning">{{ $totalDipertimbangkan }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-sm-6">
        <div class="card shadow-sm h-100 border-0">
            <div class="card-body py-3">
                <div class="text-muted small">Disetujui</div>
                <div class="display-6 fw-semibold text-success">{{ $totalDisetujui }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-sm-6">
        <div class="card shadow-sm h-100 border-0">
            <div class="card-body py-3">
                <div class="text-muted small">Ditolak</div>
                <div class="display-6 fw-semibold text-danger">{{ $totalDitolak }}</div>
            </div>
        </div>
    </div>
</div>
<div class="mt-4">
    <h5>Ringkasan</h5>
    <ul class="small mb-0">
        <li>Pengajuan yang masih menunggu keputusan tersedia di halaman "Pengajuan Dipertimbangkan".</li>
        <li>Angka "Upload Aktif" menghitung semua proses yang belum berstatus final (disetujui/ditolak).</li>
        <li>Kenaikan pangkat otomatis diterapkan saat pengajuan disetujui.</li>
    </ul>
</div>
@endsection
