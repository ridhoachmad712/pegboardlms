@extends('layouts.app')

@php
    $isGroup = $assignment->isGroup();
    $label = \App\Services\GradingQueue::label($submission, $assignment);
    $hasRubric = $assignment->rubricCriteria->isNotEmpty();
    $query = $onlyUngraded ? ['ungraded' => 1] : [];
    $gradingUrl = fn ($sub) => route('grading.show', array_merge([$assignment, $sub], $query));
@endphp

@section('title', 'Penilaian · '.$label)
@section('page-pretitle', $course->name.' · '.$assignment->title)
@section('page-title', 'Ruang Penilaian')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('courses.show', $course) }}">{{ $course->name }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('assignments.show', $assignment) }}">{{ \Illuminate\Support\Str::limit($assignment->title, 24) }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">Penilaian</li>
@endsection

@section('page-actions')
    <a href="{{ route('assignments.show', $assignment) }}" class="btn"><i class="ti ti-arrow-left me-1"></i>Kembali ke tugas</a>
@endsection

@push('styles')
<style>
    .grading-preview{height:calc(100vh - 16rem);min-height:420px;}
    .grading-preview iframe{width:100%;height:100%;border:0;display:block;}
    .grading-preview img{max-width:100%;max-height:100%;object-fit:contain;}
    .grading-text{white-space:pre-line;overflow:auto;height:100%;}
    @media (min-width:1200px){.grading-panel{position:sticky;top:1rem;}}
    @media (max-width:1199.98px){.grading-preview{height:65vh;min-height:320px;}}
</style>
@endpush

@section('content')
{{-- ============ Bilah navigasi antar mahasiswa ============ --}}
<div class="card mb-3">
    <div class="card-body py-2">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <div class="d-flex align-items-center gap-2 flex-grow-1" style="min-width:0;max-width:34rem">
            <a href="{{ $previous ? $gradingUrl($previous) : '#' }}" class="btn btn-icon {{ $previous ? '' : 'disabled' }}"
               id="nav-prev" title="Sebelumnya (Alt + ←)" aria-label="Pengumpulan sebelumnya" data-bs-toggle="tooltip">
                <i class="ti ti-chevron-left"></i>
            </a>
            <select class="form-select flex-fill" style="min-width:0" id="nav-jump" aria-label="Pilih {{ $isGroup ? 'kelompok' : 'mahasiswa' }}">
                @foreach ($queue as $i => $item)
                    <option value="{{ $gradingUrl($item) }}" @selected($item->id === $submission->id)>
                        {{ $item->isGraded() ? '✓' : '○' }} {{ $i + 1 }}. {{ \App\Services\GradingQueue::label($item, $assignment) }}{{ $item->isGraded() ? ' — '.\App\Support\Grades::num($item->score) : '' }}
                    </option>
                @endforeach
            </select>
            <a href="{{ $next ? $gradingUrl($next) : '#' }}" class="btn btn-icon {{ $next ? '' : 'disabled' }}"
               id="nav-next" title="Berikutnya (Alt + →)" aria-label="Pengumpulan berikutnya" data-bs-toggle="tooltip">
                <i class="ti ti-chevron-right"></i>
            </a>
            </div>
            <span class="text-secondary small text-nowrap">{{ $position }} dari {{ $queue->count() }}</span>
            <div class="ms-auto d-flex align-items-center gap-3">
                <label class="form-check form-switch m-0" title="Navigasi & Simpan-lanjut hanya ke yang belum dinilai">
                    <input class="form-check-input" type="checkbox" id="toggle-ungraded" @checked($onlyUngraded)
                           data-url-on="{{ route('grading.show', [$assignment, $submission, 'ungraded' => 1]) }}"
                           data-url-off="{{ route('grading.show', [$assignment, $submission]) }}">
                    <span class="form-check-label small">Lewati yang sudah dinilai</span>
                </label>
                <div class="text-nowrap small">
                    <span class="fw-bold text-green">{{ $gradedCount }}</span><span class="text-secondary">/{{ $queue->count() }} dinilai</span>
                </div>
            </div>
        </div>
        @php $pct = $queue->count() ? (int) round($gradedCount / $queue->count() * 100) : 0; @endphp
        <div class="progress mt-2" style="height:.25rem"><div class="progress-bar bg-green" style="width:{{ $pct }}%" role="progressbar" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100" aria-label="Progres penilaian"></div></div>
    </div>
</div>

<div class="row g-3">
    {{-- ============ Preview jawaban ============ --}}
    <div class="col-xl-8">
        @php
            $tabs = [];
            foreach ($submission->filePaths() as $slot => $path) {
                $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                $isOffice = in_array($ext, ['doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx'], true);
                $tabs[] = [
                    'key' => 'file-'.$slot,
                    'title' => 'Berkas '.$slot.' · '.strtoupper($ext),
                    'kind' => match (true) {
                        in_array($ext, ['pdf', 'txt'], true) => 'frame',
                        in_array($ext, ['jpg', 'jpeg', 'png'], true) => 'image',
                        $isOffice => 'office',
                        default => 'none',
                    },
                    'src' => $isOffice
                        ? 'https://view.officeapps.live.com/op/embed.aspx?src='.urlencode(\Illuminate\Support\Facades\Storage::disk('local')->temporaryUrl($path, now()->addMinutes(30)))
                        : route('submissions.preview', [$submission, 'slot' => $slot]),
                    'download' => route('submissions.download', [$submission, 'slot' => $slot]),
                ];
            }
            if ($assignment->allowsText() && $submission->answer_text) {
                $tabs[] = ['key' => 'text', 'title' => 'Jawaban teks', 'kind' => 'text', 'src' => null, 'download' => null];
            }
        @endphp

        <div class="card">
            <div class="card-header">
                @if (count($tabs) > 1)
                    <ul class="nav nav-tabs card-header-tabs" role="tablist">
                        @foreach ($tabs as $i => $tab)
                            <li class="nav-item" role="presentation">
                                <button type="button" class="nav-link {{ $i === 0 ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#pane-{{ $tab['key'] }}" role="tab">{{ $tab['title'] }}</button>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <h3 class="card-title">{{ $tabs[0]['title'] ?? 'Jawaban' }}</h3>
                @endif
                @foreach ($tabs as $i => $tab)
                    @if ($tab['download'])
                        <div class="ms-auto btn-list js-tab-actions {{ $i === 0 ? '' : 'd-none' }}" data-tab="pane-{{ $tab['key'] }}">
                            <a href="{{ $tab['src'] }}" target="_blank" rel="noopener" class="btn btn-sm" title="Buka di tab baru"><i class="ti ti-external-link"></i></a>
                            <a href="{{ $tab['download'] }}" class="btn btn-sm"><i class="ti ti-download me-1"></i>Unduh</a>
                        </div>
                    @endif
                @endforeach
            </div>
            @if ($tabs === [])
                <div class="card-body"><x-empty-state icon="ti-file-off" title="Tidak ada berkas atau jawaban teks" description="Pengumpulan ini tidak berisi berkas maupun jawaban teks." /></div>
            @else
                <div class="tab-content">
                    @foreach ($tabs as $i => $tab)
                        <div class="tab-pane {{ $i === 0 ? 'active show' : '' }} grading-preview" id="pane-{{ $tab['key'] }}" role="tabpanel">
                            @if ($tab['kind'] === 'frame' || $tab['kind'] === 'office')
                                {{-- src dipasang saat tab dibuka agar berkas lain tidak ikut dimuat --}}
                                <iframe data-src="{{ $tab['src'] }}" @if ($i === 0) src="{{ $tab['src'] }}" @endif title="Preview {{ $tab['title'] }}"></iframe>
                            @elseif ($tab['kind'] === 'image')
                                <div class="h-100 d-flex align-items-center justify-content-center bg-body-tertiary p-2">
                                    <img src="{{ $tab['src'] }}" alt="{{ $tab['title'] }} — {{ $label }}" loading="lazy">
                                </div>
                            @elseif ($tab['kind'] === 'text')
                                <div class="grading-text p-3">{{ $submission->answer_text }}</div>
                            @else
                                <div class="h-100 d-flex align-items-center justify-content-center p-3">
                                    <x-empty-state icon="ti-file-zip" title="Berkas tidak bisa dipreview" description="Unduh berkas untuk memeriksanya." />
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- ============ Panel nilai ============ --}}
    <div class="col-xl-4">
        <div class="grading-panel">
            <form class="card" method="POST" action="{{ route('submissions.grade', $submission) }}" id="grade-form">
                @csrf
                <input type="hidden" name="ungraded" value="{{ $onlyUngraded ? 1 : 0 }}">
                <div class="card-body border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        @unless ($isGroup)
                            <x-avatar :name="$submission->student->name" :url="$submission->student->avatarUrl()" />
                        @endunless
                        <div class="min-w-0 flex-fill">
                            <div class="fw-bold text-truncate">{{ $label }}</div>
                            <div class="small text-secondary text-truncate">
                                {{ $isGroup ? $submission->group?->members->pluck('name')->join(', ') : ($submission->student->nim_nip ?? '—') }}
                            </div>
                        </div>
                        @if ($submission->isGraded())
                            <span class="badge bg-green-lt" title="Nilai tersimpan">{{ \App\Support\Grades::num($submission->score) }}</span>
                        @endif
                    </div>
                    <div class="mt-2 small">
                        <span class="badge bg-{{ $submission->isLate() ? 'red' : 'green' }}-lt">{{ $submission->isLate() ? 'Terlambat' : 'Tepat waktu' }}</span>
                        <span class="text-secondary ms-1">{{ $submission->submitted_at?->translatedFormat('d M Y H:i') }}</span>
                    </div>
                </div>
                <div class="card-body">
                    @if ($hasRubric)
                        <label class="form-label">Rubrik <span class="text-secondary fw-normal">(nilai dihitung otomatis)</span></label>
                        @foreach ($assignment->rubricCriteria as $crit)
                            @php $cs = $submission->rubricScores->firstWhere('rubric_criterion_id', $crit->id); @endphp
                            <div class="d-flex align-items-center mb-2 gap-2">
                                <label class="me-auto small" for="rubric-{{ $crit->id }}">{{ $crit->name }}
                                    <span class="text-secondary">(maks {{ \App\Support\Grades::num($crit->max_points) }})</span>
                                </label>
                                <input type="number" step="0.01" min="0" max="{{ $crit->max_points }}" id="rubric-{{ $crit->id }}"
                                       name="rubric[{{ $crit->id }}]" value="{{ old('rubric.'.$crit->id, $cs->points ?? '') }}"
                                       class="form-control form-control-sm js-rubric js-first-input" style="max-width:90px" required>
                            </div>
                        @endforeach
                        <div class="text-end mb-3">Total: <strong id="rubric-total">0</strong> / {{ $assignment->max_score }}</div>
                    @else
                        <div class="mb-3">
                            <label class="form-label required" for="score">Nilai (0–{{ $assignment->max_score }})</label>
                            <input type="number" step="0.01" name="score" id="score" class="form-control form-control-lg js-first-input @error('score') is-invalid @enderror"
                                   value="{{ old('score', $submission->score) }}" min="0" max="{{ $assignment->max_score }}" required inputmode="decimal">
                            @error('score')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    @endif
                    <div>
                        <label class="form-label" for="feedback">Feedback</label>
                        <textarea name="feedback" id="feedback" class="form-control" rows="5" placeholder="Catatan untuk {{ $isGroup ? 'kelompok' : 'mahasiswa' }} (opsional)">{{ old('feedback', $submission->feedback) }}</textarea>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="d-flex flex-wrap gap-2">
                        <button type="submit" name="grading" value="next" class="btn btn-primary flex-fill" id="btn-save-next">
                            <i class="ti ti-player-track-next me-1"></i>Simpan &amp; lanjut
                        </button>
                        <button type="submit" name="grading" value="stay" class="btn">
                            <i class="ti ti-device-floppy me-1"></i>Simpan
                        </button>
                    </div>
                    <div class="small text-secondary mt-2 d-none d-md-block">
                        <kbd>Ctrl</kbd>+<kbd>Enter</kbd> simpan &amp; lanjut · <kbd>Alt</kbd>+<kbd>←</kbd>/<kbd>→</kbd> pindah {{ $isGroup ? 'kelompok' : 'mahasiswa' }}
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@if ($next)
    <link rel="prefetch" href="{{ $gradingUrl($next) }}">
