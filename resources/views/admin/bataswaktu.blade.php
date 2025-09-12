@extends('layouts.app')
@section('content')
<div class="alert alert-info">
    Halaman ini tidak digunakan lagi. Silakan kelola periode pengisian melalui menu Periode Pengisian.
    <div class="mt-2"><a class="btn btn-primary btn-sm" href="{{ route('admin.periode.settings') }}">Buka Pengaturan Periode</a></div>
    </div>
@endsection
