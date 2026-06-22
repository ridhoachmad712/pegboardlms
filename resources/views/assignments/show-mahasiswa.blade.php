@extends('layouts.app')

@section('title', $assignment->title)
@section('page-pretitle', $assignment->course->name . ' · Tugas')
@section('page-title', $assignment->title)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('courses.index') }}">Kelas Saya</a></li>
    <li class="breadcrumb-item"><a href="{{ route('courses.show', $assignment->course) }}">{{ $assignment->course->name }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('assignments.index', $assignment->course) }}">Tugas & Kuis</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ \Illuminate\Support\Str::limit($assignment->title, 24) }}</li>
@endsection

@section('content')
@php($course = $assignment->course)
@include('courses._subnav')

<div class="row row-cards">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-body">
                <div class="d-flex mb-3">
                    <div>
                        <span class="text-secondary">Deadline</span>
                        <div class="fw-bold">{{ $assignment->deadline?->translatedFormat('d M Y H:i') ?? 'Tanpa deadline' }}</div>
                    </div>
                    @if ($assignment->isPastDeadline())
                        <span class="badge bg-red-lt ms-auto align-self-start">Deadline terlewat</span>
                    @endif
                </div>
                @if ($assignment->description)
                    <hr>
                    <div style="white-space:pre-line">{{ $assignment->description }}</div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Pengumpulan Anda</h3></div>
            <div class="card-body">
                @if ($submission)
                    <div class="mb-2">
                        <span class="badge bg-{{ $submission->isLate() ? 'red' : 'green' }}-lt">{{ $submission->isLate() ? 'Terlambat' : 'Tepat waktu' }}</span>
                        <span class="text-secondary small ms-1">{{ $submission->submitted_at?->translatedFormat('d M Y H:i') }}</span>
                    </div>
                    @if ($submission->file_path)
                        <a href="{{ route('submissions.download', $submission) }}" class="btn btn-sm mb-3"><i class="ti ti-download me-1"></i>Unduh berkas saya</a>
                    @endif
                    <hr>
                    @if ($submission->isGraded())
                        <div class="mb-2"><span class="text-secondary">Nilai</span>
                            <div class="h1 mb-0">{{ rtrim(rtrim($submission->score, '0'), '.') }} <small class="text-secondary fs-4">/ {{ $assignment->max_score }}</small></div>
                        </div>
                        @if ($submission->feedback)
                            <div class="mt-2"><span class="text-secondary">Feedback dosen</span>
                                <div class="alert alert-info mt-1" style="white-space:pre-line">{{ $submission->feedback }}</div>
                            </div>
                        @endif
                    @else
                        <div class="text-secondary mb-3">Menunggu penilaian dosen.</div>
                        {{-- Boleh ganti berkas selama belum dinilai --}}
                        <form method="POST" action="{{ route('submissions.store', $assignment) }}" enctype="multipart/form-data">
                            @csrf
                            <label class="form-label">Ganti berkas</label>
                            <div class="input-group">
                                <input type="file" name="file" class="form-control" accept=".pdf,.doc,.docx,.zip,.ppt,.pptx,.xls,.xlsx" required>
                                <button class="btn btn-primary"><i class="ti ti-refresh me-1"></i>Ganti</button>
                            </div>
                            <small class="form-hint">Bisa diganti selama belum dinilai dosen.</small>
                        </form>
                    @endif
                @else
                    @if ($assignment->isPastDeadline())
                        <div class="alert alert-warning mb-3">Deadline sudah lewat — pengumpulan akan ditandai <strong>terlambat</strong>.</div>
                    @endif
                    <form method="POST" action="{{ route('submissions.store', $assignment) }}" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label required">Unggah berkas</label>
                            <input type="file" name="file" class="form-control" accept=".pdf,.doc,.docx,.zip,.ppt,.pptx,.xls,.xlsx" required>
                            <small class="form-hint">PDF/Word/PPT/Excel/ZIP, maks 20 MB. Bisa diganti selama belum dinilai.</small>
                        </div>
                        <button class="btn btn-primary w-100"><i class="ti ti-upload me-1"></i>Kumpulkan Tugas</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
