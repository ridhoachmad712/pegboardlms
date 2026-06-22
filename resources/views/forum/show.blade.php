@extends('layouts.app')

@section('title', $thread->title)
@section('page-pretitle', $thread->course->name . ' · Forum')
@section('page-title', $thread->title)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('courses.index') }}">Kelas Saya</a></li>
    <li class="breadcrumb-item"><a href="{{ route('courses.show', $thread->course) }}">{{ $thread->course->name }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('forum.index', $thread->course) }}">Forum</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ \Illuminate\Support\Str::limit($thread->title, 24) }}</li>
@endsection

@section('page-actions')
    <div class="btn-list">
        <a href="{{ route('forum.index', $thread->course) }}" class="btn"><i class="ti ti-arrow-left me-1"></i>Forum</a>
        @if (auth()->user()->isDosen() && $thread->course->user_id === auth()->id())
            <form method="POST" action="{{ route('forum.pin', $thread) }}">@csrf @method('PATCH')
                <button class="btn"><i class="ti ti-pin me-1"></i>{{ $thread->pinned ? 'Lepas sematan' : 'Sematkan' }}</button>
            </form>
        @endif
        @if ($thread->user_id === auth()->id() || (auth()->user()->isDosen() && $thread->course->user_id === auth()->id()))
            <form method="POST" action="{{ route('forum.threads.destroy', $thread) }}" data-confirm="Hapus diskusi ini?">@csrf @method('DELETE')
                <button class="btn btn-danger"><i class="ti ti-trash"></i></button>
            </form>
        @endif
    </div>
@endsection

@php($roleBadge = fn ($u) => $u->isDosen()
    ? '<span class="badge bg-primary-lt ms-1">Dosen</span>'
    : '<span class="badge bg-secondary-lt ms-1">Mahasiswa</span>')

@section('content')
@php($course = $thread->course)
@include('courses._subnav')

<div class="row justify-content-center">
    <div class="col-lg-8">
        {{-- Thread utama --}}
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex mb-2">
                    <x-avatar :name="$thread->author->name" :url="$thread->author->avatarUrl()" size="md" class="me-2" />
                    <div>
                        <div class="fw-bold">{{ $thread->author->name }} {!! $roleBadge($thread->author) !!}</div>
                        <div class="text-secondary small">{{ $thread->created_at->translatedFormat('d M Y H:i') }}</div>
                    </div>
                </div>
                <div style="white-space:pre-line">{{ $thread->content }}</div>
            </div>
        </div>

        {{-- Balasan --}}
        <h4 class="mb-2">{{ $thread->replies->count() }} Balasan</h4>
        @foreach ($thread->replies as $reply)
            <div class="card mb-2">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-1">
                        <x-avatar :name="$reply->author->name" :url="$reply->author->avatarUrl()" class="me-2" />
                        <div class="fw-bold">{{ $reply->author->name }} {!! $roleBadge($reply->author) !!}</div>
                        <span class="text-secondary small ms-2">{{ $reply->created_at->diffForHumans() }}</span>
                        @if ($reply->user_id === auth()->id() || (auth()->user()->isDosen() && $thread->course->user_id === auth()->id()))
                            <form method="POST" action="{{ route('forum.replies.destroy', $reply) }}" class="ms-auto" data-confirm="Hapus balasan?">@csrf @method('DELETE')
                                <button class="btn btn-sm btn-ghost-danger"><i class="ti ti-trash"></i></button>
                            </form>
                        @endif
                    </div>
                    <div style="white-space:pre-line">{{ $reply->content }}</div>
                </div>
            </div>
        @endforeach

        {{-- Form balas --}}
        <form class="card mt-3" method="POST" action="{{ route('forum.replies.store', $thread) }}">
            @csrf
            <div class="card-body">
                <label class="form-label">Tulis balasan</label>
                <textarea name="content" class="form-control mb-2" rows="3" required></textarea>
                <button class="btn btn-primary"><i class="ti ti-send me-1"></i>Kirim Balasan</button>
            </div>
        </form>
    </div>
</div>
@endsection
