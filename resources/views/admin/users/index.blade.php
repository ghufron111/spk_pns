@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h4 mb-0">Kelola User</h2>
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Tambah User</a>
</div>
@if(session('success'))
    <div class="alert alert-success py-2 px-3 small mb-3">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger py-2 px-3 small mb-3">{{ $errors->first() }}</div>
@endif
<div class="card shadow-sm border-0">
    <div class="card-body pb-1">
        <form method="GET" class="row g-2 align-items-end mb-3">
            <div class="col-auto">
                <label class="form-label mb-1 small">Filter Role</label>
                <select name="role" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Semua</option>
                    @foreach($roles as $r)
                        <option value="{{ $r }}" @selected($filterRole===$r)>{{ ucfirst($r) }}</option>
                    @endforeach
                </select>
            </div>
            @if($filterRole)
            <div class="col-auto">
                <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
            @endif
        </form>
        <div class="table-responsive small">
            <table class="table table-sm align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>NIP</th>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Dibuat</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $u)
                        <tr>
                            <td>{{ $users->firstItem() + $loop->index }}</td>
                            <td>{{ $u->id }}</td>
                            <td>{{ $u->name }}</td>
                            <td>{{ $u->email }}</td>
                            <td><span class="badge text-bg-secondary">{{ ucfirst($u->role) }}</span></td>
                            <td>{{ $u->created_at?->format('d/m/Y') }}</td>
                            <td class="text-nowrap">
                                <a href="{{ route('admin.users.edit',$u) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                <form action="{{ route('admin.users.destroy',$u) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus user ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" @disabled(auth()->id()===$u->id || in_array($u->role,['admin','pimpinan']))>Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">Tidak ada data</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-2 small">{{ $users->links() }}</div>
    </div>
</div>
@endsection