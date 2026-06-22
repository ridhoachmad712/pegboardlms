@extends('layouts.app')

@section('title', 'Pengumuman')

@section('hero-actions')
    @if (auth()->user()->isDosen())
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-announce"><i class="ti ti-plus me-1"></i>Buat Pengumuman</button>
    @endif
@endsection

@section('content')
@include('courses._hero')

<div class="row justify-content-center">
    <div class="col-lg-8">
        @forelse ($announcements as $a)
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex">
                        <span class="avatar bg-orange-lt me-3"><i class="ti ti-speakerphone"></i></span>
                        <div class="flex-fill">
                            <div class="d-flex">
                                <h3 class="card-title mb-0">{{ $a->title }}</h3>
                                @if (auth()->user()->isDosen())
                                    <form method="POST" action="{{ route('announcements.destroy', $a) }}" class="ms-auto" data-confirm="Hapus pengumuman?">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-ghost-danger"><i class="ti ti-trash"></i></button>
                                    </form>
                                @endif
                            </div>
                            <div class="text-secondary small mb-2">{{ $a->author->name }} · {{ $a->created_at->translatedFormat('d M Y H:i') }}</div>
                            <div style="white-space:pre-line">{{ $a->content }}</div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="card"><div class="card-body">
                <x-empty-state icon="ti-speakerphone" title="Belum ada pengumuman"
                    :description="auth()->user()->isDosen() ? 'Sampaikan informasi penting ke seluruh mahasiswa kelas.' : 'Dosen belum membuat pengumuman.'">
                    @if (auth()->user()->isDosen())
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-announce"><i class="ti ti-plus me-1"></i>Buat Pengumuman</button>
                    @endif
                </x-empty-state>
            </div></div>
        @endforelse

        @if ($announcements->hasPages())
            <div class="mt-2">{{ $announcements->links() }}</div>
        @endif
    </div>
</div>

@if (auth()->user()->isDosen())
    <div class="modal modal-blur fade" id="modal-announce" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content" method="POST" action="{{ route('announcements.store', $course) }}">
                @csrf
                <div class="modal-header"><h5 class="modal-title">Buat Pengumuman</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label required">Judul</label><input type="text" name="title" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label required">Isi</label><textarea name="content" class="form-control" rows="5" required></textarea></div>
                    <div class="text-secondary small"><i class="ti ti-bell me-1"></i>Semua mahasiswa kelas akan menerima notifikasi.</div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-link" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Publikasikan</button></div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        // Buka modal otomatis bila datang dari pintasan "Buat Pengumuman" (?compose=1)
        if (new URLSearchParams(location.search).has('compose') && window.bootstrap) {
            var el = document.getElementById('modal-announce');
            if (el) bootstrap.Modal.getOrCreateInstance(el).show();
        }
    </script>
    @endpush
@endif
@endsection
