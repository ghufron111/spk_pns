@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">Data Pegawai</h5>
    <form method="get" class="d-flex gap-2">
        <input type="text" name="q" value="{{ $q }}" class="form-control form-control-sm" placeholder="Cari NIP / Nama">
        <button class="btn btn-sm btn-outline-primary">Cari</button>
        @if($q)
            <a href="{{ route('pimpinan.pegawai.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
        @endif
    </form>
</div>
<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width:40px; text-align:center;">No.</th>
                    <th style="width:140px; text-align:center;">NIP</th>
                    <th style="width:350px; text-align:center;">Nama</th>
                    <th style="width:140px; text-align:center;">Pangkat (Kode)</th>
                    <th style="width:200px; text-align:center;">Pangkat Deskriptif</th>
                    <th style="width:240px; text-align:center;">History Kenaikan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $u)
                    @php
                        $ref = $u->pangkatRef; // relasi pangkat
                        $kode = $ref ? (strtoupper($ref->golongan).strtolower($ref->ruang)) : null;
                        $deskripsi = $ref->nama_pangkat ?? ($kode ?: null);
                        $h = $history[$u->id] ?? null;
                    @endphp
                    <tr class="{{ $ref ? '' : 'table-warning-subtle' }}">
                        <td style="text-align:center;">{{ $loop->iteration + ($users->currentPage()-1)*$users->perPage() }}</td>
                        <td class="text-monospace small fw-semibold">{{ $u->id }}</td>
                        <td>{{ $u->name }}</td>
                        <td>
                            @if($kode)
                                <span class="badge bg-primary-subtle text-primary-emphasis border">{{ $kode }}</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary-emphasis border">-</span>
                            @endif
                        </td>
                        <td>
                            @if($deskripsi)
                                <span>{{ $deskripsi }}</span>
                            @else
                                <span class="text-muted fst-italic">Belum terdata</span>
                            @endif
                        </td>
                        <td class="small">
                            @if($h)
                                <div><strong>{{ $h['count'] }}</strong>x disetujui</div>
                                <div class="text-muted">Terakhir: {{ $h['last_periode'] ?? '-' }} ({{ $h['last_jenis'] ?? '-' }})</div>
                                @if($h['last_target'])
                                    <div>Target: <span class="badge bg-info-subtle text-info-emphasis border">{{ $h['last_target'] }}</span></div>
                                @endif
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">Tidak ada data</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer py-2">
        {{ $users->links() }}
    </div>
</div>
@endsection