@endif
@endsection

@push('scripts')
<script>
(function () {
    var form = document.getElementById('grade-form');
    var dirty = false;
    var submitting = false;

    // Peringatkan bila pindah mahasiswa sebelum nilai disimpan.
    form.addEventListener('input', function () { dirty = true; });
    form.addEventListener('submit', function () { submitting = true; });
    window.addEventListener('beforeunload', function (e) {
        if (dirty && !submitting) { e.preventDefault(); e.returnValue = ''; }
    });
    function go(url) {
        if (!url || url === '#') return;
        if (dirty && !confirm('Nilai belum disimpan. Tinggalkan halaman ini?')) return;
        dirty = false;
        window.location.href = url;
    }

    document.getElementById('nav-jump').addEventListener('change', function () { go(this.value); });
    document.getElementById('toggle-ungraded').addEventListener('change', function () {
        go(this.checked ? this.dataset.urlOn : this.dataset.urlOff);
    });
    ['nav-prev', 'nav-next'].forEach(function (id) {
        document.getElementById(id).addEventListener('click', function (e) { e.preventDefault(); go(this.getAttribute('href')); });
    });

    document.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            e.preventDefault();
            if (form.reportValidity()) { document.getElementById('btn-save-next').click(); }
        } else if (e.altKey && e.key === 'ArrowRight') {
            e.preventDefault(); go(document.getElementById('nav-next').getAttribute('href'));
        } else if (e.altKey && e.key === 'ArrowLeft') {
            e.preventDefault(); go(document.getElementById('nav-prev').getAttribute('href'));
        }
    });

    // Fokus langsung ke input nilai (desktop) agar dosen bisa langsung mengetik.
    var first = form.querySelector('.js-first-input');
    if (first && window.matchMedia('(min-width: 1200px)').matches) { first.focus({ preventScroll: true }); }

    // Tab preview: muat iframe saat tab dibuka, tampilkan tombol unduh milik tab aktif.
    document.querySelectorAll('[data-bs-toggle="tab"]').forEach(function (btn) {
        btn.addEventListener('shown.bs.tab', function () {
            var id = btn.getAttribute('data-bs-target').slice(1);
            var frame = document.querySelector('#' + id + ' iframe');
            if (frame && !frame.getAttribute('src')) { frame.setAttribute('src', frame.dataset.src); }
            document.querySelectorAll('.js-tab-actions').forEach(function (el) {
                el.classList.toggle('d-none', el.dataset.tab !== id);
            });
        });
    });

    // Rubrik: total poin langsung.
    var inputs = form.querySelectorAll('.js-rubric');
    var out = document.getElementById('rubric-total');
    function recalc() {
        var total = 0;
        inputs.forEach(function (i) { total += parseFloat(i.value) || 0; });
        if (out) out.textContent = Math.round(total * 100) / 100;
    }
    inputs.forEach(function (i) { i.addEventListener('input', recalc); });
    recalc();
})();
</script>
@endpush
