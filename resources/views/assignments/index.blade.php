@extends('layouts.app')

@section('title', 'Tugas & Kuis')

@section('hero-actions')
    @if (auth()->user()->isDosen() && ! $course->isCompleted())
        <a href="{{ route('assignments.create', [$course, 'type' => 'tugas']) }}" class="btn btn-primary"><i class="ti ti-plus me-1"></i>Tugas</a>
        <a href="{{ route('assignments.create', [$course, 'type' => 'kuis']) }}" class="btn btn-outline-primary"><i class="ti ti-plus me-1"></i>Kuis</a>
    @endif
@endsection

@section('content')
@include('courses._hero')

@if ($assignments->isEmpty())
    <div class="card"><div class="card-body">
        <x-empty-state icon="ti-checklist" title="Belum ada tugas atau kuis"
            :description="auth()->user()->isDosen() ? 'Buat tugas atau kuis pertama untuk kelas ini.' : 'Belum ada yang diberikan dosen.'">
            @if (auth()->user()->isDosen() && ! $course->isCompleted())
                <div class="btn-list">
                    <a href="{{ route('assignments.create', [$course, 'type' => 'tugas']) }}" class="btn btn-primary"><i class="ti ti-plus me-1"></i>Buat Tugas</a>
                    <a href="{{ route('assignments.create', [$course, 'type' => 'kuis']) }}" class="btn btn-outline-primary"><i class="ti ti-plus me-1"></i>Buat Kuis</a>
                </div>
            @endif
        </x-empty-state>
    </div></div>
@else
    <div class="row row-cards">
        @foreach ($assignments as $a)
            @php($sub = $mySubs[$a->id] ?? null)
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex">
                            <span class="avatar bg-{{ $a->isQuiz() ? 'purple' : 'blue' }}-lt me-3"><i class="ti {{ $a->isQuiz() ? 'ti-help-circle' : 'ti-file-text' }}"></i></span>
                            <div class="flex-fill">
                                <div class="d-flex">
                                    <a href="{{ route('assignments.show', $a) }}" class="fw-bold text-reset">{{ $a->title }}</a>
                                    <span class="badge bg-{{ $a->isQuiz() ? 'purple' : 'blue' }}-lt ms-auto text-uppercase">{{ $a->type }}</span>
                                </div>
                                <div class="mt-1 d-flex align-items-center gap-2">
                                    <x-due :date="$a->deadline" />
                                    @if ($a->deadline)<span class="text-secondary small">{{ $a->deadline->translatedFormat('d M Y H:i') }}</span>@endif
                                </div>
                                <div class="mt-2">
                                    @if (auth()->user()->isDosen() && ! $course->isCompleted())
                                        <span class="text-secondary small"><i class="ti ti-users"></i> {{ $a->submissions_count }} pengumpulan</span>
                                    @else
                                        @if ($sub && $sub->isGraded())
                                            <span class="badge bg-green-lt">Nilai: {{ rtrim(rtrim($sub->score, '0'), '.') }}</span>
                                        @elseif ($sub)
                                            <span class="badge bg-azure-lt">Sudah dikumpulkan</span>
                                        @elseif ($a->isPastDeadline())
                                            <span class="badge bg-red-lt">Terlewat</span>
                                        @else
                                            <span class="badge bg-yellow-lt">Belum dikerjakan</span>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <a href="{{ route('assignments.show', $a) }}" class="btn btn-sm">Buka</a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
@endsection
