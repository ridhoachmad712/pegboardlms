@extends('layouts.app')

@section('title', 'Materi · '.$course->name)

@section('content')
@include('courses._hero')

@php($latestMeetingId = $course->meetings->sortByDesc('number')->first()?->id)
<div x-data="{ query: '' }">
    <div class="d-flex align-items-end mb-3">
        <div>
            <div class="text-secondary small">Bahan pembelajaran</div>
            <h2 class="h2 mb-0">Materi Kuliah</h2>
        </div>
        <span class="badge bg-secondary-lt ms-auto">{{ $course->meetings->count() }} pertemuan</span>
    </div>

    @if ($course->meetings->isNotEmpty())
        <div class="input-icon mb-3">
            <span class="input-icon-addon"><i class="ti ti-search"></i></span>
            <input type="search" class="form-control" x-model.debounce.200ms="query" placeholder="Cari pertemuan atau materi…" aria-label="Cari materi">
        </div>
    @endif

    @forelse ($course->meetings->sortByDesc('number') as $meeting)
        @php($isLatest = $meeting->id === $latestMeetingId)
        <section class="card mb-3 overflow-hidden material-meeting" x-show="query === '' || $el.textContent.toLowerCase().includes(query.toLowerCase())">
            <button type="button" class="card-header w-100 border-0 text-start d-flex align-items-start gap-2 py-3 {{ $isLatest ? '' : 'collapsed' }}"
                    data-bs-toggle="collapse" data-bs-target="#meeting-materials-{{ $meeting->id }}" aria-expanded="{{ $isLatest ? 'true' : 'false' }}" aria-controls="meeting-materials-{{ $meeting->id }}">
                <span class="badge bg-blue-lt flex-shrink-0">P{{ $meeting->number }}</span>
                <span class="min-w-0 flex-fill">
                    <span class="d-block fw-bold material-title">{{ $meeting->topic }}</span>
                    <span class="text-secondary small">
                        {{ $meeting->materials->count() }} materi
                        @if ($meeting->date) · {{ $meeting->date->translatedFormat('d M Y') }} @endif
                    </span>
                </span>
                <i class="ti ti-chevron-down material-chevron text-secondary flex-shrink-0 mt-1"></i>
            </button>

            <div class="collapse {{ $isLatest ? 'show' : '' }}" id="meeting-materials-{{ $meeting->id }}">
                <div class="list-group list-group-flush">
                    @forelse ($meeting->materials as $material)
                        @php($extension = $material->isFile() ? strtolower(pathinfo($material->path ?? '', PATHINFO_EXTENSION)) : null)
                        @if ($material->isText())
                            <div class="list-group-item">
                                <button type="button" class="w-100 border-0 bg-transparent p-0 d-flex align-items-center gap-3 text-start collapsed" data-bs-toggle="collapse" data-bs-target="#material-text-{{ $material->id }}">
                                    <span class="avatar avatar-sm bg-purple-lt"><i class="ti ti-notes"></i></span>
                                    <span class="min-w-0 flex-fill"><span class="d-block fw-bold material-title">{{ $material->title }}</span><span class="text-secondary small">Materi teks · Baca</span></span>
                                    <x-learning-status status="available" />
                                    <i class="ti ti-chevron-down material-chevron text-secondary"></i>
                                </button>
                                <div class="collapse" id="material-text-{{ $material->id }}">
                                    @if ($material->content)<div class="markdown text-secondary border-top mt-3 pt-3">{!! \Illuminate\Support\Str::markdown($material->content, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}</div>@endif
                                </div>
                            </div>
                        @elseif ($material->isFile())
                            <div class="list-group-item d-flex align-items-center gap-3">
                                <span class="avatar avatar-sm bg-blue-lt"><i class="ti ti-{{ $extension === 'pdf' ? 'file-type-pdf' : 'file-text' }}"></i></span>
                                <div class="min-w-0 flex-fill">
                                    <div class="fw-bold material-title">{{ $material->title }}</div>
                                    <div class="text-secondary small">
                                        {{ strtoupper($extension ?: 'Berkas') }}
                                        @if ($material->size_for_humans) · {{ $material->size_for_humans }} @endif
                                    </div>
                                </div>
                                <div class="d-flex gap-1 flex-shrink-0">
                                    <x-learning-status status="available" class="d-none d-sm-inline-flex" />
                                    <a href="{{ route('materials.preview', $material) }}" class="btn btn-sm btn-icon" title="Baca" aria-label="Baca {{ $material->title }}"><i class="ti ti-eye"></i></a>
                                    <a href="{{ route('materials.download', $material) }}" class="btn btn-sm btn-icon" title="Unduh" aria-label="Unduh {{ $material->title }}"><i class="ti ti-download"></i></a>
                                </div>
                            </div>
                        @else
                            @php($isVideo = $material->type === \App\Models\Material::TYPE_VIDEO)
                            <a href="{{ route('materials.preview', $material) }}" class="list-group-item list-group-item-action d-flex align-items-center gap-3">
                                <span class="avatar avatar-sm bg-{{ $isVideo ? 'red' : 'azure' }}-lt"><i class="ti ti-{{ $isVideo ? 'player-play' : 'link' }}"></i></span>
                                <span class="min-w-0 flex-fill"><span class="d-block fw-bold material-title">{{ $material->title }}</span><span class="text-secondary small">{{ $isVideo ? 'Video · Tonton' : 'Tautan · Buka' }}</span></span>
                                <x-learning-status status="available" class="d-none d-sm-inline-flex" />
                                <i class="ti ti-external-link text-secondary"></i>
                            </a>
                        @endif
                    @empty
                        <div class="list-group-item text-secondary small text-center py-3">Belum ada materi pada pertemuan ini.</div>
                    @endforelse
                </div>
            </div>
        </section>
    @empty
        <div class="card"><div class="card-body">
            <x-empty-state icon="ti-folder-off" title="Belum ada materi" description="Dosen belum menambahkan pertemuan atau bahan pembelajaran." />
        </div></div>
    @endforelse
</div>
@endsection

@push('styles')
<style>
.material-chevron{transition:transform .2s ease;}
[aria-expanded="true"] .material-chevron{transform:rotate(180deg);}
.material-meeting>.card-header:hover{background:rgba(var(--tblr-primary-rgb),.06);}
.material-title{line-height:1.35;overflow-wrap:anywhere;word-break:break-word;}
@media (prefers-reduced-motion:reduce){.material-chevron{transition:none;}}
</style>
@endpush
