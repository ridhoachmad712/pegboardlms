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

@section('page-actions')
    <div class="btn-list">
        @if ($submissions->contains(fn ($submission) => $submission->hasFiles()))
            <a href="{{ route('submissions.downloadAll', $assignment) }}" class="btn btn-outline-green"><i class="ti ti-file-zip me-1"></i>Unduh semua (ZIP)</a>
        @endif
        <button type="button" class="btn" data-bs-toggle="modal" data-bs-target="#modal-rubrik">
            <i class="ti ti-list-check me-1"></i>Rubrik
            @if ($assignment->rubricCriteria->isNotEmpty())<span class="badge bg-blue-lt ms-1">{{ $assignment->rubricCriteria->count() }}</span>@endif
        </button>
        <a href="{{ route('assignments.edit', $assignment) }}" class="btn"><i class="ti ti-edit me-1"></i>Edit</a>
        <form method="POST" action="{{ route('assignments.destroy', $assignment) }}" data-confirm="Hapus tugas ini beserta seluruh pengumpulan?">
            @csrf @method('DELETE')
            <button class="btn btn-danger"><i class="ti ti-trash me-1"></i>Hapus</button>
        </form>
    </div>
@endsection

@section('content')
@php($course = $assignment->course)
@php($isGroup = $assignment->isGroup())
@include('courses._subnav')

{{-- Statistik pengumpulan --}}
<div class="row row-cards mb-1">
    <div class="col-6 col-md-3">
        <div class="card card-sm"><div class="card-body text-center">
            <div class="h1 m-0">{{ $stats['submitted'] }}<small class="text-secondary fs-4">/{{ $stats['total'] }}</small></div>
            <div class="text-secondary">{{ $isGroup ? 'Kelompok kumpul' : 'Mengumpulkan' }}</div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card card-sm"><div class="card-body text-center">
            <div class="h1 m-0 text-red">{{ $stats['late'] }}</div>
            <div class="text-secondary">Terlambat</div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card card-sm"><div class="card-body text-center">
            <div class="h1 m-0 text-green">{{ $stats['graded'] }}</div>
            <div class="text-secondary">Sudah dinilai</div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        {{-- Kartu ini bisa diklik untuk melihat daftar yang belum mengumpulkan --}}
        <div class="card card-sm {{ $pending->isNotEmpty() ? 'card-link' : '' }}"
             @if ($pending->isNotEmpty()) role="button" data-bs-toggle="modal" data-bs-target="#modal-pending" @endif>
            <div class="card-body text-center">
                <div class="h1 m-0 text-orange">{{ $stats['pending'] }}</div>
                <div class="text-secondary">{{ $isGroup ? 'Belum berkelompok' : 'Belum mengumpulkan' }} @if ($pending->isNotEmpty())<i class="ti ti-eye ms-1"></i>@endif</div>
            </div>
        </div>
    </div>
    <div class="col-12">
        <div class="progress" style="height:.5rem"><div class="progress-bar bg-primary" style="width:{{ $stats['pct'] }}%" role="progressbar" aria-valuenow="{{ $stats['pct'] }}" aria-valuemin="0" aria-valuemax="100"></div></div>
        <div class="text-secondary small mt-1">{{ $stats['pct'] }}% kelas sudah mengumpulkan.</div>
    </div>
</div>

