@extends('layouts.app')
@section('content')
<div>
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
        <h2 class="mb-2 mb-md-0">Upload Berkas Kenaikan Pangkat</h2>
        <div class="small text-muted">Pangkat Saat Ini: <span class="badge bg-primary">{{ auth()->user()->pangkat ?? '-' }}</span></div>
    </div>
    @if(isset($periodeMulai,$periodeSelesai) && $periodeMulai && $periodeSelesai)
        <div class="alert alert-info py-2 small mb-3">
            @php
                $today = \Carbon\Carbon::today();
            @endphp

            @if($today->between($periodeMulai, $periodeSelesai))
                Periode aktif: <strong>{{ $periodeMulai }}</strong> s/d <strong>{{ $periodeSelesai }}</strong>
                @if(isset($periodeLabel) && $periodeLabel)
                    <span class="ms-2">(Label: {{ $periodeLabel }})</span>
                @endif
            @else
                <span class="text-danger">Tidak dalam masa pengangkatan PNS</span>
            @endif
        </div>
    @endif
    @php
        $remaining = $needs ?? collect();
        // Dokumen dengan status ditolak khusus ditandai untuk perbaikan
        $rejected = collect($required)->filter(fn($d)=>(($latest[$d] ?? null)==='ditolak'));
        $missing = $remaining->filter(fn($d)=>(($latest[$d] ?? null)===null));
        $labelOf = function($slug) use ($slugToLabel){ return $slugToLabel[$slug] ?? ucwords(str_replace('_',' ',$slug)); };
    @endphp
    @if($remaining->count()>0)
        <div class="alert alert-warning small">
            <strong>Peringatan:</strong> Masih ada {{ $remaining->count() }} dokumen yang belum lengkap.
            <ul class="mb-1 ps-3">
                @foreach($remaining->take(6) as $r)
                    <li>{{ $labelOf($r) }} @if(($latest[$r] ?? null)==='ditolak')<span class="text-danger">(Perlu perbaikan)</span>@elseif(($latest[$r] ?? null)===null)<span class="text-muted">(Belum diupload)</span>@endif</li>
                @endforeach
                @if($remaining->count()>6)<li>... dan {{ $remaining->count()-6 }} lainnya</li>@endif
            </ul>
            <div class="mb-0">
                @if($rejected->count()>0)
                    <span class="text-danger">{{ $rejected->count() }} dokumen ditolak perlu diupload ulang.</span>
                @endif
                @if($missing->count()>0)
                    <span class="ms-2">{{ $missing->count() }} belum pernah diupload.</span>
                @endif
            </div>
        </div>
    @else
        <div class="alert alert-success small">Semua dokumen wajib untuk jenis <strong>{{ ucfirst($selectedJenis) }}</strong> pada periode <strong>{{ $selectedPeriode }}</strong> sudah lengkap.</div>
    @endif
    <form action="{{ route('pegawai.upload.store') }}" method="POST" enctype="multipart/form-data" class="mb-4 shadow-sm border rounded p-3 bg-white">
        @csrf
        <div class="d-flex flex-column flex-md-row gap-3">
            <div class="mb-3 w-100" style="max-width:400px">
                <label class="form-label">Jenis Kenaikan Pangkat</label>
                <select name="jenis" class="form-select @error('jenis') is-invalid @enderror" {{ isset($lockedJenis) && $lockedJenis ? 'disabled' : '' }}>
                    @foreach($jenisList as $j)
                        <option value="{{ $j }}" {{ $selectedJenis === $j ? 'selected':'' }}>{{ ucfirst($j) }}</option>
                    @endforeach
                </select>
                @if(isset($lockedJenis) && $lockedJenis)
                    <input type="hidden" name="jenis" value="{{ $lockedJenis }}">
                    <div class="alert alert-info mt-2 p-2 small">Jenis dikunci ke <strong>{{ ucfirst($lockedJenis) }}</strong> sampai proses disetujui atau ditolak.</div>
                @else
                    <small class="text-muted">Setelah upload berjalan, jenis akan terkunci.</small>
                @endif
                @error('jenis')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3 w-300" style="max-width:400px">
                <label class="form-label">Periode (Contoh: 2025 atau 2025-S1)</label>
                <div class="input-group">
                    <input type="text" name="periode" value="{{ old('periode', $selectedPeriode ?? '') }}" class="form-control @error('periode') is-invalid @enderror" placeholder="{{ $periodeLabel ?? '2025' }}" maxlength="30" readonly>
                    @if(isset($periodeHist) && $periodeHist->count())
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#periodeHistList">Riwayat</button>
                    @endif
                </div>
                @error('periode')<div class="invalid-feedback">{{ $message }}</div>@enderror
                @if(isset($periodeHist) && $periodeHist->count())
                    <div class="collapse mt-2" id="periodeHistList">
                        <div class="card card-body p-2 small">
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($periodeHist as $p)
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="document.querySelector('[name=periode]').value='{{ $p }}'">{{ $p }}</button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
                <small class="text-muted">Periode membedakan batch upload dan menjaga histori.</small>
            </div>
            @php
                $jenisKhusus = in_array($selectedJenis, ['pilihan','ijazah']);
            @endphp
            <div class="mb-3" style="max-width:400px; {{ $jenisKhusus ? '' : 'display:none' }}" id="targetPangkatWrapper">
                <label class="form-label d-flex justify-content-between align-items-center">
                    <span>Target Pangkat ({{ $jenisKhusus ? 'wajib' : 'khusus' }})</span>
                    <span class="text-muted small">Maks: IVe</span>
                </label>
                <select name="target_pangkat" class="form-select @error('target_pangkat') is-invalid @enderror" {{ $jenisKhusus ? '' : 'disabled' }} {{ isset($selectedTarget) && $selectedTarget ? 'disabled' : '' }}>
                    <option value="" disabled {{ (old('target_pangkat')||$selectedTarget)?'':'selected' }}>-- Pilih Pangkat --</option>
                    @if(isset($targetPangkatOptions))
                        @php $hasOptions = false; @endphp
                        @foreach($targetPangkatOptions as $code => $label)
                            @php $hasOptions = true; @endphp
                            <option value="{{ $code }}" {{ (old('target_pangkat')===$code || $selectedTarget===$code) ? 'selected' : '' }}>{{ $code }} - {{ $label }}</option>
                        @endforeach
                        @if(!$hasOptions)
                            <option value="" disabled>(Tidak ada pangkat lebih tinggi tersedia)</option>
                        @endif
                    @endif
                </select>
                @if(isset($selectedTarget) && $selectedTarget)
                    <input type="hidden" name="target_pangkat" value="{{ $selectedTarget }}">
                    <div class="form-text text-success">Target pangkat telah dikunci ke <strong>{{ $selectedTarget }}</strong>.</div>
                @endif
                @error('target_pangkat')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text">Hanya pangkat di atas pangkat sekarang ({{ auth()->user()->pangkat ?? '-' }}) dan ≤ IVe.</div>
            </div>
        </div>
    <div class="row g-3 mt-2">
            @foreach($required as $doc)
            <div class="col-md-6">
                @php
                    $status = $latest[$doc] ?? null;
                    $label = $slugToLabel[$doc] ?? ucwords(str_replace('_',' ', $doc));
                @endphp
                <div class="card h-100 border @if($status==='ditolak') border-danger @elseif($status==='valid') border-success @elseif($status==='pending') border-warning @endif">
                    <div class="card-body p-3">
                        <label class="form-label fw-semibold d-flex justify-content-between align-items-center">
                            <span>{{ $label }}</span>
                            <span>
                                @if($status === 'valid') <span class="badge bg-success">Valid</span>
                                @elseif($status === 'pending') <span class="badge bg-warning text-dark">Pending</span>
                                @elseif($status === 'ditolak') <span class="badge bg-danger">Ditolak</span>
                                @elseif(!$status) <span class="badge bg-secondary">Belum</span>
                                @endif
                            </span>
                        </label>
                        <input type="file" name="berkas[{{ $doc }}]" class="form-control form-control-sm" @if(!$needs->contains($doc) && $status!=='ditolak') disabled @endif>
                        @error('berkas.'.$doc)<div class="text-danger small mt-1">{{ $label }}: {{ $message }}</div>@enderror
                        @if(!$needs->contains($doc) && $status!=='ditolak')
                            <div class="small text-muted mt-1">Sudah dipenuhi.</div>
                        @elseif($status==='ditolak')
                            <div class="small text-danger mt-1">Unggah ulang perbaikan.</div>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        <div class="mt-4">
            <button type="submit" class="btn btn-success">Simpan / Tambah Berkas</button>
            <a href="{{ route('pegawai.dashboard') }}" class="btn btn-secondary">Kembali</a>
        </div>
    </form>
    <div class="small text-muted mt-2">Daftar dokumen ini sudah termasuk dokumen dinamis tambahan (jika ada) yang ditambahkan oleh admin.</div>
    <script>
    (function(){
        const jenisSelect = document.querySelector('select[name=jenis]');
        const targetWrap = document.getElementById('targetPangkatWrapper');
        const targetSel = document.querySelector('select[name=target_pangkat]');
        if(jenisSelect && !jenisSelect.disabled){
            jenisSelect.addEventListener('change', function(){
                const periodeInput = document.querySelector('input[name=periode]');
                const periodeVal = periodeInput ? periodeInput.value.trim() : '';
                const params = new URLSearchParams();
                params.set('jenis', this.value);
                if(periodeVal) params.set('periode', periodeVal);
                window.location = '{{ route('pegawai.upload') }}' + '?' + params.toString();
            });
        }
        // Toggle target pangkat UI (client-side safeguard; server enforces real rules)
        function updateTargetVisibility(){
            if(!targetWrap) return;
            const val = jenisSelect ? jenisSelect.value : '';
            const special = ['pilihan','ijazah'].includes(val);
            targetWrap.style.display = special ? '' : 'none';
            if(targetSel){ targetSel.disabled = !special; if(!special) targetSel.value=''; }
        }
        updateTargetVisibility();
    })();
    </script>
</div>
@endsection
