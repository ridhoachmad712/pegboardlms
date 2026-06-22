@extends('layouts.app')

@section('title', 'Review: ' . $assignment->title)
@section('page-pretitle', $assignment->course->name . ' · Kuis')
@section('page-title', 'Review — ' . $assignment->title)

@section('content')
@php($hasEssay = $assignment->questions->contains(fn ($q) => ! $q->isPg()))

<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="card mb-3">
            <div class="card-body d-flex align-items-center">
                <div>
                    <div class="text-secondary">{{ $isDosen ? 'Peserta: '.$submission->student->name : 'Hasil Anda' }}</div>
                    <div class="h1 mb-0">
                        {{ is_null($submission->score) ? '—' : rtrim(rtrim($submission->score, '0'), '.') }}
                        <small class="text-secondary fs-4">/ {{ $assignment->max_score }}</small>
                    </div>
                </div>
                @if (is_null($submission->score))
                    <span class="badge bg-yellow-lt ms-auto">Menunggu penilaian esai</span>
                @endif
            </div>
        </div>

        <form method="POST" action="{{ route('quizzes.gradeEssays', $submission) }}">
            @csrf
            @foreach ($assignment->questions as $i => $q)
                @php($ans = $answers[$q->id] ?? null)
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="d-flex mb-2">
                            <span class="badge bg-{{ $q->isPg() ? 'blue' : 'purple' }}-lt me-2">Soal {{ $i + 1 }} · {{ $q->isPg() ? 'PG' : 'Esai' }}</span>
                            <span class="text-secondary small ms-auto">{{ $q->points }} poin</span>
                        </div>
                        <div class="mb-3" style="white-space:pre-line">{{ $q->question }}</div>

                        @if ($q->isPg())
                            @foreach ($q->options as $key => $opt)
                                @php($isChosen = $ans && $ans->answer === $key)
                                @php($isCorrect = $key === $q->correct_answer)
                                <div class="p-2 rounded mb-1 {{ $isCorrect ? 'bg-green-lt' : ($isChosen ? 'bg-red-lt' : '') }}">
                                    <strong>{{ $key }}.</strong> {{ $opt }}
                                    @if ($isCorrect)<i class="ti ti-check text-green ms-1"></i>@endif
                                    @if ($isChosen)<span class="badge bg-dark-lt ms-1">Jawaban {{ $isDosen ? 'mahasiswa' : 'Anda' }}</span>@endif
                                </div>
                            @endforeach
                            <div class="text-secondary small mt-1">Skor: {{ $ans ? rtrim(rtrim($ans->score, '0'), '.') : 0 }} / {{ $q->points }}</div>
                        @else
                            <div class="mb-2">
                                <div class="text-secondary small">Jawaban {{ $isDosen ? 'mahasiswa' : 'Anda' }}:</div>
                                <div class="border rounded p-2" style="white-space:pre-line">{{ $ans?->answer ?: '(kosong)' }}</div>
                            </div>
                            @if ($isDosen)
                                <div class="row align-items-end">
                                    <div class="col-auto">
                                        <label class="form-label">Skor (0–{{ $q->points }})</label>
                                        <input type="number" step="0.01" min="0" max="{{ $q->points }}" name="scores[{{ $ans?->id }}]"
                                               class="form-control" style="width:120px" value="{{ $ans?->score }}" @disabled(! $ans)>
                                    </div>
                                </div>
                            @else
                                <div class="text-secondary small">Skor: {{ is_null($ans?->score) ? 'belum dinilai' : rtrim(rtrim($ans->score, '0'), '.').' / '.$q->points }}</div>
                            @endif
                        @endif
                    </div>
                </div>
            @endforeach

            @if ($isDosen && $hasEssay)
                <div class="card"><div class="card-body text-end">
                    <button class="btn btn-primary"><i class="ti ti-check me-1"></i>Simpan Nilai Esai</button>
                </div></div>
            @endif
        </form>
    </div>
</div>
@endsection
