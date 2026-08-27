@php
    $primaryAdminLinks = [
        ['route' => 'admin.dashboard', 'match' => 'admin.dashboard', 'label' => 'Ringkasan', 'icon' => 'ti-layout-dashboard'],
        ['route' => 'admin.lecturers.index', 'match' => 'admin.lecturers.*', 'label' => 'Dosen', 'icon' => 'ti-school'],
        ['route' => 'admin.students.index', 'match' => 'admin.students.*', 'label' => 'Mahasiswa', 'icon' => 'ti-users'],
    ];
    $adminMenuGroups = [
        'Akademik' => [
            ['route' => 'admin.semesters.index', 'match' => 'admin.semesters.*', 'label' => 'Semester', 'icon' => 'ti-calendar-stats'],
            ['route' => 'admin.gradeScale.edit', 'match' => 'admin.gradeScale.*', 'label' => 'Skala nilai', 'icon' => 'ti-award'],
        ],
        'Aplikasi' => [
            ['route' => 'admin.settings.edit', 'match' => 'admin.settings.*', 'label' => 'Tampilan aplikasi', 'icon' => 'ti-palette'],
            ['route' => 'admin.ai.edit', 'match' => 'admin.ai.*', 'label' => 'Integrasi AI', 'icon' => 'ti-sparkles'],
            ['route' => 'admin.activity.index', 'match' => 'admin.activity.*', 'label' => 'Riwayat aktivitas', 'icon' => 'ti-history'],
            ['route' => 'admin.backups.index', 'match' => 'admin.backups.*', 'label' => 'Backup database', 'icon' => 'ti-database'],
        ],
    ];
    $adminMenuActive = !request()->routeIs('admin.dashboard', 'admin.lecturers.*', 'admin.students.*');
@endphp
<header class="admin-header d-print-none">
    <div class="container-xl admin-header-inner">
        <a href="{{ route('admin.dashboard') }}" class="admin-brand"><span class="admin-brand-icon"><i class="ti ti-shield-check" aria-hidden="true"></i></span><span>Panel admin<small>{{ $appName }}</small></span></a>
        <div class="admin-header-actions">
            <a href="{{ route('dashboard.dosen') }}" class="btn btn-sm"><i class="ti ti-book me-1" aria-hidden="true"></i>Ruang mengajar</a>
            <a href="{{ route('notifications.index') }}" class="btn btn-icon btn-sm position-relative" aria-label="Notifikasi"><i class="ti ti-bell" aria-hidden="true"></i><span id="nav-bell-badge" @class(['admin-notification-count', 'd-none' => ($navUnreadCount ?? 0) < 1])>{{ ($navUnreadCount ?? 0) > 9 ? '9+' : ($navUnreadCount ?? 0) }}</span></a>
        </div>
    </div>
</header>
<nav class="admin-desktop-nav container-xl d-print-none" aria-label="Navigasi admin">
    @foreach ($primaryAdminLinks as $link)
        <a href="{{ route($link['route']) }}" @class(['admin-nav-link', 'active' => request()->routeIs($link['match'])]) @if(request()->routeIs($link['match'])) aria-current="page" @endif><i class="ti {{ $link['icon'] }}" aria-hidden="true"></i>{{ $link['label'] }}</a>
    @endforeach
    <button type="button" @class(['admin-nav-link', 'active' => $adminMenuActive]) data-bs-toggle="offcanvas" data-bs-target="#admin-menu" aria-controls="admin-menu"><i class="ti ti-settings" aria-hidden="true"></i>Pengaturan & lainnya</button>
</nav>
<nav class="admin-bottom-nav d-print-none" aria-label="Navigasi admin mobile">
    @foreach ($primaryAdminLinks as $link)
        <a href="{{ route($link['route']) }}" @class(['admin-nav-link', 'active' => request()->routeIs($link['match'])]) @if(request()->routeIs($link['match'])) aria-current="page" @endif><i class="ti {{ $link['icon'] }}" aria-hidden="true"></i><span>{{ $link['label'] }}</span></a>
    @endforeach
    <button type="button" @class(['admin-nav-link', 'active' => $adminMenuActive]) data-bs-toggle="offcanvas" data-bs-target="#admin-menu" aria-controls="admin-menu"><i class="ti ti-menu-2" aria-hidden="true"></i><span>Lainnya</span></button>
</nav>
<div class="offcanvas offcanvas-end admin-menu d-print-none" tabindex="-1" id="admin-menu" aria-labelledby="admin-menu-title">
    <div class="offcanvas-header"><h2 class="offcanvas-title" id="admin-menu-title">Menu admin</h2><button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Tutup menu"></button></div>
    <div class="offcanvas-body">
        <div class="admin-profile"><x-avatar :name="$user->name" :url="$user->avatarUrl()" /><div><strong>{{ $user->name }}</strong><span class="text-secondary small">{{ $user->email }}</span><span class="badge bg-primary-lt mt-1">Administrator</span></div></div>
        @foreach ($adminMenuGroups as $group => $links)
            <h3 class="admin-menu-heading">{{ $group }}</h3>
            @foreach ($links as $link)
                <a href="{{ route($link['route']) }}" @class(['admin-menu-link', 'active' => request()->routeIs($link['match'])]) @if(request()->routeIs($link['match'])) aria-current="page" @endif><i class="ti {{ $link['icon'] }}" aria-hidden="true"></i><span>{{ $link['label'] }}</span><i class="ti ti-chevron-right ms-auto" aria-hidden="true"></i></a>
            @endforeach
        @endforeach
        <h3 class="admin-menu-heading">Akun</h3>
        <a href="{{ route('profile.edit') }}" class="admin-menu-link"><i class="ti ti-user" aria-hidden="true"></i>Profil & kata sandi</a>
        <a href="{{ route('dashboard.dosen') }}" class="admin-menu-link"><i class="ti ti-book" aria-hidden="true"></i>Ruang mengajar</a>
        <button type="button" class="admin-menu-link w-100 border-0 bg-transparent hide-theme-dark" onclick="document.documentElement.setAttribute('data-bs-theme','dark');try{localStorage.setItem('lms-theme','dark');}catch(e){}"><i class="ti ti-moon" aria-hidden="true"></i>Mode gelap</button>
        <button type="button" class="admin-menu-link w-100 border-0 bg-transparent hide-theme-light" onclick="document.documentElement.setAttribute('data-bs-theme','light');try{localStorage.setItem('lms-theme','light');}catch(e){}"><i class="ti ti-sun" aria-hidden="true"></i>Mode terang</button>
        <form method="POST" action="{{ route('logout') }}" class="mt-4 pt-3 border-top">@csrf<button type="submit" class="btn btn-outline-danger w-100"><i class="ti ti-logout me-2" aria-hidden="true"></i>Keluar dari akun</button></form>
    </div>
</div>
