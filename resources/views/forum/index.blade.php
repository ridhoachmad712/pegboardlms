@extends('layouts.app')

@section('title', 'Forum')

@section('hero-actions')
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-thread"><i class="ti ti-plus me-1"></i>Diskusi Baru</button>
@endsection

@section('content')
@include('courses._hero')

<div class="row justify-content-center">
    <div class="col-lg-9">
        @forelse ($threads as $thread)
            <div class="card mb-2">
                <div class="card-body d-flex align-items-center">
                    <span class="avatar bg-{{ $thread->pinned ? 'yellow' : 'blue' }}-lt me-3"><i class="ti {{ $thread->pinned ? 'ti-pin' : 'ti-message' }}"></i></span>
                    <div class="me-auto">
                        <a href="{{ route('forum.show', $thread) }}" class="fw-bold text-reset">{{ $thread->title }}</a>
                        @if ($thread->pinned)<span class="badge bg-yellow-lt ms-1">Disematkan</span>@endif
                        <div class="text-secondary small">{{ $thread->author->name }} · {{ $thread->created_at->diffForHumans() }}</div>
                    </div>
                    <span class="badge bg-secondary-lt"><i class="ti ti-messages me-1"></i>{{ $thread->replies_count }}</span>
                </div>
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
