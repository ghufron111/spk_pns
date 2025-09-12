@extends('layouts.app')
@section('content')
<div class="container">
    <h2>Detail Berkas: {{ $upload->user->name }} (Upload #{{ $upload->id }})</h2>
    <p class="text-muted">Jenis: <strong>{{ $upload->jenis ? ucfirst($upload->jenis) : '-' }}</strong> • Periode: <strong>{{ $upload->periode ?? '-' }}</strong></p>
    <a href="{{ route('admin.validasi.index') }}" class="btn btn-secondary mb-3">Kembali</a>
    <form action="{{ route('admin.upload.validasi.batch', $upload->id) }}" method="POST">
        @csrf
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Nama Berkas</th>
                        <th>Status Saat Ini</th>
                        <th>Ubah Status</th>
                        <th>Catatan</th>
                        <th>Preview</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($upload->detailUploads as $detail)
                    <tr>
                        <td class="fw-semibold">{{ strtoupper(str_replace('_',' ', $detail->nama_berkas)) }}</td>
                        <td>
                            @php
                                $cls = match($detail->status) {
                                    'valid','disetujui' => 'success',
                                    'pending' => 'warning text-dark',
                                    'ditolak','tidak_disetujui' => 'danger',
                                    default => 'secondary'
                                };
                            @endphp
                            <span class="badge bg-{{ $cls }}">{{ strtoupper(str_replace('_',' ', $detail->status)) }}</span>
                        </td>
                        <td>
                            <select name="status[{{ $detail->id }}]" class="form-select form-select-sm">
                                <option value="disetujui" @selected($detail->status=='disetujui')>Disetujui</option>
                                <option value="tidak_disetujui" @selected($detail->status=='tidak_disetujui')>Tidak Disetujui</option>
                                <option value="pending" @selected($detail->status=='pending')>Pending</option>
                                <option value="ditolak" @selected($detail->status=='ditolak')>Ditolak (Revisi Pegawai)</option>
                            </select>
                        </td>
                        <td>
                            <input type="text" name="catatan[{{ $detail->id }}]" class="form-control form-control-sm" value="{{ $detail->catatan }}" placeholder="Catatan (opsional)">
                        </td>
                        <td><a href="{{ route('admin.upload.preview', $detail->id) }}" target="_blank" class="btn btn-outline-primary btn-sm">View</a></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-between align-items-center mt-3">
            <button type="submit" class="btn btn-success">Simpan Semua Perubahan</button>
        </div>
    </form>
</div>
@endsection