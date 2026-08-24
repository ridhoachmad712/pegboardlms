@extends('layouts.app')

@section('title', 'RPS')

@section('hero-actions')
    <a href="{{ route('syllabus.pdf', $course) }}" class="btn"><i class="ti ti-file-type-pdf me-1"></i>Unduh PDF</a>
    @if (auth()->user()->isDosen() && $course->user_id === auth()->id())
        <a href="{{ route('syllabus.edit', $course) }}" class="btn btn-primary"><i class="ti ti-edit me-1"></i>Edit RPS</a>
    @endif
@endsection

@section('content')
@include('courses._hero')

@php($s = $course->syllabus)
@php($sections = $s ? collect([
    ['CPL — Capaian Pembelajaran Lulusan', $s->cpl, true],
    ['CPMK — Capaian Pembelajaran Mata Kuliah', $s->cpmk, true],
    ['Sub-CPMK', $s->sub_cpmk, true],
    ['Deskripsi Mata Kuliah', $s->description, false],
    ['Referensi / Pustaka', $s->references, true],
    ['Metode Penilaian', $s->assessment, false],
    ['Aturan Kelas', $s->rules, false],
])->filter(fn($section) => filled($section[1])) : collect())

<a href="{{ route('syllabus.pdf', $course) }}" class="btn w-100 mb-3 d-md-none"><i class="ti ti-file-type-pdf me-1"></i>Unduh RPS</a>

<div class="row row-cards">
    <div class="col-lg-8">
        @if (! $s)
            <div class="card"><div class="card-body">
                <x-empty-state icon="ti-file-text" title="RPS belum diisi"
                    :description="auth()->user()->isDosen() ? 'Klik Edit RPS untuk mengisi.' : 'Dosen belum mengisi RPS.'" />
            </div></div>
        @else
            <div class="d-md-none card overflow-hidden mb-3">
                @foreach ($sections as [$label, $val, $numbered])
                    <details class="rps-section" @if($loop->first) open @endif>
                        <summary class="d-flex align-items-center gap-2 fw-semibold"><span class="flex-fill">{{ $label }}</span><i class="ti ti-chevron-down"></i></summary>
                        <div class="rps-section__body">
                            @if ($numbered)
                                <ol class="mb-0 ps-3">@foreach (preg_split('/\r\n|\r|\n/', $val) as $line) @if (trim($line) !== '')<li>{{ trim($line) }}</li>@endif @endforeach</ol>
                            @else
                                <div class="content-prose" style="white-space:pre-line">{{ $val }}</div>
                            @endif
                        </div>
                    </details>
                @endforeach
            </div>
            <div class="d-none d-md-block">
            @foreach ($sections as [$label, $val, $numbered])
                    <div class="card mb-3">
                        <div class="card-header"><h3 class="card-title">{{ $label }}</h3></div>
                        <div class="card-body">
                            @if ($numbered)
                                <ol class="mb-0 ps-3">
                                    @foreach (preg_split('/\r\n|\r|\n/', $val) as $line)
                                        @if (trim($line) !== '')<li>{{ trim($line) }}</li>@endif
                                    @endforeach
                                </ol>
                            @else
                                <div class="content-prose" style="white-space:pre-line">{{ $val }}</div>
                            @endif
                        </div>
                    </div>
            @endforeach
            </div>
        @endif
    </div>

    {{-- Jadwal pertemuan --}}
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Jadwal Pertemuan</h3></div>
            @if ($course->meetings->isEmpty())
                <div class="card-body text-secondary small">Belum ada jadwal pertemuan.</div>
            @else
                <div class="list-group list-group-flush">
                    @foreach ($course->meetings as $m)
                        <div class="list-group-item">
                            <div class="fw-bold responsive-item-title">P{{ $m->number }} — {{ $m->topic }}</div>
                            <div class="text-secondary small">{{ $m->date?->translatedFormat('d M Y') ?? 'Tanggal belum ditentukan' }}</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
@media(max-width:575.98px){
    .rps-section+ .rps-section{border-top:1px solid var(--tblr-border-color);}
    .rps-section summary{padding:1rem;cursor:pointer;list-style:none;}
    .rps-section summary::-webkit-details-marker{display:none;}
    .rps-section[open] summary i{transform:rotate(180deg);}
    .rps-section__body{padding:0 1rem 1rem;color:var(--tblr-secondary-color);font-size:.875rem;}
}
</style>
@endpush
