@extends('layouts.guest')
@section('content')
<div class="mx-auto bg-white p-4 rounded shadow-sm" style="max-width:420px;">
    <h2 class="mb-4">Login</h2>
    <form method="POST" action="{{ url('/login') }}">
        @csrf
        <div class="mb-3">
            <label for="login" class="form-label">Email atau NIP</label>
            <input type="text" class="form-control" id="login" name="login" value="{{ old('login') }}" required autofocus placeholder="Masukkan Email atau NIP">
            @error('login')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control" id="password" name="password" placeholder="Masukkan Password" required>
            @error('password')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
        <div class="d-grid gap-2">
            <button type="submit" class="btn btn-primary">Login</button>
            <a href="{{ route('register') }}" class="btn btn-outline-secondary">Daftar Akun Pegawai</a>
        </div>
    </form>
</div>
@endsection
