@extends('layouts.app')
@section('content')
<div class="mb-3"><a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-outline-secondary">&larr; Kembali</a></div>
<h2 class="h4 mb-3">Edit User</h2>
@if($errors->any())
    <div class="alert alert-danger small py-2 px-3 mb-3">
        <ul class="m-0 ps-3">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
@endif
<form method="POST" action="{{ route('admin.users.update',$user) }}" class="card shadow-sm border-0">
    @csrf
    @method('PUT')
    <div class="card-body">
        <div class="mb-3">
            <label class="form-label small">Nama</label>
            <input type="text" name="name" value="{{ old('name',$user->name) }}" class="form-control form-control-sm" required>
        </div>
        <div class="mb-3">
            <label class="form-label small">Email</label>
            <input type="email" name="email" value="{{ old('email',$user->email) }}" class="form-control form-control-sm" required>
        </div>
        <div class="mb-3">
            <label class="form-label small">NIP (opsional)</label>
            <input type="text" name="nip" value="{{ old('nip',$user->nip) }}" class="form-control form-control-sm" maxlength="30">
        </div>
        <div class="mb-3">
            <label class="form-label small">Role</label>
            <select name="role" class="form-select form-select-sm" required>
                @foreach($roles as $r)
                    <option value="{{ $r }}" @selected(old('role',$user->role)===$r)>{{ ucfirst($r) }}</option>
                @endforeach
            </select>
        </div>
        <hr>
        <p class="small text-muted mb-2">Kosongkan password jika tidak ingin mengubah.</p>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label small">Password Baru</label>
                <input type="password" name="password" class="form-control form-control-sm">
            </div>
            <div class="col-md-6">
                <label class="form-label small">Konfirmasi Password Baru</label>
                <input type="password" name="password_confirmation" class="form-control form-control-sm">
            </div>
        </div>
    </div>
    <div class="card-footer d-flex justify-content-between align-items-center">
        <div class="small text-muted">Dibuat: {{ $user->created_at?->format('d/m/Y H:i') }}</div>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-sm btn-primary">Update</button>
        </div>
    </div>
</form>
@endsection