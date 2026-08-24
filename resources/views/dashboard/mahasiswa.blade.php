@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-pretitle', $greeting . ',')
@section('page-title', auth()->user()->name)

@section('content')
<div class="d-none d-md-block">@include('partials.welcome-banner')</div>

{{-- Di HP, mata kuliah adalah pintu masuk utama. --}}
<section class="d-md-none mb-3" aria-labelledby="mobile-courses-title">
    <div class="d-flex align-items-end mb-2">
        <h2 class="h2 mb-0" id="mobile-courses-title">Mata Kuliah Saya</h2>
        <a href="{{ route('courses.index') }}" class="ms-auto small fw-bold text-decoration-none">Lihat semua</a>
    </div>
    @if ($courses->isEmpty())
        <div class="card"><div class="card-body text-center py-4">
            <span class="avatar avatar-lg bg-primary-lt mb-2"><i class="ti ti-books fs-1"></i></span>
            <div class="fw-bold">Belum ada mata kuliah</div>
            <div class="text-secondary small mb-3">Gabung menggunakan kode kelas dari dosen.</div>
            <a href="{{ route('enrollments.join.show') }}" class="btn btn-primary w-100"><i class="ti ti-key me-1"></i>Gabung Kelas</a>
        </div></div>
    @else
        <div class="card overflow-hidden">
            <div class="list-group list-group-flush">
                @foreach ($courses as $course)
                    <a href="{{ route('courses.show', $course) }}" class="list-group-item list-group-item-action d-flex align-items-center gap-2 py-2 px-3">
                        <span class="avatar avatar-sm bg-{{ $course->color() }}-lt flex-shrink-0"><i class="ti ti-book-2"></i></span>
                        <div class="min-w-0 flex-fill">
                            <div class="fw-bold text-truncate">{{ $course->name }}</div>
                            <div class="text-secondary small text-truncate">{{ $course->lecturer->name }}</div>
                        </div>
                        <i class="ti ti-chevron-right text-secondary flex-shrink-0"></i>
                    </a>
                @endforeach
            </div>
        </div>
    @endif
</section>

{{-- Ringkasan pusat aktivitas: tampilkan tindakan paling relevan. --}}
@if ($activity['total'] > 0)
    @php($next = $activity['actions']->first() ?? $activity['updates']->first())
    <div class="card mb-3 border-primary overflow-hidden student-priority-card" id="pengingat" style="scroll-margin-top:5rem">
        <div class="card-body">
            <div class="d-flex align-items-start gap-3">
                <span class="avatar avatar-md bg-orange-lt flex-shrink-0"><i class="ti ti-bolt fs-2"></i></span>
                <div class="min-w-0 flex-fill">
                    <div class="text-uppercase text-primary fw-bold" style="font-size:.68rem;letter-spacing:.05em">Perlu perhatian</div>
                    <div class="fw-bold fs-3 responsive-item-title">{{ $next['title'] }}</div>
                    <div class="text-secondary small responsive-item-title">{{ $next['subtitle'] }}</div>
                    <div class="small text-orange mt-1">{{ $next['meta'] }}</div>
                </div>
            </div>
            <a href="{{ route('activities.index') }}" class="btn btn-primary w-100 mt-3"><i class="ti ti-list-check me-1"></i>Lihat {{ $activity['total'] }} Aktivitas</a>
        </div>
    </div>
@endif

