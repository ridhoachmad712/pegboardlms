@extends('layouts.app')

@section('title', 'Mata Kuliah Saya')
@section('page-pretitle', 'Perkuliahan')
@section('page-title', 'Mata Kuliah Saya')

@section('content')
@if ($courses->isEmpty())
    <div class="card">
        <div class="card-body">
            <x-empty-state icon="ti-books" title="Belum ada mata kuliah"
                description="Punya kode dari dosen? Klik Gabung Kelas. Atau tunggu dosen menambahkan Anda.">
                <a href="{{ route('enrollments.join.show') }}" class="btn btn-primary"><i class="ti ti-key me-1"></i>Gabung Kelas</a>
            </x-empty-state>
        </div>
    </div>
@else
    {{-- Mobile: list ringkas, seluruh baris dapat disentuh. --}}
    <div class="card d-md-none overflow-hidden">
        <div class="list-group list-group-flush">
            @foreach ($courses as $course)
                <a href="{{ route('courses.show', $course) }}" class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3">
                    <span class="avatar avatar-md bg-{{ $course->color() }}-lt flex-shrink-0"><i class="ti ti-book-2 fs-2"></i></span>
                    <div class="min-w-0 flex-fill">
                        <div class="fw-bold line-clamp-2">{{ $course->name }}</div>
                        <div class="text-secondary small line-clamp-1">{{ $course->lecturer->name }}</div>
                    </div>
                    <i class="ti ti-chevron-right text-secondary flex-shrink-0"></i>
                </a>
            @endforeach
        </div>
    </div>

    {{-- Tablet/desktop: pertahankan card grid. --}}
    <div class="row row-cards d-none d-md-flex">
        @foreach ($courses as $course)
            <div class="col-md-6 col-lg-4">
                <div class="card card-lift overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-1">
                            <span class="avatar bg-{{ $course->color() }}-lt me-2"><i class="ti ti-school"></i></span>
                            <div class="min-w-0"><h3 class="card-title mb-0 text-truncate">{{ $course->name }}</h3><div class="text-secondary small text-truncate">{{ $course->lecturer->name }}</div></div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <a href="{{ route('courses.show', $course) }}" class="btn w-100">
                            <i class="ti ti-folder-open me-1"></i>Buka Kelas
                        </a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif

@if ($courses->isNotEmpty())
    <div class="mt-4 pb-2">
        <a href="{{ route('enrollments.join.show') }}" class="btn btn-primary w-100"><i class="ti ti-key me-1"></i>Gabung Kelas</a>
    </div>
@endif
@endsection
