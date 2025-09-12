@extends('layouts.app')
@section('content')
<div>
    <h2>SPK – Daftar Peserta</h2>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <p class="text-muted mb-2">Peserta: minimal 3 berkas bernilai (di luar KTP & KK) berstatus VALID / DISETUJUI. Bobot total maksimum {{ array_sum($weights ?? config('berkas.weights')) }}.</p>
    <form method="GET" class="row g-2 mb-3">
        <div class="col-auto">
            <select name="jenis" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">-- Semua Jenis --</option>
                @foreach($distinctJenis as $j)
                    <option value="{{ $j }}" @selected($filterJenis===$j)>{{ strtoupper($j) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-auto">
            <select name="periode" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">-- Semua Periode --</option>
                @foreach($distinctPeriode as $p)
                    <option value="{{ $p }}" @selected($filterPeriode===$p)>{{ $p }}</option>
                @endforeach
            </select>
        </div>
        @if($filterJenis || $filterPeriode)
        <div class="col-auto">
            <a href="{{ route('admin.spk.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
        </div>
        @endif
    </form>
    <style>
        /* Batasi lebar kolom catatan agar tidak memanjang memenuhi layar */
        .col-catatan { max-width: 360px; white-space: normal; word-wrap: break-word; word-break: break-word; }
    </style>
    <form method="POST" action="{{ route('admin.spk.run') }}" class="mb-3">
        @csrf
        <button type="submit" class="btn btn-primary" @if(!$uploads->count()) disabled @endif>Jalankan SPK</button>
    <a href="{{ route('admin.spk.settings') }}" class="btn btn-outline-secondary ms-2">Edit Pengaturan</a>
    </form>
    <table class="table table-striped align-middle table-sm">
        <thead>
            <tr>
                <th>Pegawai</th>
                <th>Upload ID</th>
                <th>Jenis</th>
                <th>Periode</th>
                <th>Dokumen Bernilai (Valid/Disetujui)</th>
                <th>Skor (Raw)</th>
                <th>Normalisasi %</th>
                <th>Hasil Terakhir</th>
                <th class="col-catatan">Catatan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($uploads as $u)
                @php 
                    $jenisW = $weightsPerJenis[$u->jenis] ?? $weightsPerJenis['reguler'] ?? [];
                    $totalJenis = array_sum($jenisW) ?: 1;
                    $validDocs = $u->detailUploads->filter(fn($d)=>in_array($d->status,['valid','disetujui']) && array_key_exists($d->nama_berkas,$jenisW));
                    $rawScore = $validDocs->sum(fn($d)=>$jenisW[$d->nama_berkas] ?? 0);
                    $norm = $totalJenis ? round(($rawScore / $totalJenis) * 100,2) : 0;
                    $hasilRow = $hasil->firstWhere('upload_id', $u->id); 
                    $labelHasil = $hasilRow? strtoupper($hasilRow->hasil):'-';
                    $badgeClass = match($hasilRow->hasil ?? '') {
                        'disetujui' => 'bg-success',
                        'dipertimbangkan' => 'bg-warning text-dark',
                        'ditolak' => 'bg-danger',
                        default => 'bg-secondary'
                    };
                @endphp
                <tr>
                    <td>{{ $u->user->name }}</td>
                    <td>#{{ $u->id }}</td>
                    <td><span class="badge bg-info text-dark">{{ strtoupper($u->jenis ?? '-') }}</span></td>
                    <td>{{ $u->periode ?? '-' }}</td>
                    <td>{{ $validDocs->count() }}</td>
                    <td>{{ $rawScore }}</td>
                    <td>{{ $norm }}</td>
                    <td><span class="badge {{ $badgeClass }}">{{ $labelHasil }}</span></td>
                    <td class="col-catatan"><div class="small">{{ $hasilRow? $hasilRow->catatan:'-' }}</div></td>
                </tr>
            @empty
            <tr><td colspan="9" class="text-center text-muted">Belum ada peserta siap.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