<div class="row row-deck row-cards">
    {{-- Stat cards (dapat diklik ke halaman terkait) --}}
    @foreach ([
        ['Kelas Diikuti', $stats['courses'], 'ti-school', 'primary', route('courses.index')],
        ['Tugas Pending', $stats['pending'], 'ti-checklist', 'orange', $pending->isNotEmpty() ? route('assignments.show', $pending->first()) : null],
        ['Rata-rata Hadir', is_null($stats['attendance']) ? '—' : $stats['attendance'].'%', 'ti-qrcode', 'green', null],
        ['Notif Baru', $stats['unread'], 'ti-bell', 'azure', route('notifications.index')],
    ] as [$label, $value, $icon, $color, $href])
        <div class="col-6 col-lg-3 d-none d-md-block">
            @if ($href)<a href="{{ $href }}" class="card card-sm card-link card-link-pop">@else<div class="card card-sm">@endif
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto"><span class="bg-{{ $color }} text-white avatar"><i class="ti {{ $icon }} fs-2"></i></span></div>
                        <div class="col"><div class="font-weight-medium">{{ $value }}</div><div class="text-secondary">{{ $label }}</div></div>
                    </div>
                </div>
            @if ($href)</a>@else</div>@endif
        </div>
    @endforeach

    {{-- Tugas mendatang --}}
    <div class="col-lg-6 d-none d-md-block" id="tugas" style="scroll-margin-top:5rem">
        <div class="card">
            <div class="card-header d-flex align-items-center">
                <div><div class="text-secondary small">Perlu tindakan</div><h3 class="card-title">Tugas Mendatang</h3></div>
                @if ($pending->isNotEmpty())<span class="badge bg-orange-lt ms-auto">{{ $pending->count() }} belum selesai</span>@endif
            </div>
            @if ($pending->isEmpty())
                <div class="card-body"><x-empty-state icon="ti-circle-check" title="Tidak ada tugas pending" description="Semua tugas sudah dikumpulkan." /></div>
            @else
                <div class="list-group list-group-flush">
                    @foreach ($pending->groupBy('course_id') as $courseAssignments)
                        @php($pendingCourse = $courseAssignments->first()->course)
                        <a href="{{ route('assignments.index', $pendingCourse) }}" class="list-group-item list-group-item-action d-flex align-items-center gap-2">
                            <span class="avatar bg-{{ $pendingCourse->color() }}-lt me-2"><i class="ti ti-book-2"></i></span>
                            <div class="me-auto">
                                <div class="fw-bold">{{ $pendingCourse->name }}</div>
                                <div class="text-secondary small">{{ $courseAssignments->count() }} tugas/kuis belum selesai</div>
                            </div>
                            <i class="ti ti-chevron-right text-secondary"></i>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Pertemuan mendatang --}}
    <div class="col-lg-6">
        <section class="d-md-none" aria-labelledby="mobile-meetings-title">
            <div class="d-flex align-items-end mb-2">
                <h2 class="h2 mb-0" id="mobile-meetings-title">Pertemuan Berikutnya</h2>
                <a href="{{ route('calendar') }}" class="ms-auto small fw-bold text-decoration-none">Lihat kalender</a>
            </div>
            @if ($upcomingMeetings->isEmpty())
                <div class="card"><div class="card-body py-3"><x-empty-state icon="ti-calendar-off" title="Tidak ada jadwal mendatang" /></div></div>
            @else
                <div class="card overflow-hidden">
                    <div class="list-group list-group-flush">
                        @foreach ($upcomingMeetings as $m)
                            <a href="{{ route('courses.show', $m->course) }}" class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-2 px-3">
                                <time datetime="{{ $m->date->format('Y-m-d') }}" class="text-center flex-shrink-0" style="width:2.5rem">
                                    <span class="d-block fw-bold fs-3 lh-1">{{ $m->date->format('d') }}</span>
                                    <span class="d-block text-secondary text-uppercase" style="font-size:.68rem">{{ $m->date->translatedFormat('M') }}</span>
                                </time>
                                <div class="min-w-0 flex-fill">
                                    <div class="fw-semibold text-truncate">{{ $m->topic }}</div>
                                    <div class="small text-secondary text-truncate">{{ $m->course->name }} · P{{ $m->number }}</div>
                                </div>
                                <i class="ti ti-chevron-right text-secondary flex-shrink-0"></i>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </section>

        <div class="card d-none d-md-block">
            <div class="card-header"><div><div class="text-secondary small">Agenda</div><h3 class="card-title">Jadwal Pertemuan</h3></div></div>
            @if ($upcomingMeetings->isEmpty())
                <div class="card-body"><x-empty-state icon="ti-calendar-off" title="Tidak ada jadwal mendatang" /></div>
            @else
                <div class="list-group list-group-flush">
                    @foreach ($upcomingMeetings as $m)
                        <div class="list-group-item d-flex align-items-center">
                            <span class="avatar bg-azure-lt me-2"><i class="ti ti-calendar-event"></i></span>
                            <div class="me-auto">
                                <div class="fw-bold">Pertemuan {{ $m->number }} — {{ $m->topic }}</div>
                                <div class="text-secondary small">{{ $m->course->name }}</div>
                            </div>
                            <span class="text-secondary small text-end">{{ $m->date->translatedFormat('d M') }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Kelas saya --}}
    <div class="col-lg-6 d-none d-md-block">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Kelas Saya</h3></div>
            @if ($courses->isEmpty())
                <div class="card-body"><x-empty-state icon="ti-school" title="Belum terdaftar di kelas" /></div>
            @else
                <div class="list-group list-group-flush">
                    @foreach ($courses as $course)
                        <a href="{{ route('courses.show', $course) }}" class="list-group-item list-group-item-action d-flex align-items-center">
                            <span class="avatar bg-primary-lt me-2"><i class="ti ti-book"></i></span>
                            <div><div class="fw-bold">{{ $course->name }}</div>
                                <div class="text-secondary small">{{ $course->lecturer->name }}</div></div>
                            <i class="ti ti-chevron-right ms-auto text-secondary"></i>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
