@extends('layouts.app')

@section('title', 'Forum')

@section('hero-actions')
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-thread"><i class="ti ti-plus me-1"></i>Diskusi Baru</button>
@endsection

@section('content')
@include('courses._hero')

<button class="btn btn-primary w-100 mb-3 d-md-none" data-bs-toggle="modal" data-bs-target="#modal-thread"><i class="ti ti-plus me-1"></i>Diskusi Baru</button>

<div class="row justify-content-center">
    <div class="col-lg-9">
        @forelse ($threads as $thread)
            <div class="card mb-2 overflow-hidden">
                <a href="{{ route('forum.show', $thread) }}" class="card-body d-flex align-items-center gap-2 text-reset text-decoration-none">
                    <span class="avatar bg-{{ $thread->pinned ? 'yellow' : 'blue' }}-lt flex-shrink-0"><i class="ti {{ $thread->pinned ? 'ti-pin' : 'ti-message' }}"></i></span>
                    <div class="responsive-item-main">
                        <div class="fw-bold responsive-item-title">{{ $thread->title }}</div>
                        @if ($thread->pinned)<span class="badge bg-yellow-lt ms-1">Disematkan</span>@endif
                        <div class="text-secondary small">{{ $thread->author->name }} · {{ $thread->created_at->diffForHumans() }}</div>
                    </div>
                    <span class="badge bg-secondary-lt flex-shrink-0"><i class="ti ti-messages me-1"></i>{{ $thread->replies_count }}</span>
                    <i class="ti ti-chevron-right text-secondary flex-shrink-0 d-md-none"></i>
                </a>
            </div>
        @empty
            <div class="card"><div class="card-body">
                <x-empty-state icon="ti-messages" title="Belum ada diskusi" description="Mulai diskusi pertama di kelas ini.">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-thread"><i class="ti ti-plus me-1"></i>Diskusi Baru</button>
                </x-empty-state>
            </div></div>
        @endforelse
    </div>
</div>

<div class="modal modal-blur fade" id="modal-thread" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="POST" action="{{ route('forum.threads.store', $course) }}">
            @csrf
            <div class="modal-header"><h5 class="modal-title">Diskusi Baru</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label required">Judul</label><input type="text" name="title" class="form-control" required></div>
                <div class="mb-3"><label class="form-label required">Isi</label><textarea name="content" class="form-control" rows="4" required></textarea></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-link" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Kirim</button></div>
        </form>
    </div>
</div>
@endsection
