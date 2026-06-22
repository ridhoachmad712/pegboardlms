@extends('layouts.app')

@section('title', 'Kalender')
@section('page-pretitle', 'Jadwal')
@section('page-title', 'Kalender')

@section('page-actions')
    <div class="btn-list align-items-center">
        <a href="{{ route('calendar', ['month' => $prevMonth]) }}" class="btn btn-icon" title="Bulan sebelumnya" aria-label="Bulan sebelumnya" data-bs-toggle="tooltip"><i class="ti ti-chevron-left"></i></a>
        <span class="fw-bold px-1" style="min-width:9rem;text-align:center">{{ $cursor->translatedFormat('F Y') }}</span>
        <a href="{{ route('calendar', ['month' => $nextMonth]) }}" class="btn btn-icon" title="Bulan berikutnya" aria-label="Bulan berikutnya" data-bs-toggle="tooltip"><i class="ti ti-chevron-right"></i></a>
        <a href="{{ route('calendar') }}" class="btn">Hari ini</a>
    </div>
@endsection

@section('content')
@php($cap = 3)

{{-- Grid kalender — disembunyikan di layar kecil (pakai daftar di bawah) --}}
<div class="card mb-3 d-none d-md-block">
    <div class="card-header py-2">
        <h3 class="card-title">{{ $cursor->translatedFormat('F Y') }}</h3>
        <div class="card-actions small text-secondary d-flex gap-3">
            <span><i class="ti ti-square-rounded-filled text-blue"></i> Pertemuan</span>
            <span><i class="ti ti-square-rounded-filled text-red"></i> Deadline</span>
        </div>
    </div>
    <div class="card-body p-2">
        <div class="row g-1 text-center fw-bold small mb-1">
            @foreach (['Min','Sen','Sel','Rab','Kam','Jum','Sab'] as $dn)
                <div class="col {{ $dn === 'Min' ? 'text-red' : 'text-secondary' }}">{{ $dn }}</div>
            @endforeach
        </div>
        @foreach (array_chunk($days, 7) as $week)
            <div class="row g-1 mb-1">
                @foreach ($week as $day)
                    @php($key = $day->format('Y-m-d'))
                    @php($inMonth = $day->month === $cursor->month)
                    @php($ev = $events[$key] ?? [])
                    @php($evMeetings = $ev['meetings'] ?? [])
                    @php($evDeadlines = $ev['deadlines'] ?? [])
                    @php($total = count($evMeetings) + count($evDeadlines))
                    @php($shown = 0)
                    <div class="col">
                        <div class="border rounded p-1 h-100 cal-cell
                                    {{ $inMonth ? '' : 'cal-out' }}
                                    {{ $inMonth && $day->isSaturday() ? 'cal-weekend' : '' }}
                                    {{ $inMonth && $day->isSunday() ? 'cal-sunday' : '' }}
                                    {{ $day->isToday() ? 'border-primary border-2' : '' }}">
                            <div class="mb-1">
                                @if ($day->isToday())
                                    <span class="cal-today-num">{{ $day->day }}</span>
                                @else
                                    <span class="small {{ $day->isSunday() ? 'text-red fw-bold' : 'text-secondary' }}">{{ $day->day }}</span>
                                @endif
                            </div>
                            @foreach ($evMeetings as $m)
                                @if ($shown < $cap)
                                    <a href="{{ route('courses.show', $m->course) }}" class="d-block text-truncate badge bg-blue-lt w-100 text-start mb-1 cal-ev" title="P{{ $m->number }} — {{ $m->course->name }}: {{ $m->topic }}">
                                        <i class="ti ti-calendar-event"></i> P{{ $m->number }} · {{ $m->course->name }}
                                    </a>
                                    @php($shown++)
                                @endif
                            @endforeach
                            @foreach ($evDeadlines as $a)
                                @if ($shown < $cap)
                                    <a href="{{ route('assignments.show', $a) }}" class="d-block text-truncate badge bg-red-lt w-100 text-start mb-1 cal-ev" title="Deadline: {{ $a->title }} — {{ $a->course->name }}">
                                        <i class="ti ti-clock"></i> {{ $a->title }}
                                    </a>
                                    @php($shown++)
                                @endif
                            @endforeach
                            @if ($total > $cap)
                                <div class="small text-secondary px-1">+{{ $total - $cap }} lainnya</div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>
</div>

<div class="row row-cards">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title"><i class="ti ti-calendar-event text-blue me-1"></i>Pertemuan bulan ini</h3></div>
            @if ($meetings->isEmpty())
                <div class="card-body text-secondary small">Tidak ada pertemuan.</div>
            @else
                <div class="list-group list-group-flush">
                    @foreach ($meetings as $m)
                        <a href="{{ route('courses.show', $m->course) }}" class="list-group-item list-group-item-action">
                            <div class="fw-bold">P{{ $m->number }} — {{ $m->topic }}</div>
                            <div class="small text-secondary">{{ $m->course->name }} · {{ $m->date->translatedFormat('d M Y') }}</div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title"><i class="ti ti-clock text-red me-1"></i>Deadline bulan ini</h3></div>
            @if ($deadlines->isEmpty())
                <div class="card-body text-secondary small">Tidak ada deadline.</div>
            @else
                <div class="list-group list-group-flush">
                    @foreach ($deadlines as $a)
                        <a href="{{ route('assignments.show', $a) }}" class="list-group-item list-group-item-action">
                            <div class="fw-bold">{{ $a->title }}</div>
                            <div class="small text-secondary">{{ $a->course->name }} · {{ $a->deadline->translatedFormat('d M Y H:i') }}</div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .cal-cell{min-height:96px;}
    .cal-out{background:var(--tblr-bg-surface-secondary);opacity:.55;}
    .cal-weekend{background:var(--tblr-bg-surface-secondary);}
    .cal-sunday{background:rgba(var(--tblr-red-rgb), .06);}
    .cal-ev{font-size:.7rem;line-height:1.3;}
    .cal-today-num{display:inline-flex;align-items:center;justify-content:center;
        width:1.55rem;height:1.55rem;border-radius:50%;background:var(--tblr-primary);
        color:#fff;font-weight:600;font-size:.8rem;}
</style>
@endpush
