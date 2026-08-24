<!DOCTYPE html>
<html lang="id" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @if (config('demo.enabled'))<meta name="robots" content="noindex,nofollow">@endif
    <link rel="icon" href="{{ $faviconUrl }}">
    <script>(function(){try{var t=localStorage.getItem('lms-theme');if(t){document.documentElement.setAttribute('data-bs-theme',t);}}catch(e){}})();</script>
    <title>{{ $appName }}</title>
    <link rel="stylesheet" href="{{ asset('tabler/css/tabler.min.css') }}">
    <link rel="stylesheet" href="{{ asset('tabler/css/tabler-vendors.min.css') }}">
    <link rel="stylesheet" href="{{ asset('tabler/css/tabler-icons.min.css') }}">
    <style>
        [x-cloak]{display:none !important;}
        /* Logo terang/gelap: tampilkan sesuai tema aktif */
        .brand-logo-dark{display:none;}
        [data-bs-theme="dark"] .brand-logo-light{display:none;}
        [data-bs-theme="dark"] .brand-logo-dark{display:inline-block;}
        /* Build Tabler ini memaksa corner-shape:squircle + border-radius ×2.5 (dalam @supports).
           Pada card-header/footer nilai radius-nya invalid sehingga tidak match dengan .card →
           sudut tampak buram. Kembalikan ke sudut bulat normal & batalkan inflasi radius. */
        *{corner-shape:round !important;}
        @supports (corner-shape: squircle) {
            .card{border-radius:var(--tblr-card-border-radius) !important;}
            .card-header:first-child{border-radius:var(--tblr-card-inner-border-radius) var(--tblr-card-inner-border-radius) 0 0 !important;}
            .card-footer:last-child{border-radius:0 0 var(--tblr-card-inner-border-radius) var(--tblr-card-inner-border-radius) !important;}
            .modal-content{border-radius:var(--tblr-modal-border-radius, var(--tblr-border-radius-lg)) !important;}
            .dropdown-menu{border-radius:var(--tblr-border-radius) !important;}
            .alert{border-radius:var(--tblr-border-radius) !important;}
        }
        body{transition:background-color .2s ease, color .2s ease;}
        .navbar .nav-link,.dropdown-item,.mobile-more-link{transition:color .15s ease,background-color .15s ease,transform .15s ease;}
        .navbar .nav-link:hover{color:var(--tblr-primary);background:rgba(var(--tblr-primary-rgb),.08);text-decoration:none;}
        .dropdown-item:hover,.dropdown-item:focus{color:var(--tblr-primary);background:rgba(var(--tblr-primary-rgb),.08);text-decoration:none;transform:translateX(2px);}
        .card-lift{transition:transform .15s ease, box-shadow .15s ease;}
        .card-lift:hover{transform:translateY(-3px);box-shadow:0 .5rem 1.25rem rgba(0,0,0,.13);}
        /* Kartu kelas diarsipkan: redupkan isi tapi tombol tetap bisa diklik */
        .course-archived .card-body{opacity:.6;}
        /* Token panjang dalam <code> jangan memaksa lebar (overflow horizontal di HP) */
        code{overflow-wrap:anywhere;word-break:break-word;}
        .card,.card-body,.card-header,.card-footer,.list-group-item{min-width:0;}
        .card-title,.page-title,.fw-bold,.fw-semibold{overflow-wrap:anywhere;}
        .content-prose,.content-prose *{max-width:100%;overflow-wrap:anywhere;word-break:break-word;}
        .content-prose img,.content-prose iframe,.content-prose video{max-width:100%;height:auto;}
        .content-prose pre{max-width:100%;overflow:auto;white-space:pre-wrap;}
        .responsive-item-main{min-width:0;flex:1 1 auto;}
        .responsive-item-title{overflow-wrap:anywhere;word-break:break-word;}
        .responsive-item-meta{display:flex;flex-wrap:wrap;gap:.15rem .65rem;min-width:0;}
        /* Sub-nav kelas: 1 baris yang bisa digeser di layar kecil */
        .lms-subnav{scrollbar-width:thin;-ms-overflow-style:none;}
        .lms-subnav::-webkit-scrollbar{height:4px;}
        .lms-subnav::-webkit-scrollbar-thumb{background:var(--tblr-border-color);border-radius:4px;}
        /* Judul hero kelas: lebih kecil di HP */
        .course-hero-title{font-size:1.4rem;line-height:1.2;overflow-wrap:anywhere;}
        @media (min-width:576px){.w-sm-auto{width:auto !important;}}
        @media (min-width:768px){.w-md-auto{width:auto !important;}}
        @media (min-width:768px){.course-hero-title{font-size:1.9rem;}}
        /* Skeleton loading (shimmer) */
        .skeleton{position:relative;overflow:hidden;background:var(--tblr-border-color,#e6e7e9);border-radius:var(--tblr-border-radius,4px);}
        .skeleton::after{content:"";position:absolute;inset:0;transform:translateX(-100%);background:linear-gradient(90deg,transparent,rgba(255,255,255,.45),transparent);animation:skeleton-shimmer 1.3s infinite;}
        [data-bs-theme="dark"] .skeleton::after{background:linear-gradient(90deg,transparent,rgba(255,255,255,.08),transparent);}
        @keyframes skeleton-shimmer{100%{transform:translateX(100%);}}
        #nprogress{position:fixed;top:0;left:0;height:3px;width:0;background:var(--tblr-primary,#206bc4);z-index:2000;opacity:0;transition:width .25s ease,opacity .3s ease;}
        #nprogress.on{opacity:1;}
        :root, [data-bs-theme="light"], [data-bs-theme="dark"]{
            --tblr-primary: {{ $themeColor }};
            --tblr-primary-rgb: {{ $themeColorRgb }};
            --tblr-primary-darken: color-mix(in srgb, {{ $themeColor }}, #000 12%);
        }
        /* ============ Penghalusan tampilan mobile (≤575px) ============ */
        @media (max-width: 575.98px) {
            html,body{max-width:100%;overflow-x:hidden;}
            .page-body>.container-xl,.page-header .container-xl{max-width:100%;}
            .row>*{min-width:0;}
            .card-header{padding:.85rem 1rem;gap:.5rem;flex-wrap:wrap;}
            .card-header .card-actions{max-width:100%;margin-left:auto;}
            .card-body{padding:1rem;}
            .btn-list{max-width:100%;flex-wrap:wrap;}
            .btn-list>*{min-width:0;}
            .table-responsive{max-width:100%;margin:0;overscroll-behavior-x:contain;scrollbar-width:thin;}
            .table-responsive>.table{margin-bottom:0;}
            .table th,.table td{max-width:16rem;overflow-wrap:anywhere;}
            .modal-dialog{margin:0;min-height:100%;align-items:stretch;}
            .modal-content{min-height:100dvh;border:0;border-radius:0 !important;}
            .modal-body{overflow-y:auto;}
            .modal-footer{position:sticky;bottom:0;z-index:2;padding:.75rem 1rem calc(.75rem + env(safe-area-inset-bottom));background:var(--tblr-bg-surface);flex-wrap:wrap;}
            .modal-footer .btn{flex:1 1 auto;min-height:2.75rem;}
            .mobile-action-stack{flex-direction:column !important;align-items:stretch !important;}
            .mobile-action-stack>.btn,.mobile-action-stack>button,.mobile-action-stack>select,.mobile-action-stack>.form-control{width:100%;}
            .responsive-item-title{line-height:1.35;}
            .responsive-item-meta{font-size:.75rem;}
            .content-prose{font-size:.9rem;line-height:1.6;}
            body.student-mobile-ui{background:var(--tblr-bg-surface-secondary,#f6f8fb);}
            body.student-mobile-ui .page{min-height:100dvh;padding-bottom:calc(4.75rem + env(safe-area-inset-bottom));}
            body.student-mobile-ui .navbar{display:none !important;}
            body.student-mobile-ui .navbar .container-xl{padding-left:1rem;padding-right:.75rem;}
            body.student-mobile-ui .navbar-toggler,
            body.student-mobile-ui #navbar-menu{display:none !important;}
            body.student-mobile-ui .navbar-brand{max-width:58%;margin:0;}
            body.student-mobile-ui .navbar-brand .brand-logo{max-width:2rem;object-fit:contain;}
            body.student-mobile-ui .navbar-brand .fs-2{font-size:1rem !important;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
            body.student-mobile-ui .navbar-nav .nav-link{min-width:2.75rem;min-height:2.75rem;justify-content:center;}
            body.student-mobile-ui .page-wrapper{min-height:0;flex:1 1 auto;}
            body.student-mobile-ui .page-body{margin-top:.75rem;}
            body.student-mobile-ui .page-body>.container-xl,
            body.student-mobile-ui .page-header .container-xl{padding-left:1rem;padding-right:1rem;}
            body.student-mobile-ui .page-header{padding-top:1rem;padding-bottom:0;}
            body.student-mobile-ui .page-pretitle{font-size:.7rem;letter-spacing:.04em;}
            body.student-mobile-ui .page-title{font-size:1.35rem;line-height:1.25;}
            body.student-mobile-ui .footer{display:none;}
            body.student-mobile-ui .card{border-radius:.875rem !important;box-shadow:0 1px 3px rgba(35,46,60,.06);}
            body.student-mobile-ui .card-body,
            body.student-mobile-ui .list-group-item{overflow-wrap:anywhere;}
            body.student-mobile-ui .badge{max-width:100%;overflow:hidden;text-overflow:ellipsis;vertical-align:middle;}
            body.student-mobile-ui .btn:not(.btn-icon){max-width:100%;white-space:normal;}
            body.student-mobile-ui input,
            body.student-mobile-ui select,
            body.student-mobile-ui textarea{max-width:100%;}
            body.student-mobile-ui .alert,
            body.student-mobile-ui .form-hint,
            body.student-mobile-ui .dropdown-item{overflow-wrap:anywhere;}
            body.student-mobile-ui .d-flex>div,
            body.student-mobile-ui .d-flex>span{min-width:0;}
            body.student-mobile-ui .empty{padding:1rem .5rem;}
            body.student-mobile-ui .empty .empty-icon{margin-bottom:.5rem !important;}
            body.student-mobile-ui .empty .empty-icon .avatar{width:2.75rem;height:2.75rem;}
            body.student-mobile-ui .empty .empty-icon i{font-size:1.4rem !important;}
            body.student-mobile-ui .empty .empty-title{margin-bottom:.25rem;font-size:.95rem;}
            body.student-mobile-ui .empty .empty-subtitle{margin-bottom:.75rem;font-size:.78rem;}
            body.student-mobile-ui .list-group-item{min-height:4rem;padding:.8rem 1rem;}
            body.student-mobile-ui .btn:not(.btn-sm):not(.btn-link){min-height:2.75rem;}
            body.student-mobile-ui .toast-container{bottom:calc(4.75rem + env(safe-area-inset-bottom)) !important;padding:.75rem !important;width:100%;}
            .page-title{font-size:1.2rem;}
            .page-header{padding-top:.75rem;padding-bottom:.25rem;}
            /* Aksi halaman: turun penuh & boleh membungkus rapi di bawah judul */
            .page-header .btn-list{width:100%;flex-wrap:wrap;}
            /* Hero kelas rapi saat elemen menumpuk */
            .course-hero .card-body{gap:.75rem;}
            /* Target sentuh tombol ikon lebih nyaman */
            .btn-icon{min-width:2.25rem;min-height:2.25rem;}
        }
        .mobile-bottom-nav{display:none;}
        @media (max-width:575.98px){
            .mobile-bottom-nav{display:grid;grid-template-columns:repeat(4,1fr);position:fixed;z-index:1080;left:0;right:0;bottom:0;min-height:4.25rem;padding:.35rem .35rem calc(.35rem + env(safe-area-inset-bottom));background:var(--tblr-bg-surface);border-top:1px solid var(--tblr-border-color);box-shadow:0 -.25rem 1rem rgba(35,46,60,.08);}
            .mobile-bottom-nav__item{position:relative;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:.1rem;min-height:3.55rem;border:0;background:transparent;color:var(--tblr-secondary-color);font-size:.68rem;font-weight:600;text-decoration:none;border-radius:.75rem;}
            .mobile-bottom-nav__item i{font-size:1.45rem;line-height:1;}
            .mobile-bottom-nav__item.active{color:var(--tblr-primary);}
            .mobile-bottom-nav__item{transition:color .15s ease,background-color .15s ease,transform .15s ease;}
            .mobile-bottom-nav__item:hover{color:var(--tblr-primary);background:rgba(var(--tblr-primary-rgb),.08);text-decoration:none;transform:translateY(-1px);}
            .mobile-bottom-nav__badge{position:absolute;top:.15rem;left:calc(50% + .45rem);min-width:1rem;padding:.05rem .25rem;border-radius:2rem;background:var(--tblr-danger);color:#fff;font-size:.58rem;text-align:center;}
            .mobile-more-sheet{z-index:1090;height:auto !important;max-height:82vh;border-radius:1.25rem 1.25rem 0 0;}
            .mobile-more-sheet .offcanvas-header{padding:1rem 1.25rem .5rem;}
            .mobile-more-profile{display:flex;align-items:center;gap:.75rem;padding:.85rem;border-radius:.875rem;background:var(--tblr-bg-surface-secondary);}
            .mobile-more-section-title{margin:.9rem 0 .4rem;color:var(--tblr-secondary-color);font-size:.68rem;font-weight:700;letter-spacing:.05em;text-transform:uppercase;}
            .mobile-more-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:.75rem;}
            .mobile-more-link{display:flex;min-width:0;min-height:5.25rem;padding:.75rem .35rem;flex-direction:column;align-items:center;justify-content:center;gap:.45rem;border:1px solid var(--tblr-border-color);border-radius:.875rem;color:inherit;text-decoration:none;text-align:center;font-size:.75rem;font-weight:600;}
            .mobile-more-link i{font-size:1.5rem;color:var(--tblr-primary);}
            .mobile-more-link:hover,.mobile-more-link:focus{color:var(--tblr-primary);background:rgba(var(--tblr-primary-rgb),.08);border-color:rgba(var(--tblr-primary-rgb),.35);text-decoration:none;transform:translateY(-2px);}
            .mobile-more-logout{display:flex;align-items:center;justify-content:center;gap:.5rem;width:100%;min-height:2.75rem;margin-top:1rem;border:1px solid rgba(var(--tblr-danger-rgb),.25);border-radius:.875rem;background:transparent;color:var(--tblr-danger);font-weight:600;}
        }
        @media (prefers-reduced-motion:reduce){
            .navbar .nav-link,.dropdown-item,.mobile-bottom-nav__item,.mobile-more-link{transition:none !important;transform:none !important;}
        }
    </style>
    @stack('styles')
</head>
<body @class(['student-mobile-ui' => auth()->user()?->isMahasiswa()])>
<div id="nprogress"></div>
<div class="page">
    @php($user = auth()->user())

    @if (config('demo.enabled'))
        <div class="bg-yellow-lt border-bottom text-center py-1 px-2 small d-print-none">
            <i class="ti ti-flask me-1"></i><strong>Mode Demo</strong> — Anda menjelajah aplikasi contoh. Perubahan bersifat sementara &amp; data direset berkala.
            <a href="#" class="ms-1 fw-bold" onclick="event.preventDefault();document.getElementById('demo-logout-form').submit();">Keluar / ganti peran</a>
            <form id="demo-logout-form" method="POST" action="{{ route('logout') }}" class="d-none">@csrf</form>
        </div>
    @endif

    {{-- ===================== HEADER ATAS (brand + aksi) ===================== --}}
    <header class="navbar navbar-expand-md d-print-none sticky-top">
        <div class="container-xl">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu" aria-controls="navbar-menu" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <a href="{{ route('dashboard') }}" class="navbar-brand {{ $hasLogoDark ? '' : 'navbar-brand-autodark' }} d-none-navbar-horizontal pe-0 pe-md-3 d-flex align-items-center">
                <img src="{{ $logoUrl }}" height="{{ $logoHeight }}" alt="{{ $headerTitle }}" class="brand-logo {{ $hasLogoDark ? 'brand-logo-light' : '' }} {{ $hideHeaderTitle ? '' : 'me-2' }}" style="max-height:{{ $logoHeight }}px;width:auto;">
                @if ($hasLogoDark)
                    <img src="{{ $logoDarkUrl }}" height="{{ $logoHeight }}" alt="{{ $headerTitle }}" class="brand-logo brand-logo-dark {{ $hideHeaderTitle ? '' : 'me-2' }}" style="max-height:{{ $logoHeight }}px;width:auto;">
                @endif
                @unless ($hideHeaderTitle)
                    <span class="fw-bold fs-2 text-primary">{{ $headerTitle }}</span>
                @endunless
            </a>

            <div class="navbar-nav flex-row order-md-last">
                {{-- Theme toggle --}}
                <div class="nav-item d-none d-md-flex me-2">
                    <a href="#" class="nav-link px-0 hide-theme-dark" title="Mode gelap" aria-label="Aktifkan mode gelap" data-bs-toggle="tooltip" onclick="document.documentElement.setAttribute('data-bs-theme','dark');try{localStorage.setItem('lms-theme','dark');}catch(e){}return false;">
                        <i class="ti ti-moon fs-2"></i>
                    </a>
                    <a href="#" class="nav-link px-0 hide-theme-light" title="Mode terang" aria-label="Aktifkan mode terang" data-bs-toggle="tooltip" onclick="document.documentElement.setAttribute('data-bs-theme','light');try{localStorage.setItem('lms-theme','light');}catch(e){}return false;">
                        <i class="ti ti-sun fs-2"></i>
                    </a>
                </div>

                {{-- Notification bell --}}
                <div class="nav-item dropdown me-2">
                    <a href="#" class="nav-link px-0 position-relative" data-bs-toggle="dropdown" tabindex="-1" aria-label="Notifikasi">
                        <i class="ti ti-bell fs-2"></i>
                        <span id="nav-bell-badge"
                              @class([
                                  'badge bg-red text-white position-absolute top-0 start-100 translate-middle rounded-pill',
                                  'd-none' => ($navUnreadCount ?? 0) < 1,
                              ])
                              style="font-size:.6rem; padding:.2em .4em;">{{ ($navUnreadCount ?? 0) > 9 ? '9+' : ($navUnreadCount ?? 0) }}</span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end dropdown-menu-card">
                        <div class="card">
                            <div class="card-header d-flex">
                                <h3 class="card-title">Notifikasi</h3>
                                @if (($navUnreadCount ?? 0) > 0)
                                    <form method="POST" action="{{ route('notifications.readAll') }}" class="ms-auto">
                                        @csrf
                                        <button class="btn btn-sm btn-link p-0">Tandai semua dibaca</button>
                                    </form>
                                @endif
                            </div>
                            <div class="list-group list-group-flush" style="max-height:320px;overflow:auto;">
                                @forelse ($navNotifications ?? [] as $n)
                                    <a href="{{ route('notifications.read', $n) }}" class="list-group-item list-group-item-action {{ $n->isUnread() ? '' : 'text-secondary' }}">
                                        <div class="d-flex align-items-center">
                                            @if ($n->isUnread())<span class="status-dot status-dot-animated bg-red me-2"></span>@endif
                                            <div>
                                                <div class="fw-bold">{{ $n->title }}</div>
                                                @if ($n->message)<div class="small text-secondary">{{ $n->message }}</div>@endif
                                                <div class="small text-secondary">{{ $n->created_at->diffForHumans() }}</div>
                                            </div>
                                        </div>
                                    </a>
                                @empty
                                    <div class="list-group-item text-secondary text-center py-4">Belum ada notifikasi.</div>
                                @endforelse
                            </div>
                            <div class="card-footer text-center p-2">
                                <a href="{{ route('notifications.index') }}" class="btn btn-sm w-100">Lihat semua</a>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- User dropdown --}}
                <div class="nav-item dropdown">
                    <a href="#" class="nav-link d-flex lh-1 p-0 px-2" data-bs-toggle="dropdown" aria-label="Buka menu pengguna">
                        <span class="avatar avatar-sm rounded bg-primary-lt" @if ($user->avatarUrl()) style="background-image:url('{{ $user->avatarUrl() }}')" @endif>@unless ($user->avatarUrl()){{ $user->initial() }}@endunless</span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                        <div class="dropdown-header d-flex align-items-center gap-2 py-2">
                            <x-avatar :name="$user->name" :url="$user->avatarUrl()" />
                            <div class="text-truncate">
                                <div class="fw-bold text-truncate" style="max-width:160px">{{ $user->name }}</div>
                                <div class="small text-secondary text-capitalize">{{ $user->role }}</div>
                            </div>
                        </div>
                        <div class="dropdown-divider"></div>
                        <a href="{{ route('profile.edit') }}" class="dropdown-item"><i class="ti ti-user me-2"></i>Profil & Kata Sandi</a>
                        <a href="{{ route('panduan') }}" class="dropdown-item"><i class="ti ti-help-circle me-2"></i>Panduan</a>
                        <div class="dropdown-divider"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger"><i class="ti ti-logout me-2"></i>Keluar</button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Menu (condensed: satu baris dengan brand & aksi) --}}
            <div class="collapse navbar-collapse" id="navbar-menu">
                <div class="d-flex flex-column flex-md-row flex-fill align-items-stretch align-items-md-center">
                    <ul class="navbar-nav">
                        <li class="nav-item {{ request()->routeIs('dashboard*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('dashboard') }}">
                                <span class="nav-link-icon"><i class="ti ti-home"></i></span>
                                <span class="nav-link-title">Dashboard</span>
                            </a>
                        </li>
                        <li class="nav-item {{ request()->routeIs('courses.*', 'assignments.*', 'quizzes.*', 'grades.*', 'attendance.*', 'forum.*', 'announcements.*', 'syllabus.*', 'analytics.*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('courses.index') }}">
                                <span class="nav-link-icon"><i class="ti ti-school"></i></span>
                                <span class="nav-link-title">Kelas</span>
                            </a>
                        </li>
                        <li class="nav-item {{ request()->routeIs('calendar') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('calendar') }}">
                                <span class="nav-link-icon"><i class="ti ti-calendar"></i></span>
                                <span class="nav-link-title">Kalender</span>
                            </a>
                        </li>
                        @if ($user->isMahasiswa())
                            <li class="nav-item {{ request()->routeIs('activities.*') ? 'active' : '' }}">
                                <a class="nav-link" href="{{ route('activities.index') }}">
                                    <span class="nav-link-icon"><i class="ti ti-list-check"></i></span>
                                    <span class="nav-link-title">Aktivitas</span>
                                </a>
                            </li>
                        @endif
                        @if ($user->isDosen())
                            <li class="nav-item dropdown {{ request()->routeIs('admin.*') ? 'active' : '' }}">
                                <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown" role="button" aria-expanded="false">
                                    <span class="nav-link-icon"><i class="ti ti-settings"></i></span>
                                    <span class="nav-link-title">Pengaturan</span>
                                </a>
                                <div class="dropdown-menu">
                                    <a class="dropdown-item" href="{{ route('admin.students.index') }}"><i class="ti ti-users me-2"></i>Mahasiswa</a>
                                    <a class="dropdown-item" href="{{ route('admin.semesters.index') }}"><i class="ti ti-calendar-stats me-2"></i>Kelola Semester</a>
                                    <a class="dropdown-item" href="{{ route('admin.settings.edit') }}"><i class="ti ti-palette me-2"></i>Tampilan</a>
                                    <a class="dropdown-item" href="{{ route('admin.gradeScale.edit') }}"><i class="ti ti-award me-2"></i>Skala Nilai</a>
                                    <a class="dropdown-item" href="{{ route('admin.ai.edit') }}"><i class="ti ti-sparkles me-2"></i>Integrasi AI</a>
                                    <a class="dropdown-item" href="{{ route('admin.activity.index') }}"><i class="ti ti-history me-2"></i>Riwayat Aktivitas</a>
                                    <a class="dropdown-item" href="{{ route('admin.backups.index') }}"><i class="ti ti-database me-2"></i>Backup</a>
                                </div>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </header>

    {{-- ===================== PAGE CONTENT ===================== --}}
    <div class="page-wrapper">
        @hasSection('page-title')
        <div class="page-header d-print-none">
            <div class="container-xl">
                <div class="row g-2 align-items-center">
                    <div class="col-12 col-sm">
                        @hasSection('page-pretitle')
                            <div class="page-pretitle">@yield('page-pretitle')</div>
                        @endif
                        <h2 class="page-title">@yield('page-title')</h2>
                    </div>
                    <div class="col-12 col-sm-auto ms-sm-auto d-print-none">
                        @yield('page-actions')
                    </div>
                </div>
            </div>
        </div>
        @endif

        <div class="page-body">
            <div class="container-xl">
                @include('partials.flash')
                @yield('content')
            </div>
        </div>

        <footer class="footer footer-transparent d-print-none">
            <div class="container-xl">
                <div class="row text-center align-items-center">
                    <div class="col-12 text-secondary small">
                        &copy; {{ date('Y') }} {{ $headerTitle }} — {{ $footerText }}
                    </div>
                </div>
            </div>
        </footer>
    </div>
</div>

@if ($user->isMahasiswa())
    @php($mobileHomeActive = request()->routeIs('dashboard*'))
    @php($mobileCoursesActive = request()->routeIs('courses.*', 'materials.*', 'assignments.*', 'quizzes.*', 'submissions.*', 'grades.*', 'attendance.*', 'forum.*', 'announcements.*', 'syllabus.*'))
    @php($mobileCalendarActive = request()->routeIs('calendar'))
    @php($mobileMoreActive = request()->routeIs('activities.*', 'notifications.*', 'profile.*', 'panduan'))
    <nav class="mobile-bottom-nav d-print-none" aria-label="Navigasi utama">
        <a href="{{ route('dashboard') }}" class="mobile-bottom-nav__item {{ $mobileHomeActive ? 'active' : '' }}" aria-current="{{ $mobileHomeActive ? 'page' : 'false' }}">
            <i class="ti ti-home"></i><span>Beranda</span>
        </a>
        <a href="{{ route('courses.index') }}" class="mobile-bottom-nav__item {{ $mobileCoursesActive ? 'active' : '' }}" aria-current="{{ $mobileCoursesActive ? 'page' : 'false' }}">
            <i class="ti ti-books"></i><span>Kelas</span>
        </a>
        <a href="{{ route('calendar') }}" class="mobile-bottom-nav__item {{ $mobileCalendarActive ? 'active' : '' }}" aria-current="{{ $mobileCalendarActive ? 'page' : 'false' }}">
            <i class="ti ti-calendar"></i><span>Kalender</span>
        </a>
        @php($mobileUnread = $user->notifications()->unread()->count())
        <button class="mobile-bottom-nav__item {{ $mobileMoreActive ? 'active' : '' }}" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobile-more-menu" aria-controls="mobile-more-menu" aria-current="{{ $mobileMoreActive ? 'page' : 'false' }}">
            <i class="ti ti-menu-2"></i><span>Lainnya</span>
            @if($mobileUnread)<span class="mobile-bottom-nav__badge">{{ $mobileUnread > 99 ? '99+' : $mobileUnread }}</span>@endif
        </button>
    </nav>

    <div class="offcanvas offcanvas-bottom mobile-more-sheet d-print-none" tabindex="-1" id="mobile-more-menu" aria-labelledby="mobile-more-title">
        <div class="offcanvas-header">
            <div>
                <div class="text-secondary small">Menu mahasiswa</div>
                <h2 class="offcanvas-title" id="mobile-more-title">Lainnya</h2>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Tutup"></button>
        </div>
        <div class="offcanvas-body pt-2 pb-4">
            <div class="mobile-more-profile">
                <x-avatar :name="$user->name" :url="$user->avatarUrl()" size="md" />
                <div class="min-w-0"><div class="fw-bold text-truncate">{{ $user->name }}</div><div class="small text-secondary text-truncate">{{ $user->nim_nip ?: 'Mahasiswa' }}</div></div>
            </div>
            <div class="mobile-more-section-title">Akademik</div>
            <div class="mobile-more-grid">
                <a href="{{ route('activities.index') }}" class="mobile-more-link"><i class="ti ti-list-check"></i><span>Aktivitas</span></a>
                <a href="{{ route('notifications.index') }}" class="mobile-more-link position-relative"><i class="ti ti-bell"></i><span>Notifikasi</span>@if($mobileUnread)<span class="badge bg-red text-white position-absolute top-0 end-0 m-2">{{ $mobileUnread }}</span>@endif</a>
                <a href="{{ route('enrollments.join.show') }}" class="mobile-more-link"><i class="ti ti-key"></i><span>Gabung Kelas</span></a>
            </div>
            <div class="mobile-more-section-title">Akun & Bantuan</div>
            <div class="mobile-more-grid">
                <a href="{{ route('profile.edit') }}" class="mobile-more-link"><i class="ti ti-user"></i><span>Profil</span></a>
                <a href="{{ route('panduan') }}" class="mobile-more-link"><i class="ti ti-help-circle"></i><span>Panduan</span></a>
            </div>
            <button type="submit" form="mobile-logout-form" class="mobile-more-logout"><i class="ti ti-logout"></i><span>Keluar dari akun</span></button>
            <form id="mobile-logout-form" method="POST" action="{{ route('logout') }}" class="d-none">@csrf</form>
        </div>
    </div>
@endif

{{-- Toast container (untuk flash message) --}}
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:1090;">
    @if (session('status'))
        <div class="toast align-items-center border-0" role="alert" data-bs-delay="4000">
            <div class="d-flex">
                <div class="toast-body"><i class="ti ti-circle-check text-green me-1"></i>{{ session('status') }}</div>
                <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    @endif
    @if (session('error'))
        <div class="toast align-items-center border-0" role="alert" data-bs-delay="6000">
            <div class="d-flex">
                <div class="toast-body"><i class="ti ti-alert-triangle text-red me-1"></i>{{ session('error') }}</div>
                <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    @endif
</div>

{{-- Modal konfirmasi global (dipicu form ber-atribut data-confirm) --}}
<div class="modal modal-blur fade" id="confirm-modal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center py-4">
                <i class="ti ti-alert-triangle text-danger" style="font-size:2.5rem;"></i>
                <h3 class="mt-2 mb-1">Konfirmasi</h3>
                <div class="text-secondary" id="confirm-modal-text">Yakin melakukan tindakan ini?</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link link-secondary me-auto" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" id="confirm-modal-ok">Ya, lanjutkan</button>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('tabler/js/tabler.min.js') }}" defer></script>
<script src="{{ asset('js/alpine.min.js') }}" defer></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var bs = window.bootstrap;
    if (!bs) return;

    // Tampilkan semua toast otomatis
    document.querySelectorAll('.toast').forEach(function (el) { new bs.Toast(el).show(); });

    // Aktifkan tooltip
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) { new bs.Tooltip(el); });

    // Progress bar navigasi
    var np = document.getElementById('nprogress');
    function npStart() { if (!np) return; np.classList.add('on'); np.style.width = '0'; requestAnimationFrame(function () { np.style.width = '85%'; }); }
    document.addEventListener('click', function (e) {
        var a = e.target.closest('a');
        if (!a) return;
        var href = a.getAttribute('href');
        if (!href || href.charAt(0) === '#' || a.target === '_blank' || a.hasAttribute('download') || a.hasAttribute('data-bs-toggle') || href.indexOf('javascript:') === 0) return;
        if (a.origin && a.origin !== location.origin) return;
        npStart();
    });
    document.addEventListener('submit', npStart);
    window.addEventListener('pageshow', function () { if (np) { np.classList.remove('on'); np.style.width = '0'; } });

    // Auto-refresh badge notifikasi (polling ringan, tiap 30 detik saat tab aktif)
    (function () {
        var badge = document.getElementById('nav-bell-badge');
        if (!badge) return;
        function refresh() {
            if (document.hidden) return;
            fetch('{{ route('notifications.count') }}', { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.ok ? r.json() : null; })
                .then(function (d) {
                    if (!d) return;
                    var n = d.unread || 0;
                    badge.textContent = n > 9 ? '9+' : n;
                    badge.classList.toggle('d-none', n < 1);
                })
                .catch(function () {});
        }
        setInterval(refresh, 30000);
        document.addEventListener('visibilitychange', function () { if (!document.hidden) refresh(); });
    })();

    // Modal konfirmasi untuk form/link dengan data-confirm
    var modalEl = document.getElementById('confirm-modal');
    var modal = new bs.Modal(modalEl);
    var pending = null;
    var okBtn = document.getElementById('confirm-modal-ok');
    var txt = document.getElementById('confirm-modal-text');

    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (form.dataset.confirmed === '1') return;
            e.preventDefault();
            pending = form;
            txt.textContent = form.getAttribute('data-confirm');
            modal.show();
        });
    });

    okBtn.addEventListener('click', function () {
        if (pending) { pending.dataset.confirmed = '1'; pending.submit(); }
        modal.hide();
    });

    // Pencarian tabel: <input data-table-search="#idTabel">
    document.querySelectorAll('[data-table-search]').forEach(function (input) {
        var table = document.querySelector(input.getAttribute('data-table-search'));
        if (!table) return;
        input.addEventListener('input', function () {
            var q = input.value.toLowerCase();
            table.querySelectorAll('tbody tr').forEach(function (tr) {
                tr.style.display = tr.textContent.toLowerCase().indexOf(q) > -1 ? '' : 'none';
            });
        });
    });

    // Loading state untuk tombol operasi lambat: <button data-loading="teks…">
    document.querySelectorAll('form').forEach(function (form) {
        form.addEventListener('submit', function () {
            var b = form.querySelector('button[data-loading]');
            if (!b || b.disabled) return;
            var label = b.getAttribute('data-loading') || 'Memproses…';
            b.dataset.orig = b.innerHTML;
            b.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span>' + label;
            setTimeout(function () { b.disabled = true; }, 0);
        });
    });

    // Peringatan perubahan belum disimpan: <form data-warn-unsaved>
    document.querySelectorAll('form[data-warn-unsaved]').forEach(function (form) {
        var dirty = false;
        form.addEventListener('input', function () { dirty = true; });
        form.addEventListener('change', function () { dirty = true; });
        form.addEventListener('submit', function () { dirty = false; });
        window.addEventListener('beforeunload', function (e) {
            if (dirty) { e.preventDefault(); e.returnValue = ''; }
        });
    });

    // Sort tabel: <table class="table-sortable"> — klik <th> (kecuali .no-sort)
    document.querySelectorAll('table.table-sortable thead th').forEach(function (th) {
        if (th.classList.contains('no-sort')) return;
        th.style.cursor = 'pointer';
        th.addEventListener('click', function () {
            var table = th.closest('table');
            var tbody = table.querySelector('tbody');
            var rows = Array.from(tbody.querySelectorAll('tr'));
            var ci = Array.from(th.parentNode.children).indexOf(th);
            var asc = th.dataset.sortAsc !== '1';
            th.dataset.sortAsc = asc ? '1' : '0';
            rows.sort(function (a, b) {
                var x = (a.children[ci] ? a.children[ci].textContent : '').trim();
                var y = (b.children[ci] ? b.children[ci].textContent : '').trim();
                var nx = parseFloat(x.replace(/[^0-9.\-]/g, '')), ny = parseFloat(y.replace(/[^0-9.\-]/g, ''));
                if (!isNaN(nx) && !isNaN(ny) && x.replace(/[^0-9.\-]/g,'') !== '' && y.replace(/[^0-9.\-]/g,'') !== '') return asc ? nx - ny : ny - nx;
                return asc ? x.localeCompare(y) : y.localeCompare(x);
            });
            rows.forEach(function (r) { tbody.appendChild(r); });
        });
    });
});
</script>
@stack('scripts')
</body>
</html>
