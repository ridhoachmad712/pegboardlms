@extends('layouts.app')

@section('title', 'Dashboard Dosen')
@section('page-pretitle', $greeting . ',')
@section('page-title', auth()->user()->name)

@php($pp = explode('-', $periode))
@php($periodeLabel = $periode === 'all' ? 'Semua semester' : (($pp[1] ?? '').' '.($pp[0] ?? '')))
@php($ap = explode('-', $activePeriod))
@php($activeLabel = ($ap[1] ?? '').' '.($ap[0] ?? ''))

@section('page-actions')
    <div class="btn-list">
        <div class="dropdown">
            <button class="btn dropdown-toggle" type="button" data-bs-toggle="dropdown">
                <i class="ti ti-calendar me-1"></i>{{ $periodeLabel }}
            </button>
            <div class="dropdown-menu dropdown-menu-end">
                <a class="dropdown-item {{ $periode === $activePeriod ? 'active' : '' }}" href="{{ route('dashboard.dosen', ['periode' => $activePeriod]) }}">
                    {{ $activeLabel }} <span class="badge bg-blue-lt ms-1">aktif</span>
                </a>
                @foreach ($periods as $p)
                    @continue($p->key === $activePeriod)
                    <a class="dropdown-item {{ $periode === $p->key ? 'active' : '' }}" href="{{ route('dashboard.dosen', ['periode' => $p->key]) }}">{{ $p->label }}</a>
                @endforeach
                <div class="dropdown-divider"></div>
                <a class="dropdown-item {{ $periode === 'all' ? 'active' : '' }}" href="{{ route('dashboard.dosen', ['periode' => 'all']) }}">Semua semester</a>
            </div>
        </div>
    </div>
@endsection

@section('content')
@if ($periods->isEmpty())
    @include('partials.welcome-banner')
@endif

{{-- Quick actions --}}
<div class="btn-list mb-3">
    <a href="{{ route('courses.create') }}" class="btn btn-primary"><i class="ti ti-plus me-1"></i>Buat Kelas</a>
    @if ($activeCourses->isNotEmpty())
        <div class="dropdown">
            <button class="btn dropdown-toggle" type="button" data-bs-toggle="dropdown"><i class="ti ti-speakerphone me-1"></i>Buat Pengumuman</button>
            <div class="dropdown-menu">
                @foreach ($activeCourses as $c)
                    <a class="dropdown-item" href="{{ route('announcements.index', ['course' => $c, 'compose' => 1]) }}">{{ $c->name }}@if ($c->class_name) — {{ $c->class_name }}@endif</a>
                @endforeach
            </div>
        </div>
    @endif
    @if ($todayMeetings->isNotEmpty())
        <div class="dropdown">
            <button class="btn dropdown-toggle" type="button" data-bs-toggle="dropdown"><i class="ti ti-qrcode me-1"></i>Absensi Hari Ini</button>
            <div class="dropdown-menu">
                @foreach ($todayMeetings as $m)
                    <a class="dropdown-item" href="{{ route('attendance.session', $m) }}">{{ $m->course->name }}@if ($m->course->class_name) ({{ $m->course->class_name }})@endif · P{{ $m->number }}</a>
                @endforeach
            </div>
        </div>
    @endif
    <a href="{{ route('calendar') }}" class="btn"><i class="ti ti-calendar me-1"></i>Kalender</a>
</div>

{{-- Pusat tindakan: satu daftar gabungan --}}
@php($totalActions = $needGrading->count() + $needAttendance->count())
@php($shownGrading = $needGrading->take(6))
@php($shownAttendance = $needAttendance->take(6))
@php($hiddenActions = $totalActions - $shownGrading->count() - $shownAttendance->count())
<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title"><i class="ti ti-checklist me-1"></i>Perlu Tindakan</h3>
        @if ($totalActions > 0)<span class="badge bg-red text-white ms-2">{{ $totalActions }}</span>@endif
    </div>
    @if ($totalActions === 0)
        <div class="card-body text-center text-secondary py-4">
            <i class="ti ti-circle-check text-green" style="font-size:2.25rem"></i>
            <div class="mt-2">Tidak ada tindakan mendesak — semuanya sudah ditangani 🎉</div>
        </div>
    @else
        <div class="list-group list-group-flush">
            @foreach ($shownGrading as $a)
                <a href="{{ route('assignments.show', $a) }}" class="list-group-item list-group-item-action d-flex align-items-center">
                    <span class="avatar avatar-sm bg-orange-lt me-3"><i class="ti ti-clipboard-list"></i></span>
                    <div class="me-auto">
                        <div class="fw-bold">{{ $a->title }}</div>
                        <div class="small text-secondary">{{ $a->course->name }}@if ($a->course->class_name) ({{ $a->course->class_name }})@endif · {{ $a->isQuiz() ? 'Kuis' : 'Tugas' }}</div>
                    </div>
                    <span class="badge bg-orange-lt ms-2 flex-shrink-0">{{ $a->ungraded_count }} perlu dinilai</span>
                </a>
            @endforeach
            @foreach ($shownAttendance as $m)
                <a href="{{ route('attendance.session', $m) }}" class="list-group-item list-group-item-action d-flex align-items-center">
                    <span class="avatar avatar-sm bg-azure-lt me-3"><i class="ti ti-qrcode"></i></span>
                    <div class="me-auto">
                        <div class="fw-bold">P{{ $m->number }} — {{ $m->topic }}</div>
                        <div class="small text-secondary">{{ $m->course->name }}@if ($m->course->class_name) ({{ $m->course->class_name }})@endif · {{ $m->date->translatedFormat('d M Y') }}</div>
                    </div>
                    <span class="badge bg-azure-lt ms-2 flex-shrink-0">Buka absensi</span>
                </a>
            @endforeach
            @if ($hiddenActions > 0)
                <div class="list-group-item text-secondary small text-center">+{{ $hiddenActions }} lainnya</div>
            @endif
        </div>
    @endif
