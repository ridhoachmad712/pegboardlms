@extends('layouts.app')
@section('title', 'Notifikasi')
@section('page-title', 'Notifikasi')

@section('page-actions')
<div class="btn-list">
    <button class="btn" data-bs-toggle="modal" data-bs-target="#notification-settings"><i class="ti ti-adjustments me-1"></i>Pengaturan</button>
    @if($notifications->isNotEmpty())<form method="POST" action="{{ route('notifications.readAll') }}">@csrf<button class="btn"><i class="ti ti-checks me-1"></i>Tandai semua dibaca</button></form>@endif
</div>
@endsection

@section('content')
@php($icons = ['grade' => ['ti-clipboard-check', 'green'], 'announcement' => ['ti-speakerphone', 'yellow'], 'forum' => ['ti-messages', 'azure'], 'revision' => ['ti-refresh', 'orange'], 'reminder' => ['ti-clock', 'purple'], 'lecturer_registration' => ['ti-user-plus', 'blue']])
<div class="row justify-content-center"><div class="col-lg-8">
    <nav class="nav nav-pills flex-nowrap gap-2 mb-3 overflow-x-auto notification-filters mobile-filter-chips" aria-label="Filter notifikasi">
        <a href="{{ route('notifications.index', ['filter' => 'all', 'type' => $type]) }}" class="nav-link text-nowrap {{ $filter === 'all' ? 'active' : '' }}" aria-current="{{ $filter === 'all' && ! $type ? 'page' : 'false' }}">Semua</a>
        <a href="{{ route('notifications.index', ['filter' => 'unread', 'type' => $type]) }}" class="nav-link text-nowrap {{ $filter === 'unread' ? 'active' : '' }}" aria-current="{{ $filter === 'unread' && ! $type ? 'page' : 'false' }}">Belum dibaca</a>
        @foreach (['grade' => 'Nilai', 'announcement' => 'Pengumuman', 'forum' => 'Forum', 'revision' => 'Revisi'] as $key => $label)
            <a href="{{ route('notifications.index', ['filter' => $filter, 'type' => $type === $key ? null : $key]) }}" class="nav-link text-nowrap {{ $type === $key ? 'active' : '' }}" aria-current="{{ $type === $key ? 'page' : 'false' }}">{{ $label }}</a>
        @endforeach
        @if (auth()->user()->isAdmin())
            <a href="{{ route('notifications.index', ['filter' => $filter, 'type' => 'lecturer_registration']) }}" class="nav-link text-nowrap {{ $type === 'lecturer_registration' ? 'active' : '' }}">Pendaftaran dosen</a>
        @endif
    </nav>

    @forelse ($groups as $label => $items)
        <section class="mb-3" aria-labelledby="notification-group-{{ $loop->index }}">
            <h2 class="text-secondary text-uppercase fs-5 mb-2" id="notification-group-{{ $loop->index }}">{{ $label }}</h2>
            <div class="card overflow-hidden"><div class="list-group list-group-flush">
                @foreach ($items as $notification)
                    @php([$icon, $color] = $icons[$notification->type] ?? ['ti-bell', 'secondary'])
                    <a href="{{ route('notifications.read', $notification) }}" class="list-group-item list-group-item-action d-flex align-items-start gap-3">
                        <span class="avatar avatar-sm bg-{{ $color }}-lt flex-shrink-0"><i class="ti {{ $icon }}"></i></span>
                        <span class="responsive-item-main">
                            <span class="d-flex align-items-center gap-2"><span class="fw-bold responsive-item-title {{ $notification->isUnread() ? '' : 'text-secondary' }}">{{ $notification->title }}</span>@if($notification->isUnread())<span class="status-dot bg-red flex-shrink-0"></span>@endif</span>
                            @if ($notification->message)<span class="d-block small text-secondary content-prose">{{ $notification->message }}</span>@endif
                            <span class="d-block small text-secondary mt-1">{{ $notification->created_at->translatedFormat('H:i') }}</span>
                        </span><i class="ti ti-chevron-right text-secondary flex-shrink-0 mt-1"></i>
                    </a>
                @endforeach
            </div></div>
        </section>
    @empty
        <div class="card"><div class="card-body"><x-empty-state icon="ti-bell-off" title="Tidak ada notifikasi" description="Tidak ada notifikasi yang cocok dengan filter ini." /></div></div>
    @endforelse
    <div class="mt-3">{{ $notifications->links() }}</div>
</div></div>

<div class="modal modal-blur fade" id="notification-settings" tabindex="-1" aria-labelledby="notification-settings-title">
<div class="modal-dialog modal-dialog-centered"><form class="modal-content" method="POST" action="{{ route('notifications.preferences') }}">
    @csrf @method('PUT')
    <div class="modal-header"><h2 class="modal-title" id="notification-settings-title">Pengaturan Notifikasi</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
    <div class="modal-body"><p class="text-secondary">Pilih pembaruan yang ingin ditampilkan di aplikasi.</p>
        @foreach (['grade' => 'Nilai diterbitkan', 'announcement' => 'Pengumuman kelas', 'forum' => 'Balasan forum', 'revision' => 'Permintaan revisi', 'reminder' => 'Pengingat deadline'] as $key => $label)
            <label class="form-check form-switch py-2"><input class="form-check-input" type="checkbox" name="{{ $key }}" value="1" @checked(auth()->user()->wantsNotification($key))><span class="form-check-label">{{ $label }}</span></label>
        @endforeach
    </div>
    <div class="modal-footer"><button type="button" class="btn" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan Pengaturan</button></div>
</form></div></div>
@endsection

@push('styles')<style>.notification-filters{scrollbar-width:none}.notification-filters::-webkit-scrollbar{display:none}</style>@endpush
