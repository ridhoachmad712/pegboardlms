@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-pretitle', $greeting . ',')
@section('page-title', auth()->user()->name)

@section('content')
@include('partials.welcome-banner')

{{-- Alert kehadiran rendah --}}
@foreach ($lowAttendance as $low)
    <div class="alert alert-warning" role="alert">
        <i class="ti ti-alert-triangle me-1"></i>
        Kehadiran Anda di <strong>{{ $low['course']->name }}</strong> baru <strong>{{ $low['percent'] }}%</strong> (di bawah 75%).
    </div>
@endforeach

<div class="row row-deck row-cards">
    {{-- Stat cards --}}
    @foreach ([
        ['Kelas Diikuti', $stats['courses'], 'ti-school', 'primary'],
        ['Tugas Pending', $stats['pending'], 'ti-checklist', 'orange'],
        ['Rata-rata Hadir', is_null($stats['attendance']) ? '—' : $stats['attendance'].'%', 'ti-qrcode', 'green'],
        ['Notif Baru', $stats['unread'], 'ti-bell', 'azure'],
    ] as [$label, $value, $icon, $color])
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm"><div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto"><span class="bg-{{ $color }} text-white avatar"><i class="ti {{ $icon }} fs-2"></i></span></div>
                    <div class="col"><div class="font-weight-medium">{{ $value }}</div><div class="text-secondary">{{ $label }}</div></div>
                </div>
            </div></div>
        </div>
    @endforeach

    {{-- Tugas mendatang --}}
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Tugas Mendatang</h3></div>
            @if ($pending->isEmpty())
                <div class="card-body"><x-empty-state icon="ti-circle-check" title="Tidak ada tugas pending" description="Semua tugas sudah dikumpulkan." /></div>
            @else
                <div class="list-group list-group-flush">
                    @foreach ($pending->take(6) as $a)
                        <a href="{{ route('assignments.show', $a) }}" class="list-group-item list-group-item-action d-flex align-items-center">
                            <span class="avatar bg-{{ $a->isQuiz() ? 'purple' : 'blue' }}-lt me-2"><i class="ti {{ $a->isQuiz() ? 'ti-help-circle' : 'ti-file-text' }}"></i></span>
                            <div class="me-auto">
                                <div class="fw-bold">{{ $a->title }}</div>
                                <div class="text-secondary small">{{ $a->course->name }}</div>
                            </div>
                            <x-due :date="$a->deadline" />
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Nilai terbaru --}}
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Nilai Terbaru</h3></div>
            @if ($recentGrades->isEmpty())
                <div class="card-body"><x-empty-state icon="ti-clipboard" title="Belum ada nilai" /></div>
            @else
                <div class="list-group list-group-flush">
                    @foreach ($recentGrades as $sub)
                        <div class="list-group-item d-flex align-items-center">
                            <div class="me-auto">
                                <div class="fw-bold">{{ $sub->assignment->title }}</div>
                                <div class="text-secondary small">{{ $sub->assignment->course->name }}</div>
                            </div>
                            <span class="badge bg-green-lt fs-3">{{ rtrim(rtrim($sub->score, '0'), '.') }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Pertemuan mendatang --}}
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Jadwal Pertemuan</h3></div>
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
                            <span class="text-secondary small">{{ $m->date->translatedFormat('d M Y') }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Kelas saya --}}
    <div class="col-lg-6">
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
                                <div class="text-secondary small">{{ $course->code }} · {{ $course->lecturer->name }}</div></div>
                            <i class="ti ti-chevron-right ms-auto text-secondary"></i>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
