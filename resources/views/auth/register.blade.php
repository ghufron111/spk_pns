@extends('layouts.app')
@section('content')
<div class="row justify-content-center">
  <div class="col-md-6 col-lg-5">
    <div class="card shadow-sm">
      <div class="card-header bg-white"><strong>Registrasi Pegawai</strong></div>
      <div class="card-body">
        @if($errors->any())
          <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
        @endif
        <form method="POST" action="{{ route('register.store') }}">
          @csrf
          <div class="mb-3">
            <label class="form-label">NIP</label>
            <input type="text" name="id" class="form-control" value="{{ old('id') }}" placeholder="Masukkan NIP" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Nama</label>
            <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="Masukkan Nama" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="{{ old('email') }}" placeholder="Masukkan Email" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Pangkat Saat Ini</label>
            <select name="pangkat_id" class="form-select" required>
              <option value="" disabled selected>Pilih pangkat...</option>
              @foreach($pangkatList as $p)
                <option value="{{ $p->id }}" @selected(old('pangkat_id')==$p->id)>{{ $p->nama_pangkat }} ({{ $p->golongan }}/{{ $p->ruang }})</option>
              @endforeach
            </select>
          </div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Password</label>
              <input type="password" name="password" class="form-control" placeholder="Masukkan Password" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Konfirmasi Password</label>
              <input type="password" name="password_confirmation" class="form-control" placeholder="Masukkan Ulang Password" required>
            </div>
          </div>
          <div class="mt-3 d-flex gap-2">
            <button class="btn btn-primary">Daftar</button>
            <a href="{{ route('login') }}" class="btn btn-outline-secondary">Sudah punya akun?</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
