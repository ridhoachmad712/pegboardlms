@extends('layouts.app')
@section('title', 'Kelola Dosen')
@section('page-pretitle', 'Administrasi')
@section('page-title', 'Kelola Dosen')

@section('content')
<div class="row justify-content-center"><div class="col-lg-9">
    <p class="admin-page-description">Kelola akses akun, kode aktivasi, dan password dosen.</p>
    <nav class="admin-status-tabs" aria-label="Status akun dosen">
        @foreach (['pending' => 'Menunggu', 'active' => 'Aktif', 'disabled' => 'Nonaktif', 'all' => 'Semua'] as $key => $label)
            <a class="nav-link {{ $status === $key ? 'active' : '' }}" href="{{ route('admin.lecturers.index', ['status' => $key, 'q' => $q]) }}" @if($status === $key) aria-current="page" @endif>{{ $label }} <span class="badge bg-secondary-lt">{{ ['pending' => $pendingCount, 'active' => $activeCount, 'disabled' => $disabledCount, 'all' => $pendingCount + $activeCount + $disabledCount][$key] }}</span></a>
        @endforeach
    </nav>
    <form method="GET" action="{{ route('admin.lecturers.index') }}" class="mb-3">
        <input type="hidden" name="status" value="{{ $status }}">
        <div class="input-group"><input type="search" name="q" class="form-control" placeholder="Nama, email, atau institusi" aria-label="Cari dosen" value="{{ $q }}"><button type="submit" class="btn">Cari</button></div>
        @if($q !== '')<div class="small text-secondary mt-2">{{ $lecturers->total() }} hasil pencarian. <a href="{{ route('admin.lecturers.index', ['status' => $status]) }}">Hapus pencarian</a></div>@endif
    </form>
    <div class="card overflow-hidden">
        <div class="list-group list-group-flush">
            @forelse ($lecturers as $lecturer)
                <a href="{{ route('admin.lecturers.show', $lecturer) }}" class="list-group-item list-group-item-action admin-person-row">
                    <x-avatar :name="$lecturer->name" :url="$lecturer->avatarUrl()" />
                    <span class="admin-person-main">
                        <span class="d-block fw-bold responsive-item-title">{{ $lecturer->name }}</span>
                        <span class="d-block small text-secondary">{{ $lecturer->email }}</span>
                        <span class="d-block small text-secondary">{{ $lecturer->institution ?: 'Institusi belum diisi' }}</span>
                        <span class="badge bg-{{ $lecturer->isLecturerDisabled() ? 'red' : ($lecturer->needsLecturerActivation() ? 'orange' : 'green') }}-lt mt-2">{{ $lecturer->isLecturerDisabled() ? 'Nonaktif' : ($lecturer->needsLecturerActivation() ? 'Menunggu aktivasi' : 'Aktif') }}</span>
                    </span>
                    <i class="ti ti-chevron-right text-secondary flex-shrink-0" aria-hidden="true"></i>
                </a>
            @empty
                <div class="card-body"><x-empty-state icon="ti-user-check" :title="$q !== '' ? 'Dosen tidak ditemukan' : ($status === 'pending' ? 'Tidak ada aktivasi tertunda' : ($status === 'disabled' ? 'Tidak ada akun nonaktif' : 'Belum ada dosen pada daftar ini'))" :description="$q !== '' ? 'Coba kata kunci lain atau hapus pencarian.' : ($status === 'disabled' ? 'Akun yang dinonaktifkan admin akan muncul di sini.' : 'Pendaftaran baru akan muncul pada tab Menunggu.')" /></div>
            @endforelse
        </div>
    </div>
    @if($lecturers->hasPages())<div class="mt-3">{{ $lecturers->links('pagination.admin') }}</div>@endif
</div></div>
@endsection
