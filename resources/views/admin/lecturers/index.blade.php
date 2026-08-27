@extends('layouts.app')
@section('title', 'Kelola Dosen')
@section('page-pretitle', 'Administrasi')
@section('page-title', 'Kelola Dosen')

@section('content')
<div class="row justify-content-center"><div class="col-lg-9">
    <p class="text-secondary">Periksa pendaftaran dosen dan terbitkan kode setelah pembayaran satu kali dikonfirmasi.</p>
    <nav class="nav nav-pills gap-2 mb-3" aria-label="Status akun dosen">
        @foreach (['pending' => 'Menunggu aktivasi', 'active' => 'Aktif', 'all' => 'Semua'] as $key => $label)
            <a class="nav-link {{ $status === $key ? 'active' : '' }}" href="{{ route('admin.lecturers.index', ['status' => $key, 'q' => $q]) }}" aria-current="{{ $status === $key ? 'page' : 'false' }}">{{ $label }} <span class="badge bg-secondary-lt ms-1">{{ $key === 'pending' ? $pendingCount : ($key === 'active' ? $activeCount : $pendingCount + $activeCount) }}</span></a>
        @endforeach
    </nav>
    <form method="GET" action="{{ route('admin.lecturers.index') }}" class="mb-3">
        <input type="hidden" name="status" value="{{ $status }}">
        <div class="input-group"><input type="search" name="q" class="form-control" placeholder="Cari nama, email, atau institusi" aria-label="Cari dosen" value="{{ $q }}"><button type="submit" class="btn">Cari</button></div>
    </form>
    <div class="card overflow-hidden">
        <div class="list-group list-group-flush">
            @forelse ($lecturers as $lecturer)
                <a href="{{ route('admin.lecturers.show', $lecturer) }}" class="list-group-item list-group-item-action d-flex align-items-center gap-3">
                    <x-avatar :name="$lecturer->name" :url="$lecturer->avatarUrl()" />
                    <span class="responsive-item-main">
                        <span class="d-block fw-bold responsive-item-title">{{ $lecturer->name }}</span>
                        <span class="d-block small text-secondary">{{ $lecturer->email }}</span>
                        <span class="d-block small text-secondary">{{ $lecturer->institution ?: 'Institusi belum diisi' }}</span>
                        <span class="badge bg-{{ $lecturer->needsLecturerActivation() ? 'orange' : 'green' }}-lt mt-1">{{ $lecturer->needsLecturerActivation() ? 'Menunggu aktivasi' : 'Aktif · Sekali bayar' }}</span>
                    </span>
                    <i class="ti ti-chevron-right text-secondary flex-shrink-0" aria-hidden="true"></i>
                </a>
            @empty
                <div class="card-body"><x-empty-state icon="ti-user-check" title="Tidak ada dosen pada daftar ini" description="Pendaftaran baru akan muncul pada tab Menunggu aktivasi." /></div>
            @endforelse
        </div>
    </div>
    <div class="mt-3">{{ $lecturers->links() }}</div>
</div></div>
@endsection
