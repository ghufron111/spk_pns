@extends('layouts.app')
@section('content')
<div class="row g-4">
  <div class="col-lg-8">
    <div class="card shadow-sm">
      <div class="card-header bg-white fw-semibold">Ubah Data Profil</div>
      <div class="card-body">
  <form method="POST" action="{{ route('profile.update') }}" class="row g-3" enctype="multipart/form-data">
          @csrf
          <div class="col-md-6">
            <label class="form-label">Nama</label>
            <input type="text" name="name" value="{{ old('name',$user->name) }}" class="form-control @error('name') is-invalid @enderror">
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-6">
            <label class="form-label">Email</label>
            <input type="email" name="email" value="{{ old('email',$user->email) }}" class="form-control @error('email') is-invalid @enderror">
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-12">
            <label class="form-label">Foto Profil (opsional)</label>
            <input id="avatarInput" type="file" name="avatar" accept="image/*" class="form-control @error('avatar') is-invalid @enderror">
            @error('avatar')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <small class="text-muted d-block mt-1">Pilih gambar lalu atur area potong. Preview diperbarui otomatis.</small>
            <input type="hidden" name="avatar_cropped" id="avatarCropped">
            <div class="row mt-3 g-4" id="cropperSection" style="display:none;">
              <div class="col-lg-6">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <div class="small text-muted">Area Potong</div>
                  <div class="btn-group btn-group-sm">
                    <button type="button" id="cropRotate" class="btn btn-outline-secondary" title="Rotasi 90°">↻ 90°</button>
                    <button type="button" id="cropReset" class="btn btn-outline-warning" title="Reset">Reset</button>
                  </div>
                </div>
                <div class="border rounded position-relative bg-light" style="aspect-ratio:1/1; overflow:hidden;">
                  <img id="cropperImage" alt="preview" style="max-width:100%;display:block;">
                </div>
                <!-- Zoom dihilangkan sesuai permintaan, fokus hanya geser & resize crop box -->
              </div>
              <div class="col-lg-6">
                <div class="small text-muted mb-2">Preview (final)</div>
                <div class="d-flex flex-wrap align-items-start gap-4">
                  <div class="d-flex flex-column align-items-center gap-2">
                    <canvas id="previewMain" width="260" height="260" class="shadow-sm" style="border-radius:50%;border:4px solid #e0ecff;box-shadow:0 0 0 4px #fff,0 0 0 6px #2563eb33;background:#f8fafc;"></canvas>
                    <span class="badge text-bg-primary-subtle text-primary-emphasis border small">130 x 130</span>
                  </div>
                  <div class="d-flex flex-column gap-3">
                    <div class="d-flex flex-column align-items-center gap-1">
                      <canvas id="previewMedium" width="120" height="120" style="border-radius:50%;background:#f1f5f9;border:2px solid #dbeafe;"></canvas>
                      <small class="text-muted">60x60</small>
                    </div>
                    <div class="d-flex flex-column align-items-center gap-1">
                      <canvas id="previewSmall" width="64" height="64" style="border-radius:50%;background:#f1f5f9;border:2px solid #e2e8f0;"></canvas>
                      <small class="text-muted">32x32</small>
                    </div>
                    <div class="mt-2"><span id="cropStatus" class="badge bg-secondary-subtle text-secondary-emphasis border">Belum diterapkan</span></div>
                  </div>
                </div>
              </div>
            </div>
            @if($user->avatar_path)
              <div class="mt-4 small">Avatar sekarang:</div>
              <x-avatar :user="$user" :size="70" :version="$user->updated_at?->timestamp" />
            @endif
          </div>
          <div class="col-12 d-flex gap-2">
            <button class="btn btn-primary">Simpan</button>
            <a href="{{ route('profile.show') }}" class="btn btn-outline-secondary">Batal</a>
          </div>
        </form>
        @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.css" rel="stylesheet"/>
        @endpush
        @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.js"></script>
        <script>
        (function(){
          const input = document.getElementById('avatarInput');
          const img = document.getElementById('cropperImage');
          const section = document.getElementById('cropperSection');
          const hidden = document.getElementById('avatarCropped');
          const rotateBtn = document.getElementById('cropRotate');
          const resetBtn = document.getElementById('cropReset');
          // Zoom slider dihapus – variabel tidak diperlukan
          const statusBadge = document.getElementById('cropStatus');
          const canvases = {
            main: document.getElementById('previewMain'),
            medium: document.getElementById('previewMedium'),
            small: document.getElementById('previewSmall')
          };
          let cropper = null;
          const MAIN_SIZE = 260; // akan dishare; avatar final dipakai ukuran ini lalu component scaling down

          function destroy(){ if(cropper){ cropper.destroy(); cropper=null; } }

            function drawToCanvas(c, src){
              const ctx = c.getContext('2d');
              const w = c.width, h = c.height; ctx.clearRect(0,0,w,h);
              const temp = new Image(); temp.onload = ()=>{ ctx.save(); ctx.beginPath(); ctx.arc(w/2,h/2,Math.min(w,h)/2,0,Math.PI*2); ctx.closePath(); ctx.clip(); ctx.drawImage(temp,0,0,w,h); ctx.restore(); }; temp.src = src;
            }

          function updateAll(){
            if(!cropper) return;
            const dataUrl = cropper.getCroppedCanvas({width:MAIN_SIZE,height:MAIN_SIZE,imageSmoothingQuality:'high'}).toDataURL('image/png');
            hidden.value = dataUrl; // auto set – tidak perlu klik "Gunakan"
            statusBadge.className = 'badge bg-success-subtle text-success-emphasis border';
            statusBadge.textContent = 'Siap disimpan';
            drawToCanvas(canvases.main, dataUrl);
            drawToCanvas(canvases.medium, dataUrl);
            drawToCanvas(canvases.small, dataUrl);
          }

          input.addEventListener('change', e => {
            const file = e.target.files?.[0];
            if(!file){ destroy(); section.style.display='none'; hidden.value=''; statusBadge.className='badge bg-secondary-subtle text-secondary-emphasis border'; statusBadge.textContent='Belum diterapkan'; return; }
            if(!file.type.startsWith('image/')) { alert('File bukan gambar'); input.value=''; return; }
            const reader = new FileReader();
            reader.onload = ev => { img.src = ev.target.result; section.style.display=''; destroy();
              cropper = new Cropper(img, {
                aspectRatio:1,
                viewMode:2,
                dragMode:'crop',
                movable:false,
                zoomable:false, // dinonaktifkan
                rotatable:false,
                scalable:false,
                autoCropArea:0.85,
                background:false,
                responsive:true,
                minCropBoxWidth:120,
                minCropBoxHeight:120,
                ready(){ updateAll(); },
                crop(){ updateAll(); }
              });
            };
            reader.readAsDataURL(file);
          });

          rotateBtn.addEventListener('click', () => { if(cropper){ cropper.rotate(90); updateAll(); } });
          resetBtn.addEventListener('click', () => { if(cropper){ cropper.reset(); updateAll(); } });
        })();
        </script>
        @endpush
      </div>
    </div>
  </div>
</div>
@endsection
