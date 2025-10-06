@extends('layouts.app')
@section('content')
<div class="mb-3"><a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-outline-secondary">&larr; Kembali</a></div>
<h2 class="h4 mb-3">Tambah User</h2>
@if($errors->any())
    <div class="alert alert-danger small py-2 px-3 mb-3">
        <ul class="m-0 ps-3">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
@endif
<form method="POST" action="{{ route('admin.users.store') }}" class="card shadow-sm border-0">
    @csrf
    <div class="card-body">
        <div class="mb-3">
            <label class="form-label small">Nama</label>
            <input type="text" name="name" value="{{ old('name') }}" class="form-control form-control-sm" required>
        </div>
        <div class="mb-3">
            <label class="form-label small">Email</label>
            <input type="email" name="email" value="{{ old('email') }}" class="form-control form-control-sm" required>
        </div>
        <div class="mb-3">
            <label class="form-label small">NIP (opsional)</label>
            <input type="text" name="nip" value="{{ old('nip') }}" class="form-control form-control-sm" maxlength="30">
        </div>
        <div class="mb-3">
            <label class="form-label small">Role</label>
            <select name="role" class="form-select form-select-sm" required>
                <option value="">--Pilih--</option>
                @foreach($roles as $r)
                    <option value="{{ $r }}" @selected(old('role')===$r)>{{ ucfirst($r) }}</option>
                @endforeach
            </select>
        </div>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label small">Password</label>
                <input type="password" name="password" class="form-control form-control-sm" required>
                <div class="form-text small">Min 6 karakter kombinasi huruf besar, kecil, angka.</div>
            </div>
            <div class="col-md-6">
                <label class="form-label small">Konfirmasi Password</label>
                <input type="password" name="password_confirmation" class="form-control form-control-sm" required>
            </div>
        </div>
    </div>
    <div class="card-footer d-flex justify-content-end gap-2">
        <button type="reset" class="btn btn-sm btn-outline-secondary">Reset</button>
        <button type="submit" class="btn btn-sm btn-primary">Simpan</button>
    </div>
</form>
@endsection