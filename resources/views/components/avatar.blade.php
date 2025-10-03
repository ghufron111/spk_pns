@props([
    'user',
    'size' => 70,
    'initialSize' => null,
    'ring' => true,
    'class' => '',
    // cache bust versi (misal updated_at->timestamp)
    'version' => null,
])
@php
    $u = $user;
    $dimension = (int) $size;
    $initialSize = $initialSize ? (int)$initialSize : (int)($dimension*0.42);
    $hasAvatar = $u && $u->avatar_path;
    $src = $hasAvatar ? route('profile.avatar') . ($version ? ('?v=' . $version) : '') : null;
@endphp
<div class="avatar-wrapper {{ $class }}" style="width:{{$dimension}}px;height:{{$dimension}}px;border-radius:50%;overflow:hidden;position:relative;{{ $ring && $hasAvatar ? 'box-shadow:0 0 0 3px #fff,0 0 0 5px #2563eb33;' : '' }}">
    @if($hasAvatar)
        <img src="{{ $src }}" alt="avatar" style="width:100%;height:100%;object-fit:cover;display:block;">
    @else
        <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:#eef;font-weight:600;color:#2563eb;font-size:{{$initialSize}}px;">
            {{ strtoupper(substr($u?->name ?? 'U',0,1)) }}
        </div>
    @endif
</div>
