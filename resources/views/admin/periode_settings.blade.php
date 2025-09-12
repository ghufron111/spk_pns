@extends('layouts.app')
@section('content')
<h2>Pengaturan Periode Pengisian</h2>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
<form method="POST" action="{{ route('admin.periode.settings.store') }}" class="card p-3 shadow-sm mb-4" style="max-width:520px">
    @csrf
    <div class="mb-3">
        <label class="form-label">Label Periode Aktif</label>
        <input type="text" name="label" class="form-control" value="{{ old('label', $periodeLabel) }}" placeholder="Misal: 2025-S1">
        <div class="form-text">Label ini akan disisipkan saat upload sebagai default (bisa diedit manual pegawai).</div>
    </div>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Tanggal Mulai</label>
            <input type="date" name="mulai" class="form-control" value="{{ old('mulai', $periodeAktifMulai) }}">
        </div>
        <div class="col-md-6">
            <label class="form-label">Tanggal Selesai</label>
            <input type="date" name="selesai" class="form-control" value="{{ old('selesai', $periodeAktifSelesai) }}">
        </div>
    </div>
    <div class="mt-4 d-flex gap-2">
        <button class="btn btn-primary">Simpan Periode</button>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary">Kembali</a>
    </div>
</form>
<p class="text-muted small">Catatan: Validasi pembatasan upload berdasarkan periode sudah diterapkan di form upload pegawai menggunakan data pada tabel batas_waktu (hanya periode aktif yang diperbolehkan).</p>
@endsection
