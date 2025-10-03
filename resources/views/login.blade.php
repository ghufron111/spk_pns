@extends('layouts.guest')

@section('content')
<style>
    body {
        background: url('{{ asset('images/kantor_camat_kota_kudus.jpg') }}') no-repeat center center fixed;
        background-size: cover;
    }

    /* Overlay agar background terlihat lebih gelap dan teks lebih mudah dibaca */
    .login-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.4); /* opsional */
        z-index: -1;
    }

    .login-card {
        background: rgba(255, 255, 255, 0.15); /* transparan */
        backdrop-filter: blur(12px); /* efek blur */
        -webkit-backdrop-filter: blur(12px); /* untuk Safari */
        border-radius: 16px;
        padding: 2rem;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        color: #fff; /* supaya teks terlihat jelas */
    }

    .login-card h2,
    .login-card label {
        color: #fff; /* ubah warna teks form */
    }

    .form-control {
        background: rgba(255, 255, 255, 0.2);
        border: none;
        color: #fff;
    }

    .form-control::placeholder {
        color: rgba(255, 255, 255, 0.7);
    }

    .form-control:focus {
        background: rgba(255, 255, 255, 0.3);
        box-shadow: none;
        color: #fff;
    }
</style>

<div class="login-overlay"></div>

<div class="d-flex justify-content-center align-items-center" style="min-height: 100vh;">
    <div class="login-card" style="max-width:420px; width:100%;">
        <h2 class="mb-4 text-center">Login</h2>
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
                <button type="submit" class="btn btn-light">Login</button>
                <a href="{{ route('register') }}" class="btn btn-outline-light">Daftar Akun Pegawai</a>
            </div>
        </form>
    </div>
</div>
@endsection
