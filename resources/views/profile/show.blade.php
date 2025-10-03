@extends('layouts.app')
@section('content')
<div class="row g-4">
  <div class="col-lg-4">
    <div class="card shadow-sm">
      <div class="card-body text-center">
        <x-avatar :user="$user" :size="130" class="mx-auto mb-3" :version="$user->updated_at?->timestamp" />
        <h5 class="mb-1">{{ $user->name }}</h5>
        <div class="text-muted small mb-2">{{ ucfirst($user->role) }}</div>
        <span class="badge bg-primary-subtle text-primary-emphasis border">{{ $user->pangkat ?? '-' }}</span>
        <div class="mt-3 d-flex gap-2 justify-content-center">
          <a href="{{ route('profile.edit') }}" class="btn btn-sm btn-outline-primary">Ubah Data</a>
          <a href="{{ route('password.edit') }}" class="btn btn-sm btn-outline-secondary">Ubah Password</a>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-8">
    @if(session('success'))<div class="alert alert-success py-2 mb-3">{{ session('success') }}</div>@endif
    <div class="card shadow-sm">
      <div class="card-header bg-white fw-semibold">Detail Profil</div>
      <div class="card-body small">
        <dl class="row mb-0">
          <dt class="col-sm-3">Nama</dt><dd class="col-sm-9">{{ $user->name }}</dd>
          <dt class="col-sm-3">Email</dt><dd class="col-sm-9">{{ $user->email }}</dd>
          <dt class="col-sm-3">Pangkat</dt><dd class="col-sm-9">{{ $user->pangkat ?? '-' }} <span class="text-muted small">(tidak dapat diubah mandiri)</span></dd>
          <dt class="col-sm-3">Role</dt><dd class="col-sm-9">{{ $user->role }}</dd>
          <dt class="col-sm-3">Dibuat</dt><dd class="col-sm-9">{{ $user->created_at?->format('d/m/Y H:i') }}</dd>
          <dt class="col-sm-3">Terakhir Ubah</dt><dd class="col-sm-9">{{ $user->updated_at?->format('d/m/Y H:i') }}</dd>
        </dl>
      </div>
    </div>
  </div>
</div>
@endsection
