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
    @if (auth()->user()->isMahasiswa())
        @php($todoAssignments = $assignments->filter(fn($a) => !($mySubs[$a->id] ?? null)?->submitted_at)->sortBy(fn($a) => ($a->isPastDeadline() ? '0-' : '1-').str_pad((string) ($a->deadline?->timestamp ?? 9999999999), 10, '0', STR_PAD_LEFT)))
        @php($submittedAssignments = $assignments->filter(fn($a) => ($mySubs[$a->id] ?? null)?->submitted_at && !($mySubs[$a->id] ?? null)?->isGraded())->sortByDesc(fn($a) => ($mySubs[$a->id] ?? null)?->submitted_at))
        @php($gradedAssignments = $assignments->filter(fn($a) => ($mySubs[$a->id] ?? null)?->isGraded())->sortByDesc(fn($a) => ($mySubs[$a->id] ?? null)?->updated_at))
        @php($defaultAssignmentTab = $todoAssignments->isNotEmpty() ? 'todo' : ($submittedAssignments->isNotEmpty() ? 'submitted' : 'graded'))
        @php($assignmentTabs = [['todo', 'Perlu dikerjakan', $todoAssignments, 'orange', 'ti-clock'], ['submitted', 'Menunggu nilai', $submittedAssignments, 'azure', 'ti-hourglass'], ['graded', 'Selesai', $gradedAssignments, 'green', 'ti-circle-check']])
        <div class="d-md-none assignment-mobile-sections" x-data="{ tab: '{{ $defaultAssignmentTab }}' }">
            <div class="assignment-tabs mb-3" role="tablist" aria-label="Status tugas">
                @foreach ($assignmentTabs as [$tabKey, $tabLabel, $items, $tabColor, $tabIcon])
                    @if ($items->isNotEmpty())
                        <button type="button" class="assignment-tab" :class="tab === '{{ $tabKey }}' ? 'active' : ''" @click="tab = '{{ $tabKey }}'" role="tab" :aria-selected="tab === '{{ $tabKey }}'">
                            <i class="ti {{ $tabIcon }}"></i><span>{{ $tabLabel }}</span><span class="badge bg-{{ $tabColor }}-lt">{{ $items->count() }}</span>
                        </button>
                    @endif
                @endforeach
            </div>

            @foreach ($assignmentTabs as [$tabKey, $tabLabel, $items, $tabColor, $tabIcon])
                @if ($items->isNotEmpty())
                    <section x-show="tab === '{{ $tabKey }}'" x-cloak role="tabpanel">
                        <div class="card overflow-hidden">
                            <div class="list-group list-group-flush">
                                @foreach ($items as $a)
                                    @php($sub = $mySubs[$a->id] ?? null)
                                    @php($learningStatus = $sub?->isGraded() ? 'graded' : ($sub?->submitted_at ? ($sub->isLate() ? 'late' : 'submitted') : ($a->isPastDeadline() ? 'overdue' : 'not_started')))
                                    <a href="{{ route('assignments.show', $a) }}" class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3">
                                        <span class="avatar bg-{{ $a->isQuiz() ? 'purple' : 'blue' }}-lt flex-shrink-0"><i class="ti {{ $a->isQuiz() ? 'ti-help-circle' : 'ti-file-text' }}"></i></span>
                                        <div class="min-w-0 flex-fill">
                                            <div class="d-flex align-items-start gap-1"><span class="fw-bold line-clamp-2">{{ $a->title }}</span>@if($a->isGroup())<i class="ti ti-users text-secondary flex-shrink-0 mt-1" title="Tugas kelompok" aria-label="Tugas kelompok"></i>@endif</div>
                                            <div class="d-flex flex-wrap align-items-center gap-1 mt-1">
                                                <span class="badge bg-{{ $a->isQuiz() ? 'purple' : 'blue' }}-lt">{{ $a->isQuiz() ? 'Kuis' : 'Tugas' }}</span>
                                                <x-learning-status :status="$learningStatus" :score="$sub?->score" />
                                            </div>
                                            <div class="text-secondary small mt-1">
                                                @if ($a->deadline)
                                                    <i class="ti ti-calendar-event me-1"></i>{{ $a->deadline->translatedFormat('d M Y, H:i') }}
                                                @else
                                                    <i class="ti ti-calendar-off me-1"></i>Tanpa deadline
                                                @endif
                                            </div>
                                        </div>
                                        <i class="ti ti-chevron-right text-secondary flex-shrink-0"></i>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </section>
                @endif
            @endforeach
        </div>
    @endif

    <div @class(['row row-cards', 'd-none d-md-flex' => auth()->user()->isMahasiswa()])>
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
                                        @php($learningStatus = $sub?->isGraded() ? 'graded' : ($sub?->submitted_at ? ($sub->isLate() ? 'late' : 'submitted') : ($a->isPastDeadline() ? 'overdue' : 'not_started')))
                                        <x-learning-status :status="$learningStatus" :score="$sub?->score" />
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

@push('styles')
<style>
@media (max-width:575.98px){
    .assignment-tabs{display:flex;gap:.5rem;overflow-x:auto;padding-bottom:.2rem;scrollbar-width:none;}
    .assignment-tabs::-webkit-scrollbar{display:none;}
    .assignment-tab{display:inline-flex;align-items:center;gap:.35rem;min-height:2.75rem;padding:.45rem .7rem;flex:0 0 auto;border:1px solid var(--tblr-border-color);border-radius:.75rem;background:var(--tblr-bg-surface);color:var(--tblr-secondary-color);font-size:.75rem;font-weight:600;transition:color .15s ease,background-color .15s ease,border-color .15s ease;}
    .assignment-tab:hover,.assignment-tab:focus{color:var(--tblr-primary);border-color:rgba(var(--tblr-primary-rgb),.35);background:rgba(var(--tblr-primary-rgb),.06);}
    .assignment-tab.active{color:var(--tblr-primary);border-color:rgba(var(--tblr-primary-rgb),.45);background:rgba(var(--tblr-primary-rgb),.1);}
}
</style>
@endpush
