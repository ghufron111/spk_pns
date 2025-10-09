@extends('layouts.app')
@section('content')
<div class="container">
    <h2>Detail Berkas: {{ $upload->user->name }} (Upload #{{ $upload->id }})</h2>
    <p class="text-muted">Jenis: <strong>{{ $upload->jenis ? ucfirst($upload->jenis) : '-' }}</strong> • Periode: <strong>{{ $upload->periode ?? '-' }}</strong></p>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="{{ route('admin.validasi.index') }}" class="btn btn-secondary">Kembali</a>
        <button type="button" class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#detailHelpModal">
            <i class="bi bi-question-circle me-1"></i> Bantuan
        </button>
    </div>
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
                                $statusLabel = $detail->status === 'ditolak' ? 'revisi' : $detail->status;
                                $cls = match($statusLabel) {
                                    'valid','disetujui' => 'success',
                                    'dipertimbangkan' => 'info text-dark',
                                    'pending' => 'warning text-dark',
                                    'revisi','tidak_disetujui' => 'danger',
                                    default => 'secondary'
                                };
                            @endphp
                            <span class="badge bg-{{ $cls }}">{{ strtoupper(str_replace('_',' ', $statusLabel)) }}</span>
                        </td>
                        <td>
                            <select name="status[{{ $detail->id }}]" class="form-select form-select-sm">
                                <option value="disetujui" @selected($detail->status=='disetujui')>Disetujui</option>
                                <option value="valid" @selected($detail->status=='valid')>Valid</option>
                                <option value="dipertimbangkan" @selected($detail->status=='dipertimbangkan')>Dipertimbangkan</option>
                                <option value="pending" @selected($detail->status=='pending')>Pending</option>
                                <option value="tidak_disetujui" @selected($detail->status=='tidak_disetujui')>Tidak Disetujui</option>
                                <option value="revisi" @selected(in_array($detail->status,['revisi','ditolak']))>Revisi</option>
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
    <!-- Modal Bantuan Detail Validasi -->
    <div class="modal fade" id="detailHelpModal" tabindex="-1" aria-labelledby="detailHelpLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h5 class="modal-title" id="detailHelpLabel">Bantuan Status & Poin</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body small">
                    <p class="mb-2">Poin (faktor nilai) per status dokumen:</p>
                    <ul class="mb-2">
                        <li>Disetujui: 1.0 (100% bobot kriteria)</li>
                        <li>Valid: 0.9 (90% bobot kriteria)</li>
                        <li>Dipertimbangkan: 0.7 (70% bobot kriteria)</li>
                        <li>Pending: 0.0 (0% bobot kriteria)</li>
                        <li>Revisi/Ditolak/Tidak Disetujui: 0.0 (0% bobot kriteria)</li>
                    </ul>
                    <p class="mb-1">Dampak ke skor SPK:</p>
                    <ul>
                        <li>Poin per berkas = bobot × faktor status.</li>
                        <li>Skor akhir dinormalisasi: (∑ poin berkas) / (∑ bobot yang diunggah) × 100%.</li>
                        <li>Pending/Revisi memberi 0 poin; Dipertimbangkan memberi nilai parsial.</li>
                    </ul>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection