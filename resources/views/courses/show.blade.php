@extends('layouts.app')

@section('title', $course->name)

@section('hero-actions')
    @if ($isDosen)
        @if ($course->isCompleted())
            <form method="POST" action="{{ route('courses.complete', $course) }}">
                @csrf @method('PATCH')
                <button class="btn btn-success"><i class="ti ti-lock-open me-1"></i>Buka Kembali</button>
            </form>
        @else
            <a href="{{ route('courses.edit', $course) }}" class="btn"><i class="ti ti-edit me-1"></i>Edit</a>
            @if ($readiness['can_complete'])
                <form method="POST" action="{{ route('courses.complete', $course) }}"
                      data-confirm="Tandai kelas ini SELESAI? Setelah selesai, kelas hanya bisa dilihat (read-only). Bisa dibuka kembali bila perlu.">
                    @csrf @method('PATCH')
                    <button class="btn btn-success"><i class="ti ti-circle-check me-1"></i>Tandai Selesai</button>
                </form>
            @else
                @php
                    $reqHtml = '<div class="text-start">'
                        .'<div class="fw-bold mb-1">Syarat agar bisa ditandai selesai</div>'
                        .'<div class="mb-1"><i class="ti '.($readiness['enough_meetings'] ? 'ti-circle-check text-green' : 'ti-circle-dashed').' me-1"></i>'
                            .$readiness['max_meetings'].' pertemuan terpenuhi ('.$readiness['meetings'].'/'.$readiness['max_meetings'].')</div>'
                        .'<div><i class="ti '.($readiness['all_graded'] ? 'ti-circle-check text-green' : 'ti-circle-dashed').' me-1"></i>'
                            .'Semua mahasiswa sudah dinilai'
                            .(! $readiness['all_graded']
                                ? ' ('.(! $readiness['weight_ok'] ? 'bobot komponen nilai harus 100%' : $readiness['ungraded'].' mahasiswa belum lengkap').')'
                                : '')
                        .'</div></div>';
                @endphp
                <span class="d-inline-block" tabindex="0" data-bs-toggle="tooltip" data-bs-html="true"
                      data-bs-placement="bottom" title="{{ $reqHtml }}">
                    <button class="btn" type="button" disabled style="pointer-events:none">
                        <i class="ti ti-circle-check me-1"></i>Tandai Selesai
                    </button>
                </span>
            @endif
            <form method="POST" action="{{ route('courses.destroy', $course) }}"
                  data-confirm="Hapus kelas ini? Kelas dipindahkan ke tong sampah dan bisa dipulihkan nanti.">
                @csrf @method('DELETE')
                <button class="btn btn-danger"><i class="ti ti-trash me-1"></i>Hapus</button>
            </form>
        @endif
    @endif
@endsection

@section('content')
@include('courses._hero')

