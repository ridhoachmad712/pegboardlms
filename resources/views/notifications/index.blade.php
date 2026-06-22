@extends('layouts.app')

@section('title', 'Notifikasi')
@section('page-title', 'Notifikasi')

@section('page-actions')
    <form method="POST" action="{{ route('notifications.readAll') }}">@csrf
        <button class="btn"><i class="ti ti-checks me-1"></i>Tandai semua dibaca</button>
    </form>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            @if ($notifications->isEmpty())
                <div class="card-body"><x-empty-state icon="ti-bell-off" title="Belum ada notifikasi" /></div>
            @else
                <div class="list-group list-group-flush">
                    @foreach ($notifications as $n)
                        <a href="{{ route('notifications.read', $n) }}" class="list-group-item list-group-item-action">
                            <div class="d-flex align-items-center">
                                @if ($n->isUnread())<span class="status-dot status-dot-animated bg-red me-2"></span>@else<span class="status-dot me-2"></span>@endif
                                <div>
                                    <div class="fw-bold {{ $n->isUnread() ? '' : 'text-secondary' }}">{{ $n->title }}</div>
                                    @if ($n->message)<div class="small text-secondary">{{ $n->message }}</div>@endif
                                    <div class="small text-secondary">{{ $n->created_at->translatedFormat('d M Y H:i') }}</div>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
        <div class="mt-3">{{ $notifications->links() }}</div>
    </div>
</div>
@endsection
