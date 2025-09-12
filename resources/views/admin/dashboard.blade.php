@extends('layouts.app')
@section('content')
<div>
    <h2>Dashboard Admin</h2>
    @if(isset($notifikasiAdmin) && $notifikasiAdmin->count())
    <div class="mb-4">
        <h5>Aktivitas Terbaru Pegawai</h5>
        <form method="POST" action="{{ route('admin.notifikasi.deleteselected') }}" id="formDeleteSelected">
            @csrf
            <div class="d-flex mb-2 gap-2">
                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Hapus notifikasi terpilih?')">Hapus Terpilih</button>
                <form method="POST" action="{{ route('admin.notifikasi.clearall') }}" class="d-inline" id="formClearAll">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus semua notifikasi?')">Bersihkan Semua</button>
                </form>
            </div>
            <ul class="list-group small">
                @foreach($notifikasiAdmin as $n)
                    @php
                        preg_match('/Upload #(\d+)/', $n->pesan, $m);
                        $uploadId = $m[1] ?? null;
                    @endphp
                    <li class="list-group-item d-flex justify-content-between align-items-start @if(!$n->dibaca) list-group-item-info @endif">
                        <div class="form-check me-2 mt-1">
                            <input class="form-check-input" type="checkbox" name="ids[]" value="{{ $n->id }}">
                        </div>
                        <div class="flex-grow-1">
                            <span>{{ $n->pesan }}</span>
                        </div>
                        <div class="ms-2">
                            @if($uploadId)
                            <a href="{{ route('admin.upload.detail', $uploadId) }}" class="btn btn-sm btn-outline-primary" onclick="hapusNotifSatuan(event, {{ $n->id }})">Periksa</a>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        </form>
    </div>
    @endif
        <p class="text-muted">Ringkasan aktivitas terbaru pegawai. Gunakan menu di samping untuk validasi dan proses SPK.</p>
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        <script>
            function hapusNotifSatuan(e, id){
                // Ketika klik periksa, otomatis hapus notifikasi tersebut via fetch
                fetch("{{ route('admin.notifikasi.deleteselected') }}", {
                    method:'POST',
                    headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Content-Type':'application/json'},
                    body: JSON.stringify({ids:[id]})
                }).then(()=>{}).catch(()=>{});
            }
        </script>
</div>
@endsection
