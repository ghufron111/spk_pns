@extends('layouts.app')
@section('content')
<div>
  <h2>Persetujuan Kenaikan Pangkat</h2>
  <ul class="nav nav-tabs mb-3">
    <li class="nav-item"><a class="nav-link active" href="{{ route('pimpinan.approvals.index') }}">Rekomendasi SPK</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('pimpinan.considerations') }}">Perlu Dipertimbangkan</a></li>
  </ul>
  @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
  <form method="GET" class="row g-2 mb-3">
    <div class="col-auto">
      <select name="jenis" class="form-select form-select-sm" onchange="this.form.submit()">
        <option value="">-- Semua Jenis --</option>
        @foreach($distinctJenis as $j)
          <option value="{{ $j }}" @selected($filterJenis===$j)>{{ strtoupper($j) }}</option>
        @endforeach
      </select>
    </div>
    @if($filterJenis)
    <div class="col-auto"><a href="{{ route('pimpinan.approvals.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a></div>
    @endif
  </form>
  <table class="table table-bordered table-sm align-middle">
    <thead>
      <tr>
        <th>#</th>
        <th>Pegawai</th>
        <th>Jenis</th>
        <th>Periode</th>
  <th>Skor %</th>
  <th>Hasil SPK</th>
        <th>Catatan</th>
        <th>Aksi</th>
      </tr>
    </thead>
    <tbody>
      @forelse($uploads as $u)
        <tr>
          <td>{{ $u->id }}</td>
          <td>{{ $u->user->name ?? '-' }}</td>
          <td>{{ strtoupper($u->jenis ?? '-') }}</td>
          <td>{{ $u->periode ?? '-' }}</td>
          <td>
            @php $skorPercent = null; if($u->hasilSpk && preg_match('/\[SKOR:\s*([0-9.]+)\s*\/\s*100\]/',$u->hasilSpk->catatan,$m)){ $skorPercent=$m[1]; } @endphp
            {{ $skorPercent!==null ? $skorPercent : '-' }}
          </td>
          <td>
            @if($u->hasilSpk)
              <span class="badge @class([
                'bg-success'=> $u->hasilSpk->hasil==='disetujui',
                'bg-warning text-dark'=> $u->hasilSpk->hasil==='dipertimbangkan',
                'bg-danger'=> $u->hasilSpk->hasil==='ditolak',
                'bg-secondary'=> !in_array($u->hasilSpk->hasil,['disetujui','dipertimbangkan','ditolak'])
              ])">{{ strtoupper($u->hasilSpk->hasil) }}</span>
            @else - @endif
          </td>
          <td style="max-width:320px; white-space:normal;">{{ $u->hasilSpk->catatan ?? '-' }}</td>
          <td>
            <form method="POST" action="{{ route('pimpinan.approvals.approve',$u->id) }}" class="d-inline">
              @csrf
              <button class="btn btn-sm btn-success" onclick="return confirm('Setujui pengajuan ini?')">Setujui</button>
            </form>
            <form method="POST" action="{{ route('pimpinan.approvals.reject',$u->id) }}" class="d-inline ms-1">
              @csrf
              <button class="btn btn-sm btn-danger" onclick="return confirm('Tolak pengajuan ini?')">Tolak</button>
            </form>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="8" class="text-center text-muted small">
            Belum ada rekomendasi siap. Kemungkinan:
            <ul class="list-unstyled mt-2">
              <li>• SPK belum dijalankan atau tidak ada upload mencapai ambang disetujui.</li>
              <li>• Semua rekomendasi sudah difinalkan (disetujui / ditolak).</li>
              <li>• Filter jenis membatasi hasil (coba reset).</li>
            </ul>
          </td>
        </tr>
      @endforelse
    </tbody>
  </table>
</div>
@endsection
