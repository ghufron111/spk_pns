@extends('layouts.app')
@section('content')
<div>
    @php use Illuminate\Support\Str; @endphp
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h2 class="h4 mb-0">SPK – Daftar Peserta</h2>
        <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#spkHelpModal">
            <i class="bi bi-question-circle me-1"></i> Bantuan
        </button>
    </div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @php
        $maxLabel = '';
        if (!empty($filterJenis)) {
            $wj = $weightsPerJenis[$filterJenis] ?? ($weightsPerJenis[Str::slug($filterJenis,'_')] ?? []);
            $maxLabel = 'Bobot total maksimum '.array_sum($wj ?: []);
        } else {
            $maxLabel = 'Bobot total maksimum '.array_sum($weights ?? config('berkas.weights'));
        }
    @endphp
    <p class="text-muted mb-2">Peserta: minimal 3 berkas bernilai (di luar KTP & KK) berstatus VALID / DISETUJUI. {{ $maxLabel }}.</p>
    @php
        $jenisTotals = [];
        foreach(($distinctJenis ?? collect()) as $j){
            $wj = $weightsPerJenis[$j] ?? ($weightsPerJenis[Str::slug($j,'_')] ?? []);
            $jenisTotals[$j] = array_sum($wj);
        }
    @endphp
    @if(!empty($jenisTotals))
        <div class="small text-muted mb-2">
            Maks bobot per jenis:
            @foreach($jenisTotals as $j=>$tot)
                <span class="badge bg-secondary-subtle text-secondary-emphasis border me-1">{{ strtoupper($j) }}: {{ $tot }}</span>
            @endforeach
        </div>
    @endif
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
                <th>Skor (Pembilang)</th>
                <th>Normalisasi %</th>
                <th>Skor %</th>
                <th>Hasil Terakhir</th>
                <th>Ringkasan</th>
                <th class="col-catatan">Catatan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($uploads as $u)
                @php
                    $jenisW = $weightsPerJenis[$u->jenis] ?? $weightsPerJenis['reguler'] ?? [];
                    // Effective denominator: hanya bobot dokumen yang benar-benar diunggah (ada detail upload-nya)
                    $uploadedNames = $u->detailUploads->pluck('nama_berkas')->unique();
                    $effectiveTotal = $uploadedNames->reduce(function($carry,$name) use ($jenisW){ return $carry + ($jenisW[$name] ?? 0); }, 0) ?: 1;
                    $validDocs = $u->detailUploads->filter(fn($d)=>in_array($d->status,['valid','disetujui']) && array_key_exists($d->nama_berkas,$jenisW));
                    $rawScore = $validDocs->sum(fn($d)=>$jenisW[$d->nama_berkas] ?? 0);
                    $norm = $effectiveTotal ? round(($rawScore / $effectiveTotal) * 100,2) : 0;
                    $hasilRow = $hasil->firstWhere('upload_id', $u->id); 
                    $labelHasil = $hasilRow? strtoupper($hasilRow->hasil):'-';
                    $badgeClass = match($hasilRow->hasil ?? '') {
                        'disetujui' => 'bg-success',
                        'dipertimbangkan' => 'bg-warning text-dark',
                        'ditolak' => 'bg-danger',
                        default => 'bg-secondary'
                    };

                    // Ringkasan perhitungan SPK (selaras dengan faktor status di controller)
                    $statusFactor = [
                        'disetujui' => 1.0,
                        'valid' => 0.9,
                        'dipertimbangkan' => 0.7,
                        'pending' => 0.0,
                        'revisi' => 0.0,
                        'ditolak' => 0.0,
                        'tidak_disetujui' => 0.0,
                    ];
                    $eligible = $u->detailUploads->filter(fn($d)=> array_key_exists($d->nama_berkas,$jenisW));
                    $totalWeightJenis = array_sum($jenisW) ?: 1;
                    $uploadedWeight = $eligible->sum(fn($d)=> $jenisW[$d->nama_berkas] ?? 0) ?: 1;
                    $qualifiedNames = $eligible->filter(fn($d)=> in_array($d->status,['valid','disetujui','dipertimbangkan']))->pluck('nama_berkas');
                    $qualifiedWeight = $qualifiedNames->reduce(function($c,$name) use ($jenisW){return $c + ($jenisW[$name] ?? 0);}, 0);
                    $scoreNumerator = 0.0;
                    $counts = [ 'disetujui'=>0, 'valid'=>0, 'dipertimbangkan'=>0, 'pending'=>0, 'revisi'=>0, 'tidak_disetujui'=>0 ];
                    foreach($eligible as $d){
                        $st = $d->status === 'ditolak' ? 'revisi' : $d->status; // normalisasi label
                        $w = $jenisW[$d->nama_berkas] ?? 0;
                        $factor = $statusFactor[$st] ?? 0.0;
                        $scoreNumerator += $w * $factor;
                        if(isset($counts[$st])) $counts[$st]++;
                    }
                    $ringkasanNorm = $uploadedWeight ? round(($scoreNumerator / $uploadedWeight) * 100, 2) : 0;
                    $coverageUploaded = round(($uploadedWeight / $totalWeightJenis) * 100, 2);
                    $coverageQualified = $totalWeightJenis ? round(($qualifiedWeight / $totalWeightJenis) * 100, 2) : 0;
                    // Sinkronkan nilai tabel dengan perhitungan faktor status (menggunakan pembilang berbasis faktor)
                    $rawScore = rtrim(rtrim(number_format($scoreNumerator, 2, '.', ''), '0'), '.');
                    $norm = $ringkasanNorm;
                @endphp
                <tr>
                    <td>{{ $u->user->name }}</td>
                    <td>#{{ $u->id }}</td>
                    <td><span class="badge bg-info text-dark">{{ strtoupper($u->jenis ?? '-') }}</span></td>
                    <td>{{ $u->periode ?? '-' }}</td>
                    <td>{{ $validDocs->count() }}</td>
                    <td>{{ $rawScore }}</td>
                    <td>{{ $norm }}</td>
                    <td>
                        @php
                            $skorPercent = null;
                            if($hasilRow && preg_match('/\[SKOR:\s*([0-9.]+)\s*\/\s*100\]/',$hasilRow->catatan,$mm)) {
                                $skorPercent = $mm[1];
                            } elseif($norm!==null) { $skorPercent = $norm; }
                        @endphp
                        {{ $skorPercent !== null ? $skorPercent : '-' }}
                    </td>
                    <td><span class="badge {{ $badgeClass }}">{{ $labelHasil }}</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#sum-{{ $u->id }}">Lihat</button>
                        <!-- Modal Ringkasan per Upload -->
                        <div class="modal fade" id="sum-{{ $u->id }}" tabindex="-1" aria-labelledby="sumLabel-{{ $u->id }}" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header py-2">
                                        <h5 class="modal-title" id="sumLabel-{{ $u->id }}">Ringkasan Perhitungan SPK</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body small">
                                        <div class="row g-2 mb-2">
                                            <div class="col-6">
                                                <div class="text-muted">Total Bobot Jenis</div>
                                                <div class="fw-semibold">{{ $totalWeightJenis }}</div>
                                            </div>
                                            <div class="col-6">
                                                <div class="text-muted">Bobot Terunggah</div>
                                                <div class="fw-semibold">{{ $uploadedWeight }}</div>
                                            </div>
                                            <div class="col-6">
                                                <div class="text-muted">Bobot Memenuhi Syarat</div>
                                                <div class="fw-semibold">{{ $qualifiedWeight }}</div>
                                            </div>
                                            <div class="col-6">
                                                <div class="text-muted">Skor (Pembilang)</div>
                                                <div class="fw-semibold">{{ rtrim(rtrim(number_format($scoreNumerator, 2, '.', ''), '0'), '.') }}</div>
                                            </div>
                                        </div>
                                        <ul class="mb-2">
                                            <li>Coverage: Upload {{ $coverageUploaded }}% | Qualified {{ $coverageQualified }}%</li>
                                            <li>Normalisasi: {{ $ringkasanNorm }}%</li>
                                        </ul>
                                        <div class="mb-1">Rincian Status:</div>
                                        <ul class="mb-0">
                                            <li>Disetujui: {{ $counts['disetujui'] }}</li>
                                            <li>Valid: {{ $counts['valid'] }}</li>
                                            <li>Dipertimbangkan: {{ $counts['dipertimbangkan'] }}</li>
                                            <li>Pending: {{ $counts['pending'] }}</li>
                                            <li>Revisi/Tidak Disetujui: {{ ($counts['revisi'] + $counts['tidak_disetujui']) }}</li>
                                        </ul>
                                    </div>
                                    <div class="modal-footer py-2">
                                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="col-catatan"><div class="small">{{ $hasilRow? $hasilRow->catatan:'-' }}</div></td>
                </tr>
            @empty
            <tr><td colspan="9" class="text-center text-muted">Belum ada peserta siap.</td></tr>
            @endforelse
        </tbody>
    </table>
    <!-- Modal Bantuan SPK -->
    <div class="modal fade" id="spkHelpModal" tabindex="-1" aria-labelledby="spkHelpLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h5 class="modal-title" id="spkHelpLabel">Bantuan Penilaian SPK</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body small">
                    <p class="mb-2">Poin (faktor nilai) untuk setiap status dokumen:</p>
                    <ul class="mb-2">
                        <li>Disetujui: 1.0 (100% bobot kriteria)</li>
                        <li>Valid: 0.9 (90% bobot kriteria)</li>
                        <li>Dipertimbangkan: 0.7 (70% bobot kriteria)</li>
                        <li>Pending: 0.0 (0% bobot kriteria)</li>
                        <li>Revisi/Ditolak/Tidak Disetujui: 0.0 (0% bobot kriteria)</li>
                    </ul>
                    <p class="mb-1">Perhitungan skor:</p>
                    <ul>
                        <li>Poin per kriteria = bobot_kriteria × faktor_status.</li>
                        <li>Skor akhir = (∑ poin kriteria) / (∑ bobot yang diunggah) × 100%.</li>
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
