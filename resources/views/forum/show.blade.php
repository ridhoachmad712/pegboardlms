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

@push('styles')
<style>@media(max-width:575.98px){body.student-mobile-ui .page-header{display:none;}}</style>
@endpush

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
@php($isOwner = auth()->user()->isDosen() && $thread->course->user_id === auth()->id())
@include('courses._subnav')

<div class="d-md-none mb-3">
    <div class="text-secondary small">Diskusi</div>
    <h1 class="h2 mb-0">{{ $thread->title }}</h1>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        {{-- Thread utama --}}
        <div class="card mb-3" x-data="{ edit: false }">
            <div class="card-body">
                <div class="d-flex mb-2">
                    <x-avatar :name="$thread->author->name" :url="$thread->author->avatarUrl()" size="md" class="me-2" />
                    <div class="min-w-0">
                        <div class="fw-bold">{{ $thread->author->name }} {!! $roleBadge($thread->author) !!}</div>
                        <div class="text-secondary small">
                            {{ $thread->created_at->translatedFormat('d M Y H:i') }}
                            @if ($thread->updated_at->gt($thread->created_at))<span class="ms-1">· disunting</span>@endif
                        </div>
                    </div>
                    @if ($thread->user_id === auth()->id() || $isOwner)
                        <button type="button" class="btn btn-sm btn-ghost-secondary ms-auto" @click="edit = ! edit" title="Edit diskusi" aria-label="Edit diskusi"><i class="ti ti-pencil"></i></button>
                    @endif
                </div>
                <div x-show="!edit" class="content-prose" style="white-space:pre-line">{{ $thread->content }}</div>
                @if ($thread->user_id === auth()->id() || $isOwner)
                    <form x-show="edit" x-cloak method="POST" action="{{ route('forum.threads.update', $thread) }}" class="mt-2">
                        @csrf @method('PUT')
                        <label class="form-label required">Judul</label>
                        <input type="text" name="title" class="form-control mb-2" value="{{ $thread->title }}" required>
                        <label class="form-label required">Isi</label>
                        <textarea name="content" class="form-control mb-2" rows="4" required>{{ $thread->content }}</textarea>
                        <div class="btn-list justify-content-end">
                            <button type="button" class="btn btn-sm" @click="edit = false">Batal</button>
                            <button class="btn btn-sm btn-primary"><i class="ti ti-device-floppy me-1"></i>Simpan</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>

        {{-- Balasan --}}
        <h4 class="mb-2">{{ $thread->replies->count() }} Balasan</h4>
        @foreach ($thread->replies as $reply)
            @php($canEditReply = $reply->user_id === auth()->id() || $isOwner)
            <div class="card mb-2" x-data="{ edit: false }">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-1">
                        <x-avatar :name="$reply->author->name" :url="$reply->author->avatarUrl()" class="me-2" />
                        <div class="min-w-0 flex-fill"><div class="fw-bold text-truncate">{{ $reply->author->name }} {!! $roleBadge($reply->author) !!}</div><div class="text-secondary small">{{ $reply->created_at->diffForHumans() }}@if ($reply->updated_at->gt($reply->created_at)) · disunting @endif</div></div>
                        @if ($canEditReply)
                            <div class="btn-list ms-auto">
                                <button type="button" class="btn btn-sm btn-ghost-secondary" @click="edit = ! edit" title="Edit balasan" aria-label="Edit balasan"><i class="ti ti-pencil"></i></button>
                                <form method="POST" action="{{ route('forum.replies.destroy', $reply) }}" data-confirm="Hapus balasan?">@csrf @method('DELETE')
                                    <button class="btn btn-sm btn-ghost-danger" title="Hapus balasan" aria-label="Hapus balasan"><i class="ti ti-trash"></i></button>
                                </form>
                            </div>
                        @endif
                    </div>
                    <div x-show="!edit" class="content-prose" style="white-space:pre-line">{{ $reply->content }}</div>
                    @if ($canEditReply)
                        <form x-show="edit" x-cloak method="POST" action="{{ route('forum.replies.update', $reply) }}" class="mt-1">
                            @csrf @method('PUT')
                            <textarea name="content" class="form-control mb-2" rows="3" required>{{ $reply->content }}</textarea>
                            <div class="btn-list justify-content-end">
                                <button type="button" class="btn btn-sm" @click="edit = false">Batal</button>
                                <button class="btn btn-sm btn-primary"><i class="ti ti-device-floppy me-1"></i>Simpan</button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        @endforeach

        {{-- Form balas --}}
        <form class="card mt-3" method="POST" action="{{ route('forum.replies.store', $thread) }}">
            @csrf
            <div class="card-body">
                <label class="form-label">Tulis balasan</label>
                <textarea name="content" class="form-control mb-2" rows="3" required></textarea>
                <button class="btn btn-primary w-100 w-md-auto"><i class="ti ti-send me-1"></i>Kirim Balasan</button>
            </div>
        </form>
    </div>
</div>
@endsection
