@extends('layouts.app')

@section('title', 'Soal Kuis')
@section('page-pretitle', $assignment->course->name . ' · Kuis')
@section('page-title', $assignment->title)

@section('page-actions')
    <div class="btn-list">
        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modal-import-soal"><i class="ti ti-file-import me-1"></i>Impor Soal</button>
        @if ($assignment->questions->isNotEmpty())
            <a href="{{ route('quizzes.questions.export', $assignment) }}" class="btn btn-outline-green" download><i class="ti ti-file-export me-1"></i>Ekspor Soal</a>
        @endif
        <a href="{{ route('assignments.show', $assignment) }}" class="btn"><i class="ti ti-arrow-left me-1"></i>Kembali ke kuis</a>
    </div>
@endsection

@section('content')
<div class="row row-cards">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Daftar Soal ({{ $assignment->questions->count() }}) · Total {{ $assignment->totalPoints() }} poin</h3></div>
            @if ($assignment->questions->isEmpty())
                <div class="card-body"><x-empty-state icon="ti-help-circle" title="Belum ada soal" description="Tambahkan soal di panel kanan." /></div>
            @else
                <div class="list-group list-group-flush">
                    @foreach ($assignment->questions as $i => $q)
                        <div class="list-group-item">
                            <div class="d-flex">
                                <div class="me-auto">
                                    <span class="badge bg-{{ $q->isPg() ? 'blue' : 'purple' }}-lt me-1">{{ $q->isPg() ? 'PG' : 'Esai' }}</span>
                                    <span class="text-secondary small">{{ $q->points }} poin</span>
                                    <div class="mt-1">{{ $i + 1 }}. {{ $q->question }}</div>
                                    @if ($q->isPg())
                                        <ul class="list-unstyled mt-1 mb-0 small">
                                            @foreach ($q->options as $key => $opt)
                                                <li class="{{ $key === $q->correct_answer ? 'text-green fw-bold' : 'text-secondary' }}">
                                                    {{ $key }}. {{ $opt }} @if ($key === $q->correct_answer)<i class="ti ti-check"></i>@endif
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                                <div class="btn-list">
                                    <button class="btn btn-sm" data-bs-toggle="modal" data-bs-target="#edit-q-{{ $q->id }}" title="Edit"><i class="ti ti-edit"></i></button>
                                    <form method="POST" action="{{ route('quizzes.questions.destroy', $q) }}" data-confirm="Hapus soal ini?">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-ghost-danger"><i class="ti ti-trash"></i></button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        {{-- Modal edit soal --}}
                        <div class="modal modal-blur fade" id="edit-q-{{ $q->id }}" tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered">
                                <form class="modal-content" method="POST" action="{{ route('quizzes.questions.update', $q) }}" x-data="{ type: '{{ $q->type }}' }">
                                    @csrf @method('PUT')
                                    <div class="modal-header"><h5 class="modal-title">Edit Soal {{ $i + 1 }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label">Jenis</label>
                                            <select name="type" class="form-select" x-model="type">
                                                <option value="pg">Pilihan Ganda</option>
                                                <option value="essay">Esai</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label required">Pertanyaan</label>
                                            <textarea name="question" class="form-control" rows="2" required>{{ $q->question }}</textarea>
                                        </div>
                                        <template x-if="type === 'pg'">
                                            <div>
                                                <label class="form-label required">Pilihan & jawaban benar</label>
                                                @foreach (['A','B','C','D'] as $key)
                                                    <div class="input-group mb-2">
                                                        <span class="input-group-text">
                                                            <input class="form-check-input m-0" type="radio" name="correct_answer" value="{{ $key }}" @checked($q->correct_answer === $key)>
                                                            <span class="ms-2">{{ $key }}</span>
                                                        </span>
                                                        <input type="text" name="options[{{ $key }}]" class="form-control" value="{{ $q->options[$key] ?? '' }}" placeholder="Opsi {{ $key }}">
                                                    </div>
                                                @endforeach
                                            </div>
                                        </template>
                                        <div class="mb-1">
                                            <label class="form-label required">Poin</label>
                                            <input type="number" name="points" class="form-control" value="{{ $q->points }}" min="1" max="100" required>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-link" data-bs-dismiss="modal">Batal</button>
                                        <button class="btn btn-primary">Simpan</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="col-lg-5">
        {{-- Generate soal dengan AI --}}
        <form class="card mb-3" method="POST" action="{{ route('ai.questions.generate', $assignment) }}">
            @csrf
            <div class="card-header"><h3 class="card-title"><i class="ti ti-sparkles me-1 text-purple"></i>Generate Soal (AI)</h3></div>
            <div class="card-body">
                @unless ($aiEnabled)
                    <div class="alert alert-warning mb-2">Fitur AI nonaktif. Atur <code>ANTHROPIC_API_KEY</code> di <code>.env</code> untuk mengaktifkan.</div>
                @endunless
                <div class="row g-2">
                    <div class="col-4">
                        <label class="form-label">Jumlah</label>
                        <input type="number" name="count" class="form-control" value="5" min="1" max="10">
                    </div>
                    <div class="col-8">
                        <label class="form-label">Dari Materi PDF</label>
                        <select name="material_id" class="form-select">
                            <option value="">— pilih / pakai teks —</option>
                            @foreach ($pdfMaterials as $m)
                                <option value="{{ $m->id }}">{{ $m->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">atau tempel teks materi</label>
                        <textarea name="source_text" class="form-control" rows="3" placeholder="Tempel ringkasan/teks materi di sini..."></textarea>
                    </div>
                </div>
            </div>
            <div class="card-footer text-end">
                <button class="btn btn-outline-purple" @disabled(! $aiEnabled) data-loading="Membuat soal…"><i class="ti ti-sparkles me-1"></i>Generate</button>
            </div>
        </form>

        <form class="card" method="POST" action="{{ route('quizzes.questions.store', $assignment) }}" x-data="{ type: 'pg' }">
            @csrf
            <div class="card-header"><h3 class="card-title">Tambah Soal</h3></div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Jenis Soal</label>
                    <select name="type" class="form-select" x-model="type">
                        <option value="pg">Pilihan Ganda</option>
                        <option value="essay">Esai</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label required">Pertanyaan</label>
                    <textarea name="question" class="form-control" rows="2" required></textarea>
                </div>

                <template x-if="type === 'pg'">
                    <div>
                        <label class="form-label required">Pilihan (isi minimal 2) & tandai jawaban benar</label>
                        @foreach (['A','B','C','D'] as $i => $key)
                            <div class="input-group mb-2">
                                <span class="input-group-text">
                                    <input class="form-check-input m-0" type="radio" name="correct_answer" value="{{ $key }}" @if($i===0) checked @endif>
                                    <span class="ms-2">{{ $key }}</span>
                                </span>
                                <input type="text" name="options[{{ $key }}]" class="form-control" placeholder="Opsi {{ $key }}">
                            </div>
                        @endforeach
                    </div>
                </template>

                <div class="mb-3">
                    <label class="form-label required">Poin</label>
                    <input type="number" name="points" class="form-control" value="1" min="1" max="100" required>
                </div>
            </div>
            <div class="card-footer text-end">
                <button class="btn btn-primary"><i class="ti ti-plus me-1"></i>Tambah Soal</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal impor soal --}}
<div class="modal modal-blur fade" id="modal-import-soal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="POST" action="{{ route('quizzes.questions.import', $assignment) }}" enctype="multipart/form-data">
            @csrf
            <div class="modal-header"><h5 class="modal-title">Impor Soal (JSON)</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label required">Berkas JSON</label>
                    <input type="file" name="file" class="form-control" accept=".json,.txt" required>
                </div>
                <div class="alert alert-info mb-0">
                    Gunakan berkas hasil <strong>Ekspor Soal</strong> dari kuis lain. Soal akan <strong>ditambahkan</strong> ke kuis ini (tidak menimpa soal yang ada). Cocok untuk memakai ulang bank soal antar semester.
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-link" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Impor</button></div>
        </form>
    </div>
</div>
@endsection
