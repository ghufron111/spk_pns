@extends('layouts.app')
@section('content')
<div class="row g-4">
  <div class="col-lg-7">
    @if(session('success'))<div class="alert alert-success py-2">{{ session('success') }}</div>@endif
    <div class="card shadow-sm mb-4">
      <div class="card-header bg-white fw-semibold">Ubah Password</div>
      <div class="card-body">
        <p class="small text-muted">Password minimal 6 karakter, mengandung huruf besar, huruf kecil, dan angka.</p>
        <form method="POST" action="{{ route('password.update') }}" class="row g-3">
          @csrf
          <div class="col-12">
            <label class="form-label">Password Baru</label>
            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror">
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-12">
            <label class="form-label">Konfirmasi Password</label>
            <input type="password" name="password_confirmation" class="form-control">
          </div>
          <div class="col-12 d-flex gap-2">
            <button class="btn btn-primary">Ubah</button>
            <a href="{{ route('profile.show') }}" class="btn btn-outline-secondary">Batal</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
