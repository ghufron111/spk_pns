<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SPK Kenaikan Pangkat PNS</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400..700;1,400..700&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    @stack('styles')
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark app-navbar fixed-top">
        <div class="container-fluid">
            <div class="d-flex align-items-center gap-2">
                @auth
                <!-- Burger at left for small screens -->
                <button class="btn btn-outline-light d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#appSidebarOffcanvas" aria-controls="appSidebarOffcanvas" aria-label="Menu" style="padding:.25rem .5rem; line-height:1;">
                    <i class="bi bi-list" style="font-size:1.2rem;"></i>
                </button>
                @endauth
                <a class="navbar-brand d-flex align-items-center gap-2 m-0" href="#" style="padding:0;">
                    <span class="logo-circle">SPK</span>
                    <span>SPK Kenaikan Pangkat PNS</span>
                </a>
            </div>
            @auth
            <div class="ms-auto d-flex align-items-center">
                <ul class="navbar-nav align-items-center gap-lg-3 m-0">
                    <li class="nav-item dropdown" style="z-index:1050; border-radius:6px;">
                        <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            @php($u = auth()->user())
                            <x-avatar :user="$u" :size="34" :ring="false" class="flex-shrink-0" :version="$u->updated_at?->timestamp" />
                            <span class="small fw-semibold text-white">{{ $u->name }}</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm small">
                            <li class="px-3 pt-3 pb-2 d-flex flex-column align-items-center text-center" style="min-width:230px;">
                                <div class="mb-2" style="line-height:0;">
                                    <x-avatar :user="$u" :size="80" :version="$u->updated_at?->timestamp" />
                                </div>
                                <div class="fw-semibold mb-0">{{ $u->name }}</div>
                                <div class="text-muted small mb-1">{{ ucfirst($u->role) }}</div>
                                <div class="mt-1 mb-1"><span class="badge bg-primary-subtle text-primary-emphasis border">{{ $u->pangkat ?? '-' }}</span></div>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a href="{{ route('profile.show') }}" class="dropdown-item d-flex align-items-center gap-2">
                                    <i class="bi bi-person-circle"></i> <span>Profil</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('password.edit') }}" class="dropdown-item d-flex align-items-center gap-2">
                                    <i class="bi bi-key-fill"></i> <span>Ubah Password</span>
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}" class="px-3 py-2 m-0">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-danger w-100">Logout</button>
                                </form>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
            @endauth
        </div>
    </nav>
    @auth
    <aside class="app-sidebar bg-white border-end p-0 d-none d-lg-block">
        <div class="p-3 border-bottom">
            <strong>Menu</strong>
        </div>
        <ul class="nav flex-column mb-4">
            @if(auth()->user()->role === 'pegawai')
                <li class="nav-item"><a href="{{ route('pegawai.dashboard') }}" class="nav-link px-3">Dashboard</a></li>
                <li class="nav-item"><a href="{{ route('pegawai.berkas') }}" class="nav-link px-3">Status Berkas</a></li>
                <li class="nav-item"><a href="{{ route('pegawai.upload') }}" class="nav-link px-3">Upload Berkas</a></li>
            @elseif(auth()->user()->role === 'admin')
                <li class="nav-item"><a href="{{ route('admin.dashboard') }}" class="nav-link px-3">Dashboard</a></li>
                <li class="nav-item"><a href="{{ route('admin.validasi.index') }}" class="nav-link px-3">Validasi Berkas</a></li>
                <li class="nav-item"><a href="{{ route('admin.spk.index') }}" class="nav-link px-3">Proses SPK</a></li>
                <li class="nav-item"><a href="{{ route('admin.periode.settings') }}" class="nav-link px-3">Periode Pengisian</a></li>
                <li class="nav-item"><a href="{{ route('admin.dokumen.settings') }}" class="nav-link px-3">Pengaturan Dokumen</a></li>
                <li class="nav-item"><a href="{{ route('admin.users.index') }}" class="nav-link px-3">Kelola User</a></li>
            @elseif(auth()->user()->role === 'pimpinan')
                <li class="nav-item"><a href="{{ route('pimpinan.dashboard') }}" class="nav-link px-3">Dashboard</a></li>
                <li class="nav-item"><a href="{{ route('pimpinan.considerations') }}" class="nav-link px-3">Dipertimbangkan</a></li>
                <li class="nav-item"><a href="{{ route('pimpinan.approvals.index') }}" class="nav-link px-3">Persetujuan Kenaikan</a></li>
                <li class="nav-item"><a href="{{ route('pimpinan.pegawai.index') }}" class="nav-link px-3">Data Pegawai</a></li>
            @endif
        </ul>
    </aside>
    <!-- Offcanvas Sidebar for small screens -->
    <div class="offcanvas offcanvas-end d-lg-none" tabindex="-1" id="appSidebarOffcanvas" aria-labelledby="appSidebarLabel" style="--bs-offcanvas-width: 260px;">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="appSidebarLabel">Menu</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body p-0">
            <ul class="nav flex-column mb-4">
                @if(auth()->user()->role === 'pegawai')
                    <li class="nav-item"><a href="{{ route('pegawai.dashboard') }}" class="nav-link px-3 py-2" data-bs-dismiss="offcanvas">Dashboard</a></li>
                    <li class="nav-item"><a href="{{ route('pegawai.berkas') }}" class="nav-link px-3 py-2" data-bs-dismiss="offcanvas">Status Berkas</a></li>
                    <li class="nav-item"><a href="{{ route('pegawai.upload') }}" class="nav-link px-3 py-2" data-bs-dismiss="offcanvas">Upload Berkas</a></li>
                @elseif(auth()->user()->role === 'admin')
                    <li class="nav-item"><a href="{{ route('admin.dashboard') }}" class="nav-link px-3 py-2" data-bs-dismiss="offcanvas">Dashboard</a></li>
                    <li class="nav-item"><a href="{{ route('admin.validasi.index') }}" class="nav-link px-3 py-2" data-bs-dismiss="offcanvas">Validasi Berkas</a></li>
                    <li class="nav-item"><a href="{{ route('admin.spk.index') }}" class="nav-link px-3 py-2" data-bs-dismiss="offcanvas">Proses SPK</a></li>
                    <li class="nav-item"><a href="{{ route('admin.periode.settings') }}" class="nav-link px-3 py-2" data-bs-dismiss="offcanvas">Periode Pengisian</a></li>
                    <li class="nav-item"><a href="{{ route('admin.dokumen.settings') }}" class="nav-link px-3 py-2" data-bs-dismiss="offcanvas">Pengaturan Dokumen</a></li>
                    <li class="nav-item"><a href="{{ route('admin.users.index') }}" class="nav-link px-3 py-2" data-bs-dismiss="offcanvas">Kelola User</a></li>
                @elseif(auth()->user()->role === 'pimpinan')
                    <li class="nav-item"><a href="{{ route('pimpinan.dashboard') }}" class="nav-link px-3 py-2" data-bs-dismiss="offcanvas">Dashboard</a></li>
                    <li class="nav-item"><a href="{{ route('pimpinan.considerations') }}" class="nav-link px-3 py-2" data-bs-dismiss="offcanvas">Dipertimbangkan</a></li>
                    <li class="nav-item"><a href="{{ route('pimpinan.approvals.index') }}" class="nav-link px-3 py-2" data-bs-dismiss="offcanvas">Persetujuan Kenaikan</a></li>
                    <li class="nav-item"><a href="{{ route('pimpinan.pegawai.index') }}" class="nav-link px-3 py-2" data-bs-dismiss="offcanvas">Data Pegawai</a></li>
                @endif
            </ul>
        </div>
    </div>
    <main class="app-content">
        <div class="content-inner py-4">
            <div class="container-fluid">
                @yield('content')
            </div>
        </div>
    </main>
    @else
    <main class="public-content">
        <div class="container py-4">
            @yield('content')
        </div>
    </main>
    @endauth
    <style>
    body { font-family: 'Roboto', sans-serif; }
    h1, h2, h3, h4, h5, h6, .font-serif, .navbar-brand { font-family: 'Lora', serif; }
    .nav-link { color:#333; }
    .nav-link:hover { background:#a0a0a0; border-radius:15px; color:#fff; }

    /* Layout constants */
    :root { --navbar-height:56px; --sidebar-width:220px; }

    body { padding-top: var(--navbar-height); }
    .navbar.fixed-top { box-shadow: 0 2px 4px rgba(0,0,0,.1); z-index:1030; }
    .app-navbar { min-height: var(--navbar-height); padding-top:.25rem; padding-bottom:.25rem; }
    .app-navbar .container-fluid{ min-height: var(--navbar-height); }

    /* Sidebar */
    .app-sidebar { position: fixed; top: var(--navbar-height); left:0; bottom:0; width: var(--sidebar-width); overflow-y:auto; }
    .app-sidebar .nav-link { border-radius:0; }

    /* Main content */
    .app-content { margin-left: var(--sidebar-width); height: calc(100vh - var(--navbar-height)); overflow-y:auto; }
    .content-inner { min-width: 100%; padding: 1.25rem 1.5rem; }
    .app-sidebar > .border-bottom { padding-left:1rem!important; padding-right:1rem!important; }
    .app-sidebar .nav-link { padding-top:.55rem; padding-bottom:.55rem; }

    /* Public (no auth) content adjusts only for navbar */
    .public-content { padding-top: var(--navbar-height); }

    /* Responsive: collapse fixed sidebar on small screens */
    @media (max-width: 991.98px) {
        .app-sidebar { position: static; width:100%; height:auto; max-height:none; }
        .app-content { margin-left:0; height:auto; min-height: calc(100vh - var(--navbar-height)); }
    }

    .app-navbar { background: linear-gradient(90deg,#0d6efd,#2563eb); box-shadow: 0 2px 4px rgba(0,0,0,.08); transition: box-shadow .25s, backdrop-filter .25s; }
    .scrolled .app-navbar { box-shadow: 0 4px 12px rgba(0,0,0,.18); backdrop-filter: blur(4px); }
    .logo-circle { display:inline-flex; align-items:center; justify-content:center; width:38px; height:38px; background:rgba(255,255,255,.15); border:2px solid rgba(255,255,255,.4); border-radius:50%; font-weight:700; font-size:.9rem; letter-spacing:.5px; }
    .user-initial { width:34px; height:34px; display:flex; align-items:center; justify-content:center; font-size:.9rem; }
    .dropdown-menu-end { min-width: 220px; }
    @media (max-width: 991.98px){
        .navbar .badge{ display:none; }
    }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
    <script>
        document.addEventListener('scroll', ()=>{
            if(window.scrollY>4) document.body.classList.add('scrolled'); else document.body.classList.remove('scrolled');
        });
    </script>
</body>
</html>