</div>

@php($statCards = [
    ['Kelas Aktif', $stats['active_courses'], 'ti-school', 'primary', route('courses.index', ['periode' => $periode])],
    ['Mahasiswa', $stats['students'], 'ti-users', 'green', route('admin.students.index')],
    ['Mata Kuliah', $stats['subjects'], 'ti-book', 'azure', route('courses.index', ['periode' => $periode])],
    ['Tugas & Kuis', $stats['assignments'], 'ti-checklist', 'purple', null],
])

<div class="d-flex align-items-center mb-2">
    <h3 class="mb-0">Ringkasan</h3>
    <span class="text-secondary ms-2">· {{ $periodeLabel }}</span>
</div>

<div class="row row-deck row-cards">
    @foreach ($statCards as [$label, $value, $icon, $color, $url])
        <div class="col-6 col-md-3">@include('dashboard._stat-card')</div>
    @endforeach

    {{-- Daftar kelas --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Kelas Saya <span class="text-secondary fw-normal">· {{ $periodeLabel }}</span></h3>
                <div class="card-actions">
                    <a href="{{ route('courses.index', ['periode' => $periode]) }}" class="btn btn-link">Lihat semua</a>
                </div>
            </div>
            @if ($activeCourses->isEmpty())
                <div class="card-body">
                    @if ($periods->isEmpty())
                        {{-- Benar-benar belum punya kelas sama sekali --}}
                        <x-empty-state icon="ti-school" title="Belum ada kelas"
                            description="Mulai dengan membuat kelas pertama Anda.">
                            <a href="{{ route('courses.create') }}" class="btn btn-primary"><i class="ti ti-plus me-1"></i>Buat Kelas</a>
                        </x-empty-state>
                    @elseif ($periode === 'all')
                        {{-- Punya kelas, tapi tidak ada yang berstatus aktif --}}
                        <x-empty-state icon="ti-school-off" title="Belum ada kelas aktif"
                            description="Semua kelas Anda sudah ditandai selesai.">
                            <a href="{{ route('courses.create') }}" class="btn btn-primary"><i class="ti ti-plus me-1"></i>Buat Kelas</a>
                        </x-empty-state>
                    @else
                        {{-- Punya kelas, tapi tidak di semester yang dipilih --}}
                        <x-empty-state icon="ti-calendar-off" title="Belum ada kelas di {{ $periodeLabel }}">
                            <a href="{{ route('courses.create') }}" class="btn btn-primary"><i class="ti ti-plus me-1"></i>Buat Kelas</a>
                        </x-empty-state>
                    @endif
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-vcenter card-table table-roomy">
                        <thead>
                            <tr>
                                <th>Kelas</th><th>Kode</th><th>Semester</th>
                                <th class="text-center">Mahasiswa</th><th class="text-center">Pertemuan</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($activeCourses as $course)
                                <tr>
                                    <td>
                                        <a href="{{ route('courses.show', $course) }}" class="text-reset fw-bold">{{ $course->name }}</a>
                                        @if ($course->class_name)<span class="text-secondary ms-1">· {{ $course->class_name }}</span>@endif
                                        @if ($course->ungraded_count > 0)
                                            <a href="{{ route('assignments.index', $course) }}" class="badge bg-orange-lt ms-1" title="Pengumpulan menunggu nilai">perlu dinilai: {{ $course->ungraded_count }}</a>
                                        @endif
                                    </td>
                                    <td><span class="text-secondary">{{ $course->code }}</span></td>
                                    <td>{{ $course->semester }} {{ $course->year }}</td>
                                    <td class="text-center">{{ $course->students_count }}</td>
                                    <td class="text-center">{{ $course->meetings_count }}</td>
                                    <td class="text-end">
                                        <div class="btn-list justify-content-end">
                                            <a href="{{ route('assignments.index', $course) }}" class="btn btn-sm" title="Tugas & Kuis"><i class="ti ti-checklist"></i></a>
                                            <a href="{{ route('attendance.index', $course) }}" class="btn btn-sm" title="Kehadiran"><i class="ti ti-qrcode"></i></a>
                                            <a href="{{ route('grades.index', $course) }}" class="btn btn-sm" title="Penilaian"><i class="ti ti-clipboard-check"></i></a>
                                            <a href="{{ route('courses.show', $course) }}" class="btn btn-sm btn-primary">Kelola</a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    /* Baris tabel kelas dibuat lebih lega */
    .table-roomy > :not(caption) > * > * { padding-top: 1rem; padding-bottom: 1rem; }
    /* Sapaan tidak ditampilkan huruf kapital semua */
    .page-pretitle { text-transform: none; }
</style>
@endpush
