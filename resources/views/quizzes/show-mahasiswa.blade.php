@extends('layouts.app')

@section('title', $assignment->title)
@section('page-pretitle', $assignment->course->name . ' · Kuis')
@section('page-title', $assignment->title)

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-body">
                @if ($assignment->description)
                    <div class="mb-3" style="white-space:pre-line">{{ $assignment->description }}</div>
                @endif
                <div class="row text-center mb-3">
                    <div class="col"><div class="h2 mb-0">{{ $assignment->questions->count() }}</div><div class="text-secondary small">Soal</div></div>
                    <div class="col"><div class="h2 mb-0">{{ $assignment->duration_minutes ? $assignment->duration_minutes.' mnt' : 'Bebas' }}</div><div class="text-secondary small">Durasi</div></div>
                    <div class="col"><div class="h2 mb-0">{{ $assignment->max_score }}</div><div class="text-secondary small">Nilai maks</div></div>
                </div>

                @if ($submission && $submission->submitted_at)
                    <div class="alert alert-info">Anda sudah menyelesaikan kuis ini.</div>
                    @if (! is_null($submission->score))
                        <div class="text-center mb-3"><div class="h1">{{ rtrim(rtrim($submission->score, '0'), '.') }} <small class="text-secondary fs-4">/ {{ $assignment->max_score }}</small></div></div>
                    @else
                        <div class="text-center text-secondary mb-3">Menunggu penilaian esai oleh dosen.</div>
                    @endif
                    <a href="{{ route('quizzes.review', $submission) }}" class="btn btn-primary w-100">Lihat Jawaban</a>
                @elseif ($assignment->questions->isEmpty())
                    <div class="alert alert-warning">Kuis belum memiliki soal.</div>
                @else
                    @if ($assignment->duration_minutes)
                        <div class="alert alert-warning"><i class="ti ti-clock me-1"></i>Waktu: <strong>{{ $assignment->duration_minutes }} menit</strong>. Timer berjalan setelah dimulai dan kuis hanya bisa dikerjakan sekali.</div>
                    @endif
                    <a href="{{ route('quizzes.take', $assignment) }}" class="btn btn-primary btn-lg w-100"><i class="ti ti-player-play me-1"></i>Mulai Kerjakan</a>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
