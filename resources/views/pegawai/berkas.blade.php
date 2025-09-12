@extends('layouts.app')
@section('content')
<div>
    <h2>Status Berkas Per Periode</h2>
    <p class="text-muted">Klik salah satu kartu periode untuk melihat rincian seluruh dokumen dan statusnya.</p>
    @php
        $jenisConfig = config('berkas.jenis');
        $slugToLabel = [];
        foreach($jenisConfig as $jKey=>$docs){
            foreach(array_keys($docs) as $label){
                $slug = \Illuminate\Support\Str::slug($label,'_');
                $slugToLabel[$slug] = $label;
            }
        }
    @endphp
    @if(!isset($grouped) || $grouped->isEmpty())
        <div class="alert alert-info">Belum ada berkas yang diunggah.</div>
    @else
    <div class="accordion" id="periodeAccordion">
        @php $idx=0; @endphp
        @foreach($grouped as $periode => $items)
            @php
                $idx++;
                // Flatten detailUploads
                $allDetails = $items->flatMap->detailUploads;
                $totalDocs = $allDetails->count();
                $countValid = $allDetails->whereIn('status',['valid','disetujui'])->count();
                $countPending = $allDetails->where('status','pending')->count();
                $countRejected = $allDetails->where('status','ditolak')->count();
                // Distinct jenis involved
                $jenisList = $items->pluck('jenis')->unique()->filter();
                $progress = $totalDocs>0 ? round(($countValid / $totalDocs)*100) : 0;
            @endphp
            <div class="accordion-item mb-2 shadow-sm">
                <h2 class="accordion-header" id="heading{{$idx}}">
                    <button class="accordion-button collapsed d-flex flex-column flex-md-row gap-2" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{$idx}}" aria-expanded="false" aria-controls="collapse{{$idx}}">
                        <div class="me-auto">
                            <strong>Periode: {{ $periode }}</strong>
                            <div class="small text-muted">Jenis: {{ $jenisList->implode(', ') ?: '-' }}</div>
                        </div>
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <span class="badge bg-success">Valid {{ $countValid }}</span>
                            <span class="badge bg-warning text-dark">Pending {{ $countPending }}</span>
                            <span class="badge bg-danger">Ditolak {{ $countRejected }}</span>
                            <span class="badge bg-secondary">Total {{ $totalDocs }}</span>
                            <div class="text-nowrap small">Progress: {{ $progress }}%</div>
                        </div>
                    </button>
                </h2>
                <div id="collapse{{$idx}}" class="accordion-collapse collapse" aria-labelledby="heading{{$idx}}" data-bs-parent="#periodeAccordion">
                    <div class="accordion-body px-3 py-3">
                        <div class="table-responsive">
                            <table class="table table-sm table-striped mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:110px; text-align:center">Jenis</th>
                                        <th style="width:500px; text-align:center">Nama Dokumen</th>
                                        <th style="width:120px; text-align:center">Status</th>
                                        <th style="text-align:center">Catatan</th>
                                        <th style="width:160px; text-align:center">Waktu Upload</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @forelse($allDetails->sortByDesc('created_at') as $detail)
                                    @php $parent = $items->firstWhere('id',$detail->upload_id); @endphp
                                    <tr>
                                        <td>{{ ucwords($parent?->jenis) }}</td>
                                        <td>{{ $slugToLabel[$detail->nama_berkas] ?? strtoupper(str_replace('_',' ', $detail->nama_berkas)) }}</td>
                                        <td>
                                            <span class="badge bg-{{ in_array($detail->status,['valid','disetujui']) ? 'success' : ($detail->status=='pending' ? 'warning text-dark' : 'danger') }}">{{ strtoupper($detail->status) }}</span>
                                        </td>
                                        <td class="small">{{ $detail->catatan }}</td>
                                        <td class="small">{{ $detail->created_at }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted small">Tidak ada detail.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    @endif
    <a href="{{ route('pegawai.upload') }}" class="btn btn-primary mt-3">Tambah / Perbarui Berkas</a>
</div>
<style>
    .accordion-button.collapsed { background:#f8f9fa; }
    .accordion-button:not(.collapsed) { background:#e9f2ff; }
    .accordion-button { transition: background .2s; }
</style>
@endsection
