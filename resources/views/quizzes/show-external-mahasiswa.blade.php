@extends('layouts.app')

@section('title', $assignment->title)
@section('page-pretitle', $assignment->course->name . ' · Kuis eksternal')
@section('page-title', $assignment->title)

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-body">
                <span class="badge bg-purple-lt mb-3"><i class="ti ti-external-link me-1"></i>Dikerjakan di platform eksternal</span>
                @if ($assignment->description)<div class="mb-4" style="white-space:pre-line">{{ $assignment->description }}</div>@endif
                <div class="row text-center mb-4">
                    <div class="col"><div class="h2 mb-0">{{ $assignment->max_score }}</div><div class="text-secondary small">Nilai maksimal</div></div>
                    <div class="col"><div class="h2 mb-0">{{ $assignment->deadline?->translatedFormat('d M') ?? 'Bebas' }}</div><div class="text-secondary small">Deadline</div></div>
                    <div class="col"><div class="h2 mb-0">{{ is_null($submission?->score) ? '—' : \App\Support\Grades::num($submission->score) }}</div><div class="text-secondary small">Nilai Anda</div></div>
                </div>

                @if ($submission && ! is_null($submission->score))
                    <div class="alert alert-success"><i class="ti ti-circle-check me-1"></i>Nilai hasil kuis sudah diinput oleh dosen.</div>
                @else
                    <div class="alert alert-info"><i class="ti ti-info-circle me-1"></i>Kerjakan kuis melalui tautan di bawah. Nilai akan tampil setelah diinput oleh dosen.</div>
                @endif

                <a href="{{ $assignment->external_url }}" target="_blank" rel="noopener noreferrer" class="btn btn-primary btn-lg w-100">
                    <i class="ti ti-external-link me-1"></i>Buka Kuis Eksternal
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