{{-- Info tugas: strip ringkas (horizontal) --}}
<div class="card mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-6 col-md-3">
                <div class="text-secondary small">Deadline</div>
                <div class="fw-bold">{{ $assignment->deadline?->translatedFormat('d M Y H:i') ?? '—' }}</div>
            </div>
            <div class="col-6 col-md-3">
                <div class="text-secondary small">Nilai maksimal</div>
                <div class="fw-bold">{{ $assignment->max_score }}</div>
            </div>
            <div class="col-6 col-md-3">
                <div class="text-secondary small">Bentuk tugas</div>
                <div class="fw-bold">
                    {{ $isGroup ? 'Kelompok' : 'Individu' }}
                    @if ($isGroup && $assignment->group_max)<span class="text-secondary fw-normal">· maks {{ $assignment->group_max }}</span>@endif
                    <div class="small fw-normal text-secondary">{{ \App\Models\Assignment::SUBMISSION_MODES[$assignment->submission_mode] ?? 'Unggah berkas' }}</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="text-secondary small">Penilaian</div>
                <div class="fw-bold">{{ $assignment->rubricCriteria->isNotEmpty() ? 'Rubrik ('.$assignment->rubricCriteria->count().' kriteria)' : 'Nilai tunggal' }}</div>
            </div>
        </div>
        @if ($assignment->description)
            <hr class="my-3">
            <div class="text-secondary" style="white-space:pre-line">{{ $assignment->description }}</div>
        @endif
        @if ($assignment->hasInstructionFile())
            <div class="{{ $assignment->description ? 'mt-3' : 'mt-2' }}">
                <a href="{{ route('assignments.instruction.download', $assignment) }}" class="list-group-item list-group-item-action border rounded d-flex align-items-center gap-2 py-2 px-3">
                    <span class="avatar avatar-sm bg-blue-lt"><i class="ti ti-file-download" aria-hidden="true"></i></span>
                    <span class="flex-fill min-w-0"><span class="d-block fw-semibold text-truncate">{{ $assignment->instruction_file_name }}</span><span class="d-block small text-secondary">Lampiran instruksi{{ $assignment->instructionFileSizeLabel() ? ' · '.$assignment->instructionFileSizeLabel() : '' }}</span></span>
                    <i class="ti ti-download text-secondary" aria-hidden="true"></i>
                </a>
            </div>
        @endif
    </div>
</div>

{{-- ============ MODE KELOMPOK: daftar per kelompok ============ --}}
@if ($isGroup)
<div class="card">
    <div class="card-header"><h3 class="card-title">Kelompok ({{ $groups->count() }})</h3></div>
    @if ($groups->isEmpty())
        <div class="card-body"><x-empty-state icon="ti-users-group" title="Belum ada kelompok"
            description="Mahasiswa membentuk kelompok sendiri. Anda juga bisa membentuk dari daftar 'Belum berkelompok' di bawah." /></div>
    @else
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead><tr><th>Kelompok</th><th>Anggota</th><th>Status</th><th>Nilai</th><th></th></tr></thead>
                <tbody>
                    @foreach ($groups as $g)
                        @php($sub = $g->submission)
                        <tr>
                            <td class="fw-bold">{{ $g->name }}</td>
                            <td class="small text-secondary">{{ $g->members->pluck('name')->join(', ') }}</td>
                            <td>
                                @if ($sub)
                                    <span class="badge bg-{{ $sub->isLate() ? 'red' : 'green' }}-lt">{{ $sub->isLate() ? 'Terlambat' : 'Tepat waktu' }}</span>
                                @else
                                    <span class="badge bg-secondary-lt">Belum kumpul</span>
                                @endif
                            </td>
                            <td>{!! $sub && $sub->isGraded() ? '<span class="fw-bold">'.\App\Support\Grades::num($sub->score).'</span>' : '<span class="text-secondary">—</span>' !!}</td>
                            <td class="text-end">
                                <div class="btn-list justify-content-end">
                                    @if ($sub?->hasFiles())
                                        @include('assignments._submission-file-actions', ['submission' => $sub, 'title' => $g->name])
                                    @endif
                                    @if ($sub)
                                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#grade-{{ $sub->id }}">Nilai</button>
                                        <form method="POST" action="{{ route('submissions.reopen', $sub) }}" data-confirm="Buka kembali pengumpulan {{ $g->name }}? Berkas dihapus dan kelompok bisa mengumpulkan ulang.">
                                            @csrf
                                            <button class="btn btn-sm" title="Buka kembali" data-bs-toggle="tooltip"><i class="ti ti-lock-open"></i></button>
                                        </form>
                                    @endif
                                    @unless ($course->isCompleted())
                                        <form method="POST" action="{{ route('assignment-groups.destroy', $g) }}" data-confirm="Bubarkan {{ $g->name }}?@if ($sub) Pengumpulan kelompok ikut terhapus.@endif">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-ghost-danger" title="Bubarkan kelompok"><i class="ti ti-users-minus"></i></button>
                                        </form>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
    @unless ($course->isCompleted())
        @if ($ungrouped->isNotEmpty())
            <div class="card-footer">
                <form method="POST" action="{{ route('assignment-groups.store', $assignment) }}">
                    @csrf
                    <div class="text-secondary small mb-2"><i class="ti ti-users-plus me-1"></i>Bentuk kelompok dari mahasiswa yang belum berkelompok ({{ $ungrouped->count() }}):</div>
                    <div class="row">
                        @foreach ($ungrouped as $u)
                            <div class="col-md-4"><label class="form-check">
                                <input type="checkbox" name="members[]" value="{{ $u->id }}" class="form-check-input">
                                <span class="form-check-label">{{ $u->name }}</span>
                            </label></div>
                        @endforeach
                    </div>
                    <button class="btn btn-sm btn-primary mt-2">Buat Kelompok</button>
                    @if ($assignment->group_max)<small class="form-hint d-inline ms-2">Maks {{ $assignment->group_max }} anggota.</small>@endif
                </form>
            </div>
        @endif
    @endunless
</div>
@else
{{-- Pengumpulan: lebar penuh --}}
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Pengumpulan ({{ $submissions->count() }})</h3>
        @if (! $submissions->isEmpty())
            <input type="text" class="form-control form-control-sm ms-auto" style="max-width:220px" placeholder="Cari mahasiswa…" data-table-search="#tbl-subs">
        @endif
    </div>
    @if (! $submissions->isEmpty())
        <div class="card-body py-2 border-bottom">
            <div class="btn-group btn-group-sm" role="group" id="sub-filter">
                <button type="button" class="btn active" data-filter="all">Semua</button>
                <button type="button" class="btn" data-filter="ontime">Tepat waktu</button>
                <button type="button" class="btn" data-filter="late">Terlambat</button>
                <button type="button" class="btn" data-filter="ungraded">Belum dinilai</button>
            </div>
        </div>
    @endif
    @if ($submissions->isEmpty())
        <div class="card-body"><x-empty-state icon="ti-inbox" title="Belum ada pengumpulan" /></div>
    @else
        <div class="table-responsive">
            <table id="tbl-subs" class="table table-vcenter card-table table-sortable">
                <thead><tr><th>Mahasiswa</th><th>Status</th><th>Waktu</th><th>Nilai</th><th class="no-sort"></th></tr></thead>
                <tbody>
                    @foreach ($submissions as $sub)
                        <tr data-late="{{ $sub->isLate() ? 1 : 0 }}" data-graded="{{ $sub->isGraded() ? 1 : 0 }}">
                            <td>{{ $sub->student->name }}<div class="small text-secondary">{{ $sub->student->nim_nip }}</div></td>
                            <td><span class="badge bg-{{ $sub->isLate() ? 'red' : 'green' }}-lt">{{ $sub->isLate() ? 'Terlambat' : 'Tepat waktu' }}</span></td>
                            <td class="text-secondary small">{{ $sub->submitted_at?->translatedFormat('d M H:i') }}</td>
                            <td>{!! $sub->isGraded() ? '<span class="fw-bold">'.\App\Support\Grades::num($sub->score).'</span>' : '<span class="text-secondary">—</span>' !!}</td>
                            <td class="text-end">
                                <div class="btn-list justify-content-end">
                                    @if ($sub->hasFiles())
                                        @include('assignments._submission-file-actions', ['submission' => $sub, 'title' => $sub->student->name])
                                    @endif
                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#grade-{{ $sub->id }}">Nilai</button>
                                    <form method="POST" action="{{ route('submissions.reopen', $sub) }}" data-confirm="Buka kembali pengumpulan {{ $sub->student->name }}? Berkas saat ini akan dihapus dan mahasiswa bisa mengumpulkan ulang.">
                                        @csrf
                                        <button class="btn btn-sm" title="Buka kembali" data-bs-toggle="tooltip"><i class="ti ti-lock-open"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endif

{{-- ===================== MODAL RUBRIK ===================== --}}
<div class="modal modal-blur fade" id="modal-rubrik" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti ti-list-check me-1"></i>Rubrik Penilaian</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                @if ($assignment->rubricCriteria->isEmpty())
                    <p class="text-secondary small mb-2">Belum ada kriteria. Tambahkan kriteria agar penilaian memakai rubrik (nilai akhir = jumlah poin tiap kriteria). Tanpa kriteria, penilaian memakai input nilai tunggal seperti biasa.</p>
                @else
                    @php($critMax = $assignment->rubricCriteria->sum('max_points'))
                    <div class="list-group list-group-flush mb-2">
                        @foreach ($assignment->rubricCriteria as $crit)
                            <div class="list-group-item px-0 d-flex align-items-center">
                                <span class="me-auto">{{ $crit->name }}</span>
                                <span class="badge bg-blue-lt me-2">maks {{ \App\Support\Grades::num($crit->max_points) }}</span>
                                @unless ($course->isCompleted())
                                    <form method="POST" action="{{ route('rubric.destroy', $crit) }}" data-confirm="Hapus kriteria &quot;{{ $crit->name }}&quot;? Skor rubrik terkait ikut terhapus.">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-ghost-danger" title="Hapus kriteria" aria-label="Hapus kriteria {{ $crit->name }}"><i class="ti ti-trash"></i></button>
                                    </form>
                                @endunless
                            </div>
                        @endforeach
                    </div>
                    <div class="small {{ (float) $critMax === (float) $assignment->max_score ? 'text-secondary' : 'text-orange' }}">
                        Total bobot kriteria: <strong>{{ \App\Support\Grades::num($critMax) }}</strong> / nilai maksimal {{ $assignment->max_score }}.
                        @if ((float) $critMax !== (float) $assignment->max_score) <i class="ti ti-alert-triangle"></i> Sebaiknya disamakan. @endif
                    </div>
                @endif
            </div>
            @unless ($course->isCompleted())
            <div class="modal-footer">
                <form class="w-100" method="POST" action="{{ route('rubric.store', $assignment) }}">
                    @csrf
                    <div class="text-secondary small mb-2">Tambah kriteria</div>
                    <div class="row g-2">
                        <div class="col-7"><input type="text" name="name" class="form-control" placeholder="Nama kriteria" required aria-label="Nama kriteria"></div>
                        <div class="col-3"><input type="number" step="0.01" min="0.5" name="max_points" class="form-control" placeholder="Maks" required aria-label="Poin maksimal kriteria"></div>
                        <div class="col-2"><button class="btn btn-primary w-100" aria-label="Tambah kriteria"><i class="ti ti-plus"></i></button></div>
                    </div>
                </form>
            </div>
            @endunless
        </div>
    </div>
</div>

{{-- ===================== MODAL BELUM MENGUMPULKAN ===================== --}}
@if ($pending->isNotEmpty())
    <div class="modal modal-blur fade" id="modal-pending" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ $isGroup ? 'Belum berkelompok' : 'Belum mengumpulkan' }} ({{ $stats['pending'] }})</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="list-group list-group-flush">
                        @foreach ($pending as $p)
                            <div class="list-group-item d-flex align-items-center py-2">
                                <x-avatar :name="$p->name" :url="$p->avatarUrl()" class="me-2" />
                                <div><div>{{ $p->name }}</div><div class="small text-secondary">{{ $p->nim_nip ?? '—' }}</div></div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif

{{-- Modal preview berkas jawaban (PDF inline / Office via viewer) --}}
<div class="modal modal-blur fade" id="modal-preview" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-truncate" id="preview-title">Preview Berkas</h5>
                <a href="#" id="preview-download" class="btn btn-sm ms-auto"><i class="ti ti-download me-1"></i>Unduh</a>
                <button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body p-0" style="height:80vh">
                <iframe id="preview-frame" src="" title="Preview berkas jawaban" style="width:100%;height:100%;border:0"></iframe>
            </div>
        </div>
    </div>
</div>

