@extends('layouts.app')
@section('content')
<h2>Pengaturan Dokumen Kenaikan Pangkat</h2>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white"><strong>Tambah Dokumen Baru</strong></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.dokumen.settings.store') }}" class="needs-validation" novalidate>
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Jenis Kenaikan</label>
                        <select name="jenis" class="form-select" required>
                            @foreach(array_keys(config('berkas.jenis')) as $j)
                                <option value="{{ $j }}" @selected(old('jenis')==$j)>{{ ucfirst($j) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama / Label Dokumen</label>
                        <input type="text" name="label" class="form-control" value="{{ old('label') }}" placeholder="Misal: Surat Keterangan Organisasi" required>
                        <div class="form-text">Digunakan untuk slug dan tampilan.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Pattern Nama File (opsional)</label>
                        <input type="text" name="pattern" class="form-control" value="{{ old('pattern') }}" placeholder="AUTO jika kosong akan di isi NIP">
                        <div class="form-text">Contoh: SURAT_ORGANISASI_NIPBARU.pdf</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Bobot (opsional)</label>
                        <input type="number" name="weight" class="form-control" value="{{ old('weight') }}" placeholder="Default {{ config('berkas.default_weight',5) }}">
                    </div>
                    <button class="btn btn-primary w-100">Tambah Dokumen</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <strong>Daftar Dokumen & Bobot</strong>
                <span class="small text-muted">Total: {{ $allDocs->count() }}</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height:480px;overflow:auto;">
                    <table class="table table-sm table-striped mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Nama Dokumen</th>
                                <th>Bobot</th>
                                <th>Sumber</th>
                                <th style="width:90px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($dynamicWeights as $slug=>$w)
                                <tr>
                                    <td>{{ $slug }}</td>
                                    <td>{{ $w }}</td>
                                    <td>{{ array_key_exists($slug, config('berkas.weights')) ? 'Config' : 'Dinamis' }}</td>
                                    <td>
                                        @if(!array_key_exists($slug, config('berkas.weights')))
                                        <form method="POST" action="{{ route('admin.dokumen.settings.store') }}" onsubmit="return confirm('Hapus dokumen ini? (Hanya menghapus bobot/pattern dinamis)')">
                                            @csrf
                                            <input type="hidden" name="_delete_slug" value="{{ $slug }}">
                                            <button class="btn btn-sm btn-outline-danger">Hapus</button>
                                        </form>
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-2 small text-muted">Dokumen baru disimpan ke database (tabel spk_settings) dan ikut dihitung bobotnya.</div>
            </div>
        </div>
    </div>
</div>
@endsection
