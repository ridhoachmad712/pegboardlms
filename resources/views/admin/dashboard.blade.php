@extends('layouts.app')
@section('title', 'Ringkasan Admin')
@section('page-pretitle', 'Administrasi')
@section('page-title', 'Ringkasan admin')

@section('content')
<p class="admin-page-description">Kelola akses dosen dan administrasi aplikasi dari satu tempat.</p>
<div class="admin-stats" aria-label="Ringkasan pengguna dan kelas">
    <a href="{{ route('admin.lecturers.index', ['status' => 'pending']) }}" class="admin-stat"><span class="admin-stat-label"><i class="ti ti-hourglass text-orange" aria-hidden="true"></i>Menunggu aktivasi</span><strong>{{ number_format($pendingCount) }}</strong></a>
    <a href="{{ route('admin.lecturers.index', ['status' => 'active']) }}" class="admin-stat"><span class="admin-stat-label"><i class="ti ti-school text-primary" aria-hidden="true"></i>Dosen aktif</span><strong>{{ number_format($activeCount) }}</strong></a>
    <a href="{{ route('admin.students.index') }}" class="admin-stat"><span class="admin-stat-label"><i class="ti ti-users text-primary" aria-hidden="true"></i>Mahasiswa</span><strong>{{ number_format($studentCount) }}</strong></a>
    <a href="{{ route('admin.semesters.index') }}" class="admin-stat"><span class="admin-stat-label"><i class="ti ti-books text-primary" aria-hidden="true"></i>Kelas aktif</span><strong>{{ number_format($courseCount) }}</strong></a>
</div>
<div class="row g-3">
    <div class="col-lg-8">
        <section class="card" aria-labelledby="pending-title">
            <div class="card-header"><div><h2 class="card-title" id="pending-title">Perlu ditindaklanjuti</h2><p class="small text-secondary mb-0 mt-1">Pendaftaran terlama ditampilkan lebih dahulu.</p></div><a href="{{ route('admin.lecturers.index') }}" class="btn btn-sm ms-auto">Lihat semua</a></div>
            <div class="list-group list-group-flush">
                @forelse ($pendingLecturers as $lecturer)
                    <a href="{{ route('admin.lecturers.show', $lecturer) }}" class="list-group-item list-group-item-action admin-person-row">
                        <x-avatar :name="$lecturer->name" :url="$lecturer->avatarUrl()" />
                        <div class="admin-person-main"><strong>{{ $lecturer->name }}</strong><span class="text-secondary small">{{ $lecturer->institution ?: $lecturer->email }}</span><span class="small text-secondary">Terdaftar {{ $lecturer->created_at->translatedFormat('d M Y') }}</span></div>
                        <i class="ti ti-chevron-right text-secondary" aria-hidden="true"></i>
                    </a>
                @empty
                    <div class="card-body"><x-empty-state icon="ti-circle-check" title="Tidak ada aktivasi tertunda" description="Pendaftaran dosen baru akan muncul di sini." /></div>
                @endforelse
            </div>
        </section>
    </div>
    <div class="col-lg-4">
        <section class="card" aria-labelledby="admin-quick-title"><div class="card-header"><h2 class="card-title" id="admin-quick-title">Akses cepat</h2></div><div class="list-group list-group-flush">
            @foreach ([['admin.semesters.index', 'ti-calendar-stats', 'Semester', 'Atur periode akademik aktif'], ['admin.settings.edit', 'ti-palette', 'Tampilan aplikasi', 'Identitas, logo, dan warna'], ['admin.backups.index', 'ti-database', 'Backup database', 'Cadangkan dan unduh data']] as [$route, $icon, $label, $description])
                <a href="{{ route($route) }}" class="list-group-item list-group-item-action admin-person-row"><i class="ti {{ $icon }} text-primary fs-2" aria-hidden="true"></i><div class="admin-person-main"><strong>{{ $label }}</strong><span class="small text-secondary">{{ $description }}</span></div><i class="ti ti-chevron-right text-secondary" aria-hidden="true"></i></a>
            @endforeach
        </div></section>
    </div>
</div>
@endsection
