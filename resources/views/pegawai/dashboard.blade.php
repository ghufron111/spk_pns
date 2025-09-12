@extends('layouts.app')
@section('content')
<div>
    <h2>Selamat Datang</h2>
    <p class="text-muted">Dashboard menampilkan notifikasi terbaru. Gunakan menu Upload Berkas untuk menambahkan atau memperbarui dokumen, dan menu Daftar Berkas untuk melihat status lengkap.</p>
    <div class="mb-3">
        <a href="{{ route('pegawai.upload') }}" class="btn btn-primary">Upload Berkas</a>
        <a href="{{ route('pegawai.berkas') }}" class="btn btn-outline-secondary">Lihat Daftar Berkas</a>
    </div>
    <div class="row g-3 mb-4">
        @php $jenisConfig = config('berkas.jenis'); @endphp
    @php
        // Jika currentUpload sudah final (status disetujui / ditolak) jangan tandai sebagai dipilih
        $currentJenis = (isset($currentUpload) && $currentUpload && !in_array($currentUpload->status,['disetujui','ditolak'])) ? $currentUpload->jenis : null;
    @endphp
    @foreach(array_keys($jenisConfig) as $j)
            <div class="col-md-4">
        <div class="card h-100 shadow-sm {{ $currentJenis === $j ? 'border-primary' : '' }}">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title mb-1 text-capitalize">Kenaikan {{ $j }}</h5>
                        <small class="text-muted mb-2">{{ count($jenisConfig[$j]) }} dokumen wajib</small>
            @if($currentJenis === $j)
                            <span class="badge bg-primary mb-2">Dipilih</span>
                        @endif
                        <p class="small flex-grow-1">Jenis ini memerlukan dokumen: {{ implode(', ', array_slice(array_keys($jenisConfig[$j]),0,4)) }}@if(count($jenisConfig[$j])>4), ... @endif</p>
            <a href="{{ route('pegawai.upload',['jenis'=>$j]) }}" class="btn btn-sm {{ $currentJenis === $j ? 'btn-outline-primary' : 'btn-primary' }} mt-auto">{{ ($currentJenis === $j) ? 'Lihat / Upload' : 'Pilih & Upload' }}</a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    <h4>Notifikasi</h4>
    @if(isset($notifikasi) && count($notifikasi))
        <div class="mb-2 d-flex gap-2">
            <form method="POST" action="{{ route('pegawai.notifikasi.readall') }}">
                @csrf
                <button class="btn btn-sm btn-outline-primary">Tandai Dibaca Semua</button>
            </form>
            <form method="POST" action="{{ route('pegawai.notifikasi.clearall') }}" onsubmit="return confirm('Hapus semua notifikasi?')">
                @csrf
                <button class="btn btn-sm btn-outline-danger">Hapus Semua</button>
            </form>
        </div>
        <ul class="list-group mb-3">
            @foreach($notifikasi as $notif)
                <li class="list-group-item p-2 d-flex justify-content-between align-items-start @if(!$notif->dibaca) list-group-item-warning @endif">
                    <span>{{ $notif->pesan }}</span>
                    @if(!$notif->dibaca)
                        <span class="badge bg-warning text-dark">Baru</span>
                    @endif
                </li>
            @endforeach
        </ul>
    @else
        <p class="text-muted">Belum ada notifikasi.</p>
    @endif
    {{-- Status berkas dipindah ke halaman terpisah pegawai.berkas --}}
</div>
@endsection
