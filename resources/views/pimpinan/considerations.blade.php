@extends('layouts.app')
@section('content')
<div>
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h2 class="mb-0">Pengajuan Dipertimbangkan</h2>
    <a href="{{ route('pimpinan.dashboard') }}" class="btn btn-sm btn-outline-secondary">Kembali Dashboard</a>
  </div>
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
    <div class="col-auto"><a href="{{ route('pimpinan.considerations') }}" class="btn btn-sm btn-outline-secondary">Reset</a></div>
    @endif
  </form>
  <table class="table table-bordered table-sm align-middle">
    <thead>
      <tr>
        <th>#</th>
        <th>Pegawai</th>
        <th>Jenis</th>
        <th>Periode</th>
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
            @if($u->hasilSpk)
              <span class="badge bg-warning text-dark">{{ strtoupper($u->hasilSpk->hasil) }}</span>
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
        <tr><td colspan="7" class="text-center text-muted">Belum ada data.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
@endsection
