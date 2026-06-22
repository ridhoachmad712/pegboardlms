@extends('layouts.app')

@section('title', 'Kelas Saya')
@section('page-pretitle', 'Administrasi')
@section('page-title', 'Kelas Saya')

@section('page-actions')
    <div class="btn-list">
        <a href="{{ route('courses.trash') }}" class="btn"><i class="ti ti-trash me-1"></i>Tong Sampah</a>
        <a href="{{ route('courses.create') }}" class="btn btn-primary">
            <i class="ti ti-plus me-1"></i>Buat Kelas
        </a>
    </div>
@endsection

@section('content')
{{-- Filter Aktif / Selesai + dropdown semester --}}
@php($pp = explode('-', $periode))
@php($periodeLabel = $periode === 'all' ? 'Semua semester' : (($pp[1] ?? '').' '.($pp[0] ?? '')))
@php($ap = explode('-', $activePeriod))
@php($activeLabel = ($ap[1] ?? '').' '.($ap[0] ?? ''))
<div class="mb-3 d-flex flex-wrap align-items-center gap-2">
    <ul class="nav nav-pills gap-1">
        <li class="nav-item">
            <a class="nav-link {{ $filter === 'active' ? 'active' : '' }}" href="{{ route('courses.index', ['periode' => $periode]) }}">
                <i class="ti ti-school me-1"></i>Aktif <span class="badge bg-secondary-lt ms-1">{{ $activeCount }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $filter === 'completed' ? 'active' : '' }}" href="{{ route('courses.index', ['status' => 'completed', 'periode' => $periode]) }}">
                <i class="ti ti-circle-check me-1"></i>Selesai <span class="badge bg-secondary-lt ms-1">{{ $completedCount }}</span>
            </a>
        </li>
    </ul>

    <div class="dropdown ms-auto">
        <button class="btn dropdown-toggle" type="button" data-bs-toggle="dropdown">
            <i class="ti ti-calendar me-1"></i>{{ $periodeLabel }}
        </button>
        <div class="dropdown-menu dropdown-menu-end">
            <a class="dropdown-item {{ $periode === $activePeriod ? 'active' : '' }}" href="{{ route('courses.index', ['status' => $filter, 'periode' => $activePeriod]) }}">
                {{ $activeLabel }} <span class="badge bg-blue-lt ms-1">aktif</span>
            </a>
            @foreach ($periods as $p)
                @continue($p->key === $activePeriod)
                <a class="dropdown-item {{ $periode === $p->key ? 'active' : '' }}" href="{{ route('courses.index', ['status' => $filter, 'periode' => $p->key]) }}">{{ $p->label }}</a>
            @endforeach
            <div class="dropdown-divider"></div>
            <a class="dropdown-item {{ $periode === 'all' ? 'active' : '' }}" href="{{ route('courses.index', ['status' => $filter, 'periode' => 'all']) }}">Semua semester</a>
        </div>
    </div>
</div>

@if ($courses->isEmpty())
    <div class="card">
        <div class="card-body">
            @if ($filter === 'completed')
                <x-empty-state icon="ti-circle-check" title="Belum ada kelas selesai"
                    description="Kelas yang sudah Anda tandai selesai akan muncul di sini." />
            @else
                <x-empty-state icon="ti-school" title="Belum ada kelas aktif"
                    description="Buat kelas pertama Anda untuk mulai mengelola materi dan mahasiswa.">
                    <a href="{{ route('courses.create') }}" class="btn btn-primary"><i class="ti ti-plus me-1"></i>Buat Kelas</a>
                </x-empty-state>
            @endif
        </div>
    </div>
@else
    <div class="row row-cards">
        @foreach ($courses as $course)
            <div class="col-md-6 col-lg-4">
                <div class="card card-lift overflow-hidden {{ $course->isCompleted() ? 'course-archived' : '' }}">
                    @if ($course->isCompleted())
                        <div class="ribbon ribbon-top bg-green">Selesai</div>
                    @elseif ($course->ungraded_count > 0)
                        <a href="{{ route('assignments.index', $course) }}"
                           class="position-absolute top-0 end-0 m-2 text-decoration-none"
                           style="z-index:2" title="Pengumpulan menunggu nilai" data-bs-toggle="tooltip">
                            <span class="badge bg-orange-lt"><i class="ti ti-clipboard-list me-1"></i>{{ $course->ungraded_count }} perlu dinilai</span>
                        </a>
                    @endif
                    <div class="card-body">
                        <h3 class="card-title mb-2">{{ $course->name }}</h3>
                        <div class="text-secondary mb-2">
                            {{ $course->code }}@if ($course->class_name) · {{ $course->class_name }}@endif · {{ $course->semester }} {{ $course->year }}
                        </div>
                        <div class="row text-center">
                            <div class="col">
                                <div class="h3 mb-0">{{ $course->students_count }}</div>
                                <div class="text-secondary small">Mahasiswa</div>
                            </div>
                            <div class="col">
                                <div class="h3 mb-0">{{ $course->meetings_count }}</div>
                                <div class="text-secondary small">Pertemuan</div>
                            </div>
                        </div>
                        @php($target = 16)
                        <div class="mt-3">
                            <div class="d-flex justify-content-between mb-1 small text-secondary">
                                <span>Progres pertemuan</span>
                                <span>{{ $course->meetings_count }}/{{ $target }}</span>
                            </div>
                            <div class="progress progress-sm">
                                <div class="progress-bar" style="width: {{ min(100, round($course->meetings_count / $target * 100)) }}%"></div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        @if ($course->isCompleted())
                            <div class="btn-list">
                                <a href="{{ route('courses.show', $course) }}" class="btn flex-fill"><i class="ti ti-eye me-1"></i>Lihat Kelas</a>
                                <form method="POST" action="{{ route('courses.complete', $course) }}">
                                    @csrf @method('PATCH')
                                    <button class="btn btn-success" title="Buka kembali (bisa diubah lagi)" data-bs-toggle="tooltip"><i class="ti ti-lock-open me-1"></i>Buka Kembali</button>
                                </form>
                            </div>
                        @else
                            <a href="{{ route('courses.show', $course) }}" class="btn btn-primary w-100">
                                <i class="ti ti-settings me-1"></i>Kelola Kelas
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
@endsection