{{-- Modal nilai per submission --}}
@foreach ($submissions as $sub)
    <div class="modal modal-blur fade" id="grade-{{ $sub->id }}" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content" method="POST" action="{{ route('submissions.grade', $sub) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Nilai — {{ $isGroup ? ($sub->group?->name ?? 'Kelompok') : $sub->student->name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    {{-- Jawaban mahasiswa: teks langsung dan/atau berkas --}}
                    @if ($assignment->allowsText() && $sub->answer_text)
                        <div class="mb-3">
                            <label class="form-label">Jawaban teks mahasiswa</label>
                            <div class="border rounded p-2" style="white-space:pre-line;max-height:240px;overflow:auto">{{ $sub->answer_text }}</div>
                        </div>
                    @endif
                    @if ($sub->hasFiles())
                        <div class="mb-3 btn-list">
                            @include('assignments._submission-file-actions', ['submission' => $sub, 'title' => $isGroup ? ($sub->group?->name ?? 'Kelompok') : $sub->student->name, 'buttonClass' => 'btn-outline-secondary', 'showLabels' => true])
                        </div>
                    @endif
                    @if ($assignment->rubricCriteria->isNotEmpty())
                        <label class="form-label">Rubrik (nilai dihitung otomatis)</label>
                        @foreach ($assignment->rubricCriteria as $crit)
                            @php($cs = $sub->rubricScores->firstWhere('rubric_criterion_id', $crit->id))
                            <div class="d-flex align-items-center mb-2 gap-2">
                                <div class="me-auto small">{{ $crit->name }}
                                    <span class="text-secondary">(maks {{ \App\Support\Grades::num($crit->max_points) }})</span>
                                </div>
                                <input type="number" step="0.01" min="0" max="{{ $crit->max_points }}"
                                       name="rubric[{{ $crit->id }}]" value="{{ $cs->points ?? '' }}"
                                       class="form-control form-control-sm js-rubric" data-modal="grade-{{ $sub->id }}"
                                       style="max-width:90px" aria-label="Poin {{ $crit->name }}" required>
                            </div>
                        @endforeach
                        <div class="text-end mb-3">Total: <strong class="js-rubric-total" data-modal="grade-{{ $sub->id }}">0</strong> / {{ $assignment->max_score }}</div>
                    @else
                        <div class="mb-3">
                            <label class="form-label required">Nilai (0–{{ $assignment->max_score }})</label>
                            <input type="number" step="0.01" name="score" class="form-control" value="{{ $sub->score }}" min="0" max="{{ $assignment->max_score }}" required>
                        </div>
                    @endif
                    <div class="mb-3">
                        <label class="form-label">Feedback</label>
                        <textarea name="feedback" class="form-control" rows="3">{{ $sub->feedback }}</textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-primary">Simpan Nilai</button>
                </div>
            </form>
        </div>
    </div>
@endforeach
@endsection

@push('scripts')
<script>
(function () {
    var group = document.getElementById('sub-filter');
    var table = document.getElementById('tbl-subs');
    if (!group || !table) return;
    group.querySelectorAll('button').forEach(function (btn) {
        btn.addEventListener('click', function () {
            group.querySelectorAll('button').forEach(function (b) { b.classList.remove('active'); });
            btn.classList.add('active');
            var f = btn.dataset.filter;
            table.querySelectorAll('tbody tr').forEach(function (tr) {
                var show = f === 'all'
                    || (f === 'late' && tr.dataset.late === '1')
                    || (f === 'ontime' && tr.dataset.late === '0')
                    || (f === 'ungraded' && tr.dataset.graded === '0');
                tr.style.display = show ? '' : 'none';
            });
        });
    });
})();

// Preview berkas jawaban: isi iframe modal dari tombol yang diklik.
(function () {
    var modal = document.getElementById('modal-preview');
    if (!modal) return;
    var frame = document.getElementById('preview-frame');
    var titleEl = document.getElementById('preview-title');
    var dl = document.getElementById('preview-download');
    modal.addEventListener('show.bs.modal', function (e) {
        var btn = e.relatedTarget;
        if (!btn) return;
        frame.src = btn.getAttribute('data-preview-url') || '';
        titleEl.textContent = btn.getAttribute('data-preview-title') || 'Preview Berkas';
        if (dl) dl.setAttribute('href', btn.getAttribute('data-download-url') || '#');
    });
    modal.addEventListener('hidden.bs.modal', function () { frame.src = ''; });
})();

// Rubrik: hitung total poin secara langsung di modal nilai.
(function () {
    function recalc(modalId) {
        var total = 0;
        document.querySelectorAll('.js-rubric[data-modal="' + modalId + '"]').forEach(function (i) {
            total += parseFloat(i.value) || 0;
        });
        var out = document.querySelector('.js-rubric-total[data-modal="' + modalId + '"]');
        if (out) out.textContent = Math.round(total * 100) / 100;
    }
    document.querySelectorAll('.js-rubric').forEach(function (input) {
        input.addEventListener('input', function () { recalc(input.getAttribute('data-modal')); });
    });
    document.querySelectorAll('.js-rubric-total').forEach(function (el) { recalc(el.getAttribute('data-modal')); });
})();
</script>
@endpush
