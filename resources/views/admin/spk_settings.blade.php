@extends('layouts.app')
@section('content')
<div class="container">
    <h2>Pengaturan SPK</h2>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

    <form method="POST" action="{{ route('admin.spk.settings.update') }}" class="card p-3 mb-4">
        @csrf
        <h5 class="mb-3">Bobot Dokumen</h5>
        <div class="row g-3">
            @foreach($weights as $key => $val)
                <div class="col-md-4">
                    <label class="form-label text-uppercase small">{{ str_replace('_',' ', $key) }}</label>
                    <input type="number" name="weights[{{ $key }}]" value="{{ $val }}" min="0" class="form-control" />
                </div>
            @endforeach
        </div>
        <hr />
        <h5 class="mb-3">Ambang Keputusan</h5>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Ambang Disetujui (%)</label>
                <input type="number" min="0" max="100" id="approved_percent" value="{{ round($thresholds['approved']*100) }}" class="form-control" />
                <input type="hidden" name="thresholds[approved]" id="approved_decimal" value="{{ $thresholds['approved'] }}" />
            </div>
            <div class="col-md-4">
                <label class="form-label">Ambang Dipertimbangkan (%)</label>
                <input type="number" min="0" max="100" id="consider_percent" value="{{ round($thresholds['consider']*100) }}" class="form-control" />
                <input type="hidden" name="thresholds[consider]" id="consider_decimal" value="{{ $thresholds['consider'] }}" />
            </div>
            <div class="col-md-4">
                <label class="form-label">Minimal Dokumen Bernilai</label>
                <input type="number" min="1" name="thresholds[min_valid_docs]" value="{{ $thresholds['min_valid_docs'] }}" class="form-control" />
            </div>
        </div>
        <script>
            function syncThresholdPercent(idPercent, idHidden){
                const p = document.getElementById(idPercent);
                const h = document.getElementById(idHidden);
                if(!p||!h) return;
                p.addEventListener('input', ()=>{
                    let val = parseInt(p.value||0,10);
                    if(isNaN(val)) val=0;
                    if(val<0) val=0;
                    if(val>100) val=100;
                    p.value = val; // clamp tampilan
                    h.value = (val/100).toFixed(2);
                });
                // Pastikan initial clamp
                let init = parseInt(p.value||0,10);
                if(init>100){ p.value=100; h.value=(100/100).toFixed(2);} 
                if(init<0){ p.value=0; h.value=(0).toFixed(2);}        
            }
            syncThresholdPercent('approved_percent','approved_decimal');
            syncThresholdPercent('consider_percent','consider_decimal');
        </script>
        <div class="mt-4">
            <button type="submit" class="btn btn-primary">Simpan Pengaturan</button>
            <a href="{{ route('admin.spk.index') }}" class="btn btn-secondary">Kembali</a>
        </div>
    </form>
    <p class="text-muted small">Catatan: Perubahan disimpan ke file konfigurasi. Pertimbangkan memindahkan ke tabel database untuk fleksibilitas di produksi.</p>
</div>
@endsection
