@extends('layouts.app')

@section('title', $assignment->title)
@section('page-pretitle', $assignment->course->name . ' · Tugas')
@section('page-title', $assignment->title)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('courses.index') }}">Mata Kuliah</a></li>
    <li class="breadcrumb-item"><a href="{{ route('courses.show', $assignment->course) }}">{{ $assignment->course->name }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('assignments.index', $assignment->course) }}">Tugas & Kuis</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ \Illuminate\Support\Str::limit($assignment->title, 24) }}</li>
@endsection

@section('content')
@php($course = $assignment->course)
@include('courses._subnav')

<div class="d-md-none mb-3">
    <div class="text-secondary small">{{ $assignment->isQuiz() ? 'Kuis' : 'Tugas' }}</div>
    <h1 class="h2 mb-0">{{ $assignment->title }}</h1>
</div>

<div class="row row-cards">
    <div class="col-lg-7 order-2 order-lg-1">
        <div class="card">
            <div class="card-header"><h3 class="card-title"><i class="ti ti-file-description me-1"></i>Petunjuk Tugas</h3></div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <span class="badge bg-blue-lt"><i class="ti ti-file-text me-1"></i>Tugas</span>
                    <span class="badge bg-{{ $assignment->isGroup() ? 'purple' : 'azure' }}-lt"><i class="ti ti-{{ $assignment->isGroup() ? 'users-group' : 'user' }} me-1"></i>{{ $assignment->isGroup() ? 'Kelompok' : 'Individu' }}</span>
                    <span class="badge bg-secondary-lt">Nilai maks. {{ $assignment->max_score }}</span>
                </div>
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
                    <div class="text-secondary small mb-1">Instruksi</div>
                    <div class="assignment-instructions content-prose" style="white-space:pre-line">{{ $assignment->description }}</div>
                @else
                    <div class="text-secondary">Tidak ada instruksi tambahan dari dosen.</div>
                @endif
            </div>
        </div>

        {{-- ============ KELOMPOK (tugas kelompok) ============ --}}
        @if ($assignment->isGroup())
            <div class="card mt-3">
                <div class="card-header">
                    <h3 class="card-title"><i class="ti ti-users-group me-1"></i>Kelompok</h3>
                    @if ($assignment->group_max)<div class="card-actions text-secondary small">Maks {{ $assignment->group_max }} anggota</div>@endif
                </div>
                <div class="card-body">
                    @if ($myGroup)
                        <div class="mb-2">
                            <span class="fw-bold">{{ $myGroup->name }}</span>
                            @if ($myGroup->isLocked())<span class="badge bg-secondary-lt ms-1"><i class="ti ti-lock me-1"></i>Terkunci (sudah dinilai)</span>@endif
                        </div>
                        <div class="list-group list-group-flush mb-2">
                            @foreach ($myGroup->members as $m)
                                <div class="list-group-item d-flex align-items-center px-0">
                                    <x-avatar :name="$m->name" :url="$m->avatarUrl()" class="me-2" />
                                    <div class="me-auto">{{ $m->name }}
                                        @if ($m->id === $myGroup->created_by)<span class="badge bg-blue-lt ms-1">Ketua</span>@endif
                                        @if ($m->id === auth()->id())<span class="text-secondary small">(Anda)</span>@endif
                                    </div>
                                    @if (! $myGroup->isLocked() && ($m->id === auth()->id() || auth()->id() === $myGroup->created_by))
                                        <form method="POST" action="{{ route('assignment-groups.removeMember', [$myGroup, $m]) }}"
                                              data-confirm="{{ $m->id === auth()->id() ? 'Keluar dari kelompok ini?' : 'Keluarkan '.$m->name.' dari kelompok?' }}">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-ghost-danger" title="{{ $m->id === auth()->id() ? 'Keluar' : 'Keluarkan' }}"><i class="ti ti-user-minus"></i></button>
                                        </form>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                        @if (! $myGroup->isLocked() && $groupmateCandidates->isNotEmpty() && (! $assignment->group_max || $myGroup->members->count() < $assignment->group_max))
                            <form method="POST" action="{{ route('assignment-groups.addMember', $myGroup) }}" class="d-flex gap-2 mobile-action-stack">
                                @csrf
                                <select name="user_id" class="form-select form-select-sm" required>
                                    <option value="">+ Tambah anggota…</option>
                                    @foreach ($groupmateCandidates as $cand)<option value="{{ $cand->id }}">{{ $cand->name }}</option>@endforeach
                                </select>
                                <button class="btn btn-sm"><i class="ti ti-user-plus me-1"></i>Tambah</button>
                            </form>
                        @endif
                    @else
                        <p class="text-secondary">Anda belum tergabung kelompok. Buat kelompok dan pilih anggota dari peserta kelas — cukup <strong>satu orang</strong> yang mengumpulkan untuk seluruh anggota.</p>
                        <form method="POST" action="{{ route('assignment-groups.store', $assignment) }}">
                            @csrf
                            <label class="form-label">Pilih anggota <span class="text-secondary">(Anda otomatis termasuk)</span></label>
                            <div class="row">
                                @forelse ($groupmateCandidates as $cand)
                                    <div class="col-md-6"><label class="form-check">
                                        <input type="checkbox" name="members[]" value="{{ $cand->id }}" class="form-check-input">
                                        <span class="form-check-label">{{ $cand->name }}</span>
                                    </label></div>
                                @empty
                                    <div class="col-12 text-secondary small">Belum ada peserta lain yang tersedia (semua sudah berkelompok). Anda tetap bisa membuat kelompok sendiri.</div>
                                @endforelse
                            </div>
                            <button class="btn btn-primary mt-2"><i class="ti ti-users-plus me-1"></i>Buat Kelompok</button>
                            @if ($assignment->group_max)<small class="form-hint d-block mt-1">Maksimal {{ $assignment->group_max }} anggota termasuk Anda.</small>@endif
                        </form>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <div class="col-lg-5 order-1 order-lg-2">
        <div class="card">
            <div class="card-header"><h3 class="card-title">{{ $assignment->isGroup() ? 'Pengumpulan Kelompok' : 'Pengumpulan Anda' }}</h3></div>
            <div class="card-body">
            @if (! $submission)
                <div class="alert alert-secondary mb-3">
                    <div class="d-flex align-items-start gap-2">
                        <i class="ti ti-upload fs-2"></i>
                        <div>
                            <div class="fw-bold">Belum dikumpulkan</div>
                            <div class="small">
                                @if ($assignment->deadline)
                                    Batas pengumpulan {{ $assignment->deadline->translatedFormat('d M Y, H:i') }}
                                @else
                                    Tugas ini tidak memiliki deadline.
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            @if ($assignment->isGroup() && ! $myGroup)
                <div class="text-secondary text-center py-3">
                    <i class="ti ti-users-group" style="font-size:2rem"></i>
                    <div class="mt-2">Buat atau gabung kelompok dulu (panel di kiri) sebelum mengumpulkan tugas.</div>
                </div>
            @else
                @php($mode = $assignment->submission_mode)
                @if ($submission)
                    <div class="alert alert-{{ $submission->isLate() ? 'warning' : 'success' }} mb-3">
                        <div class="d-flex align-items-start gap-2">
                            <i class="ti ti-{{ $submission->isLate() ? 'alert-triangle' : 'circle-check-filled' }} fs-2"></i>
                            <div><div class="fw-bold">Tugas sudah dikumpulkan</div><div class="small">{{ $submission->isLate() ? 'Terlambat' : 'Tepat waktu' }} · {{ $submission->submitted_at?->translatedFormat('d M Y H:i') }}</div></div>
                        </div>
                        @if ($assignment->isGroup() && $submission->student)
                            <div class="text-secondary small mt-1"><i class="ti ti-upload me-1"></i>Diunggah oleh {{ $submission->student->id === auth()->id() ? 'Anda' : $submission->student->name }}</div>
                        @endif
                    </div>
                @endif

                @if ($submission && $submission->isGraded())
                    {{-- Sudah dinilai: tampilkan jawaban terkirim (baca saja) + nilai --}}
                    @if ($assignment->allowsText() && $submission->answer_text)
                        <div class="mb-3"><span class="text-secondary">Jawaban teks Anda</span>
                            <div class="border rounded p-2 mt-1 content-prose" style="white-space:pre-line">{{ $submission->answer_text }}</div>
                        </div>
                    @endif
                    @if ($submission->file_path)
                        <a href="{{ route('submissions.download', $submission) }}" class="btn btn-sm mb-3"><i class="ti ti-download me-1"></i>Unduh berkas saya</a>
                    @endif
                    <hr>
                    <div class="mb-2"><span class="text-secondary">Nilai</span>
                        <div class="h1 mb-0">{{ \App\Support\Grades::num($submission->score) }} <small class="text-secondary fs-4">/ {{ $assignment->max_score }}</small></div>
                    </div>
                    @if ($submission->feedback)
                        <div class="mt-2"><span class="text-secondary">Feedback dosen</span>
                            <div class="alert alert-info mt-1 content-prose" style="white-space:pre-line">{{ $submission->feedback }}</div>
                        </div>
                    @endif
                @else
                    {{-- Belum dinilai (baru / sudah kumpul): form sesuai bentuk jawaban --}}
                    @if (! $submission && $assignment->isPastDeadline())
                        <div class="alert alert-warning mb-3">Deadline sudah lewat — pengumpulan akan ditandai <strong>terlambat</strong>.</div>
                    @elseif ($submission)
                        <div class="text-secondary mb-3">Menunggu penilaian dosen. Anda masih bisa memperbarui jawaban.</div>
                    @endif

                    <form method="POST" action="{{ route('submissions.store', $assignment) }}" enctype="multipart/form-data" data-warn-unsaved
                          x-data="{ fileName: '' }" @if($submission) data-confirm="Perbarui pengumpulan ini? Jawaban sebelumnya akan diganti dengan versi terbaru." @endif>
                        @csrf

                        @if ($assignment->allowsText())
                            <div class="mb-3">
                                <label class="form-label @if ($mode === 'text') required @endif">Jawaban Anda</label>
                                <textarea name="answer_text" rows="8"
                                          class="form-control @error('answer_text') is-invalid @enderror"
                                          placeholder="Tulis jawaban Anda di sini…"
                                          @if ($mode === 'text') required @endif>{{ old('answer_text', $submission->answer_text ?? '') }}</textarea>
                                @error('answer_text')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        @endif

                        @if ($assignment->allowsFile())
                            <div class="mb-3">
                                <label class="form-label @if ($mode === 'file' && ! $submission) required @endif">
                                    {{ $submission && $submission->file_path ? 'Ganti berkas' : 'Unggah berkas' }}
                                </label>
                                @if ($submission && $submission->file_path)
                                    <a href="{{ route('submissions.download', $submission) }}" class="d-flex align-items-center gap-2 border rounded p-2 mb-2 text-reset text-decoration-none">
                                        <span class="avatar avatar-sm bg-blue-lt"><i class="ti ti-file-text"></i></span>
                                        <span class="flex-fill"><span class="d-block fw-bold">Berkas yang sudah dikirim</span><span class="d-block text-secondary small">Ketuk untuk mengunduh</span></span>
                                        <i class="ti ti-download text-secondary"></i>
                                    </a>
                                @endif
                                <input type="file" name="file" accept=".pdf,.doc,.docx,.zip,.ppt,.pptx,.xls,.xlsx"
                                       class="form-control @error('file') is-invalid @enderror"
                                       @change="fileName = $event.target.files[0]?.name || ''"
                                       @if ($mode === 'file' && ! $submission) required @endif>
                                @error('file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <div x-show="fileName" x-cloak class="d-flex align-items-center gap-2 bg-blue-lt rounded p-2 mt-2">
                                    <i class="ti ti-file-check text-blue fs-2"></i><span class="small fw-bold text-truncate" x-text="fileName"></span>
                                </div>
                                <small class="form-hint">PDF/Word/PPT/Excel/ZIP, maks 20 MB.@if ($submission) Kosongkan bila tidak ingin mengganti berkas.@endif</small>
                            </div>
                        @endif

                        @if ($mode === 'both')
                            <small class="form-hint mb-2 d-block">Isi jawaban teks atau unggah berkas — minimal salah satu.</small>
                        @endif

                        <div class="student-submit-bar">
                            <button class="btn btn-primary w-100 student-submit-action" @unless($submission) data-loading="Mengirim tugas…" @endunless>
                                <i class="ti ti-{{ $submission ? 'refresh' : 'upload' }} me-1"></i>{{ $submission ? 'Perbarui Jawaban' : 'Kumpulkan Tugas' }}
                            </button>
                            <small class="form-hint d-block mt-1 text-center">Bisa diperbarui selama belum dinilai dosen.</small>
                        </div>
                    </form>

                    @if ($submission)
                        <form method="POST" action="{{ route('submissions.destroy', $submission) }}" class="mt-2"
                              data-confirm="Hapus pengumpulan Anda? Jawaban dan berkas akan dihapus, dan Anda bisa mengumpulkan ulang.">
                            @csrf @method('DELETE')
                            <button class="btn btn-outline-danger w-100"><i class="ti ti-trash me-1"></i>Hapus Pengumpulan</button>
                        </form>
                    @endif
                @endif
            @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>@media(max-width:575.98px){body.student-mobile-ui .page-header{display:none;}}</style>
@endpush

@push('styles')
<style>
.assignment-instructions{font-size:.95rem;line-height:1.65;overflow-wrap:anywhere;}
@media (max-width:575.98px){
    .student-submit-bar{position:sticky;z-index:20;bottom:calc(4.75rem + env(safe-area-inset-bottom));margin:1rem -.25rem -.25rem;padding:.65rem .25rem .25rem;background:linear-gradient(to bottom,transparent,var(--tblr-bg-surface) 20%);}
}
</style>
@endpush