{{-- ============ PERTEMUAN & MATERI ============ --}}
<div>
                @if ($isDosen && ! $course->isCompleted())
                    @php($maxMeetings = \App\Http\Controllers\MeetingController::MAX_MEETINGS)
                    <div class="mb-3 d-flex align-items-center">
                        <span class="text-secondary small">{{ $course->meetings->count() }} / {{ $maxMeetings }} pertemuan</span>
                        <div class="ms-auto">
                            @if ($course->meetings->count() >= $maxMeetings)
                                <button class="btn" disabled title="Maksimal {{ $maxMeetings }} pertemuan" data-bs-toggle="tooltip"><i class="ti ti-plus me-1"></i>Tambah Pertemuan</button>
                            @else
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-meeting">
                                    <i class="ti ti-plus me-1"></i>Tambah Pertemuan
                                </button>
                            @endif
                        </div>
                    </div>
                @endif

                @forelse ($course->meetings as $meeting)
                    <div class="card mb-3 overflow-hidden">
                        <div class="card-header d-flex flex-wrap align-items-center gap-2">
                            <a class="me-auto d-flex align-items-center text-reset text-decoration-none collapsed" role="button"
                               data-bs-toggle="collapse" href="#meeting-{{ $meeting->id }}" aria-expanded="false" aria-controls="meeting-{{ $meeting->id }}">
                                <i class="ti ti-chevron-down meeting-chevron me-2 text-secondary"></i>
                                <span>
                                    <span class="badge bg-blue-lt me-2">Pertemuan {{ $meeting->number }}</span>
                                    <strong>{{ $meeting->topic }}</strong>
                                    @if ($meeting->date)
                                        <span class="text-secondary ms-2 small text-nowrap"><i class="ti ti-calendar-event"></i> {{ $meeting->date->translatedFormat('d M Y') }}</span>
                                    @endif
                                    <span class="badge bg-secondary-lt ms-2">{{ $meeting->materials->count() }} materi</span>
                                    @if ($meeting->assignments->count())
                                        <span class="badge bg-secondary-lt ms-1">{{ $meeting->assignments->count() }} tugas/kuis</span>
                                    @endif
                                </span>
                            </a>
                            @if ($isDosen && ! $course->isCompleted())
                                <div class="btn-list">
                                    <button class="btn btn-sm" data-bs-toggle="modal" data-bs-target="#edit-meeting-{{ $meeting->id }}">
                                        <i class="ti ti-edit me-1"></i>Edit
                                    </button>
                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modal-material-{{ $meeting->id }}">
                                        <i class="ti ti-plus me-1"></i>Materi
                                    </button>
                                    @if ($aiEnabled)
                                        <form method="POST" action="{{ route('ai.material.generate', $meeting) }}"
                                              data-confirm="Buat draf materi dengan AI untuk pertemuan ini? Materi disusun dari RPS (CPL/CPMK/Sub-CPMK), topik, dan PDF yang sudah ada. Bisa diedit setelahnya.">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-purple" data-loading="Menyusun materi…"><i class="ti ti-sparkles me-1"></i>Materi AI</button>
                                        </form>
                                    @endif
                                    <a href="{{ route('assignments.create', [$course, 'meeting' => $meeting->id, 'type' => 'tugas']) }}" class="btn btn-sm"><i class="ti ti-checklist me-1"></i>Tugas</a>
                                    <a href="{{ route('assignments.create', [$course, 'meeting' => $meeting->id, 'type' => 'kuis']) }}" class="btn btn-sm"><i class="ti ti-help-circle me-1"></i>Kuis</a>
                                    <form method="POST" action="{{ route('meetings.destroy', $meeting) }}"
                                          data-confirm="Hapus pertemuan ini beserta materinya?">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-ghost-danger" title="Hapus pertemuan" aria-label="Hapus pertemuan {{ $meeting->number }}" data-bs-toggle="tooltip"><i class="ti ti-trash"></i></button>
                                    </form>
                                </div>
                            @endif
                        </div>
                        <div class="collapse" id="meeting-{{ $meeting->id }}">
                        <div class="list-group list-group-flush">
                            @forelse ($meeting->materials as $material)
                                @if ($material->isText())
                                    {{-- Materi teks (mis. hasil generate AI) — ditampilkan langsung --}}
                                    <div class="list-group-item">
                                        <div class="d-flex align-items-center mb-2">
                                            <span class="avatar avatar-sm bg-purple-lt me-2"><i class="ti ti-sparkles"></i></span>
                                            <div class="me-auto">
                                                <div class="fw-bold">{{ $material->title }}</div>
                                                <div class="text-secondary small">Materi teks</div>
                                            </div>
                                            @if ($isDosen && ! $course->isCompleted())
                                                <div class="btn-list">
                                                    <button class="btn btn-sm" data-bs-toggle="modal" data-bs-target="#edit-material-{{ $material->id }}"><i class="ti ti-edit me-1"></i>Edit</button>
                                                    <form method="POST" action="{{ route('materials.destroy', $material) }}" data-confirm="Hapus materi ini?">
                                                        @csrf @method('DELETE')
                                                        <button class="btn btn-sm btn-ghost-danger" title="Hapus materi" aria-label="Hapus materi {{ $material->title }}"><i class="ti ti-trash"></i></button>
                                                    </form>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="markdown">{!! \Illuminate\Support\Str::markdown($material->content ?? '', ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}</div>
                                    </div>
                                    @if ($isDosen)
                                        <div class="modal modal-blur fade" id="edit-material-{{ $material->id }}" tabindex="-1">
                                            <div class="modal-dialog modal-dialog-centered modal-lg">
                                                <form class="modal-content" method="POST" action="{{ route('materials.update', $material) }}">
                                                    @csrf @method('PUT')
                                                    <div class="modal-header"><h5 class="modal-title">Edit Materi Teks</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label required">Judul</label>
                                                            <input type="text" name="title" class="form-control" value="{{ $material->title }}" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Isi Materi (Markdown)</label>
                                                            <textarea name="content" class="form-control font-monospace" rows="16">{{ $material->content }}</textarea>
                                                            <small class="form-hint">Mendukung Markdown: <code>## Judul</code>, <code>- poin</code>, <code>**tebal**</code>, dll.</small>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-link" data-bs-dismiss="modal">Batal</button>
                                                        <button class="btn btn-primary">Simpan</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    @endif
                                @else
                                @php($ext = $material->isFile() ? strtolower(pathinfo($material->path ?? '', PATHINFO_EXTENSION)) : '')
                                @php($isPdf = $ext === 'pdf')
                                @php($previewUrl = $isPdf
                                    ? route('materials.preview', $material)
                                    : (in_array($ext, ['doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx'])
                                        ? 'https://view.officeapps.live.com/op/embed.aspx?src='.urlencode(asset('storage/'.$material->path))
                                        : null))
                                <div class="list-group-item">
                                    <div class="d-flex align-items-center">
                                        <span class="avatar avatar-sm bg-{{ $material->isFile() ? 'red' : 'azure' }}-lt me-2">
                                            <i class="ti {{ $material->isFile() ? 'ti-file-text' : ($material->type === 'video' ? 'ti-brand-youtube' : 'ti-link') }}"></i>
                                        </span>
                                        <div class="me-auto">
                                            <div class="fw-bold">{{ $material->title }}</div>
                                            <div class="text-secondary small">
                                                {{ ucfirst($material->type) }}@if ($material->size_for_humans) · {{ $material->size_for_humans }}@endif
                                            </div>
                                        </div>
                                        <div class="btn-list">
                                            @if ($isDosen && $isPdf && $aiEnabled)
                                                <form method="POST" action="{{ route('ai.material.summarize', $material) }}">
                                                    @csrf
                                                    <button class="btn btn-sm btn-outline-purple" title="Ringkasan AI" data-loading="Meringkas…"><i class="ti ti-sparkles me-1"></i>Ringkasan AI</button>
                                                </form>
                                            @endif
                                            @if ($previewUrl)
                                                <button type="button" class="btn btn-sm btn-outline-primary"
                                                        data-bs-toggle="modal" data-bs-target="#modal-preview"
                                                        data-preview-url="{{ $previewUrl }}"
                                                        data-download-url="{{ route('materials.download', $material) }}"
                                                        data-preview-title="{{ $material->title }}"
                                                        aria-label="Preview {{ $material->title }}">
                                                    <i class="ti ti-eye me-1"></i>Preview
                                                </button>
                                            @endif
                                            @if ($material->isFile())
                                                <a href="{{ route('materials.download', $material) }}" class="btn btn-sm"><i class="ti ti-download me-1"></i>Unduh</a>
                                            @else
                                                <a href="{{ $material->url }}" target="_blank" rel="noopener" class="btn btn-sm"><i class="ti ti-external-link me-1"></i>Buka</a>
                                            @endif
                                            @if ($isDosen && ! $course->isCompleted())
                                                <button class="btn btn-sm" data-bs-toggle="modal" data-bs-target="#edit-material-{{ $material->id }}" title="Edit" aria-label="Edit materi {{ $material->title }}"><i class="ti ti-edit"></i></button>
                                                <form method="POST" action="{{ route('materials.destroy', $material) }}"
                                                      data-confirm="Hapus materi ini?">
                                                    @csrf @method('DELETE')
                                                    <button class="btn btn-sm btn-ghost-danger" title="Hapus materi" aria-label="Hapus materi {{ $material->title }}"><i class="ti ti-trash"></i></button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                    @if ($material->summary)
                                        <div class="alert alert-purple mt-2 mb-0">
                                            <div class="fw-bold small mb-1"><i class="ti ti-sparkles me-1"></i>Ringkasan AI</div>
                                            <div class="small" style="white-space:pre-line">{{ $material->summary }}</div>
                                        </div>
                                    @endif
                                </div>

                                @if ($isDosen)
                                    <div class="modal modal-blur fade" id="edit-material-{{ $material->id }}" tabindex="-1">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <form class="modal-content" method="POST" action="{{ route('materials.update', $material) }}">
                                                @csrf @method('PUT')
                                                <div class="modal-header"><h5 class="modal-title">Edit Materi</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label required">Judul</label>
                                                        <input type="text" name="title" class="form-control" value="{{ $material->title }}" required>
                                                    </div>
                                                    @unless ($material->isFile())
                                                        <div class="mb-3">
                                                            <label class="form-label required">URL</label>
                                                            <input type="url" name="url" class="form-control" value="{{ $material->url }}" required>
                                                        </div>
                                                    @else
                                                        <div class="text-secondary small">Untuk mengganti berkas, hapus materi ini lalu unggah ulang.</div>
                                                    @endunless
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-link" data-bs-dismiss="modal">Batal</button>
                                                    <button class="btn btn-primary">Simpan</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                @endif
                                @endif
                            @empty
                                <div class="list-group-item text-secondary small">Belum ada materi pada pertemuan ini.</div>
                            @endforelse
                        </div>
                        @if ($meeting->assignments->isNotEmpty())
                            <div class="list-group list-group-flush border-top">
                                @foreach ($meeting->assignments as $a)
                                    <div class="list-group-item">
                                        <div class="d-flex align-items-center">
                                            <span class="avatar avatar-sm bg-{{ $a->isQuiz() ? 'purple' : 'green' }}-lt me-2">
                                                <i class="ti {{ $a->isQuiz() ? 'ti-help-circle' : 'ti-checklist' }}"></i>
                                            </span>
                                            <div class="me-auto">
                                                <a href="{{ route('assignments.show', $a) }}" class="fw-bold text-reset">{{ $a->title }}</a>
                                                <div class="text-secondary small">
                                                    {{ $a->isQuiz() ? 'Kuis' : 'Tugas' }}@if ($a->deadline) · <i class="ti ti-clock"></i> {{ $a->deadline->translatedFormat('d M Y H:i') }}@endif
                                                    @if ($isDosen && ! $a->published) · <span class="badge bg-orange-lt">draf</span>@endif
                                                </div>
                                            </div>
                                            <a href="{{ route('assignments.show', $a) }}" class="btn btn-sm">Buka</a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        </div>
                    </div>

                    {{-- Modal tambah materi (per meeting, dosen) --}}
                    @if ($isDosen)
                        <div class="modal modal-blur fade" id="modal-material-{{ $meeting->id }}" tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered">
                                <form class="modal-content" method="POST" action="{{ route('materials.store', $meeting) }}"
                                      enctype="multipart/form-data" x-data="{ type: 'file' }">
                                    @csrf
                                    <div class="modal-header">
                                        <h5 class="modal-title">Tambah Materi — Pertemuan {{ $meeting->number }}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label required">Judul</label>
                                            <input type="text" name="title" class="form-control" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label required">Jenis</label>
                                            <select name="type" class="form-select" x-model="type">
                                                <option value="file">Berkas (PDF/PPT/DOC/XLS)</option>
                                                <option value="link">Tautan (Google Drive, dll.)</option>
                                                <option value="video">Video (YouTube)</option>
                                            </select>
                                        </div>
                                        <div class="mb-3" x-show="type === 'file'">
                                            <label class="form-label">Berkas</label>
                                            <input type="file" name="file" class="form-control" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx">
                                            <small class="form-hint">Maks 20 MB. PDF, Word, PowerPoint, atau Excel.</small>
                                        </div>
                                        <div class="mb-3" x-show="type !== 'file'" x-cloak>
                                            <label class="form-label">URL</label>
                                            <input type="url" name="url" class="form-control" placeholder="https://...">
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-link" data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" class="btn btn-primary">Tambah</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endif

                    {{-- Modal edit pertemuan (dosen) --}}
                    @if ($isDosen && ! $course->isCompleted())
                        <div class="modal modal-blur fade" id="edit-meeting-{{ $meeting->id }}" tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered">
                                <form class="modal-content" method="POST" action="{{ route('meetings.update', $meeting) }}">
                                    @csrf @method('PUT')
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit Pertemuan {{ $meeting->number }}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row">
                                            <div class="col-4 mb-3">
                                                <label class="form-label required">Pertemuan ke-</label>
                                                <input type="number" name="number" class="form-control" min="1" max="{{ \App\Http\Controllers\MeetingController::MAX_MEETINGS }}" value="{{ $meeting->number }}" required>
                                            </div>
                                            <div class="col-8 mb-3">
                                                <label class="form-label">Tanggal</label>
                                                <input type="date" name="date" class="form-control" value="{{ $meeting->date?->format('Y-m-d') }}">
                                            </div>
                                            <div class="col-12 mb-3">
                                                <label class="form-label required">Topik</label>
                                                <input type="text" name="topic" class="form-control" value="{{ $meeting->topic }}" required>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label">Deskripsi</label>
                                                <textarea name="description" class="form-control" rows="2">{{ $meeting->description }}</textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-link" data-bs-dismiss="modal">Batal</button>
                                        <button class="btn btn-primary">Simpan</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endif
                @empty
                    <x-empty-state icon="ti-calendar-off" title="Belum ada pertemuan"
                        :description="$isDosen ? 'Tambahkan pertemuan untuk mulai mengunggah materi.' : 'Dosen belum menambahkan pertemuan.'" />
                @endforelse
</div>

{{-- ============ MODALS (dosen) ============ --}}
@if ($isDosen && ! $course->isCompleted())
    {{-- Tambah pertemuan --}}
    <div class="modal modal-blur fade" id="modal-meeting" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content" method="POST" action="{{ route('meetings.store', $course) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Pertemuan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-4 mb-3">
                            <label class="form-label required">Pertemuan ke-</label>
                            <input type="number" name="number" class="form-control" min="1" max="{{ \App\Http\Controllers\MeetingController::MAX_MEETINGS }}" value="{{ min($course->meetings->count() + 1, \App\Http\Controllers\MeetingController::MAX_MEETINGS) }}" required>
                        </div>
                        <div class="col-8 mb-3">
                            <label class="form-label">Tanggal</label>
                            <input type="date" name="date" class="form-control">
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label required">Topik</label>
                            <input type="text" name="topic" class="form-control" placeholder="Pengantar..." required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Deskripsi</label>
                            <textarea name="description" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Tambah</button>
                </div>
            </form>
        </div>
    </div>

@endif

{{-- Modal preview materi (PDF) — tersedia untuk dosen & mahasiswa --}}
<div class="modal modal-blur fade" id="modal-preview" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-truncate" id="preview-title">Preview Materi</h5>
                <a href="#" id="preview-download" class="btn btn-sm ms-auto"><i class="ti ti-download me-1"></i>Unduh</a>
                <button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body p-0" style="height:80vh">
                <iframe id="preview-frame" src="" title="Preview materi" style="width:100%;height:100%;border:0"></iframe>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    [x-cloak]{display:none!important;}
    .meeting-chevron{transition:transform .2s ease;}
    .collapsed .meeting-chevron{transform:rotate(-90deg);}
</style>
@endpush

@push('scripts')
<script>
    // Preview materi: isi iframe modal dari tombol yang diklik.
    (function () {
        var modal = document.getElementById('modal-preview');
        if (!modal) return; // cukup elemen modal-nya; event show.bs.modal dipicu Tabler saat dibuka
        var frame = document.getElementById('preview-frame');
        var titleEl = document.getElementById('preview-title');
        var dl = document.getElementById('preview-download');
        modal.addEventListener('show.bs.modal', function (e) {
            var btn = e.relatedTarget;
            if (!btn) return;
            frame.src = btn.getAttribute('data-preview-url') || '';
            titleEl.textContent = btn.getAttribute('data-preview-title') || 'Preview Materi';
            if (dl) dl.setAttribute('href', btn.getAttribute('data-download-url') || '#');
        });
        // Hentikan pemuatan saat modal ditutup.
        modal.addEventListener('hidden.bs.modal', function () { frame.src = ''; });
    })();
</script>
@endpush
