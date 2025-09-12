@extends('layouts.app')
@section('content')
<div>
    <h2>Pegawai Siap SPK</h2>
    <p class="text-muted">Semua berkas pada daftar berikut sudah tervalidasi (VALID) dan siap dimasukkan dalam perhitungan SPK.</p>
    <table class="table table-bordered align-middle">
        <thead>
            <tr>
                <th>Pegawai</th>
                <th>Upload ID</th>
                <th>Jumlah Berkas</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($uploads as $u)
            <tr>
                <td>{{ $u->user->name }}</td>
                <td>#{{ $u->id }}</td>
                <td>{{ $u->detailUploads->count() }}</td>
                <td><a href="{{ route('admin.upload.detail', $u->id) }}" class="btn btn-sm btn-outline-primary">Lihat</a></td>
            </tr>
            @empty
            <tr><td colspan="4" class="text-center text-muted">Belum ada berkas lengkap.</td></tr>
            @endforelse
        </tbody>
    </table>
    <a href="{{ route('admin.spk.index') }}" class="btn btn-success">Menu SPK</a>
</div>
@endsection
