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
        @if ($submissions->whereNotNull('file_path')->isNotEmpty())
            <a href="{{ route('submissions.downloadAll', $assignment) }}" class="btn btn-outline-green"><i class="ti ti-file-zip me-1"></i>Unduh semua (ZIP)</a>
        @endif
        <a href="{{ route('assignments.edit', $assignment) }}" class="btn"><i class="ti ti-edit me-1"></i>Edit</a>
        <form method="POST" action="{{ route('assignments.destroy', $assignment) }}" data-confirm="Hapus tugas ini beserta seluruh pengumpulan?">
            @csrf @method('DELETE')
            <button class="btn btn-danger"><i class="ti ti-trash me-1"></i>Hapus</button>
        </form>
    </div>
@endsection

@section('content')
@php($course = $assignment->course)
@include('courses._subnav')

{{-- Statistik pengumpulan --}}
<div class="row row-cards mb-1">
    <div class="col-6 col-md-3">
        <div class="card card-sm"><div class="card-body text-center">
            <div class="h1 m-0">{{ $stats['submitted'] }}<small class="text-secondary fs-4">/{{ $stats['total'] }}</small></div>
            <div class="text-secondary">Mengumpulkan</div>
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
        <div class="card card-sm"><div class="card-body text-center">
            <div class="h1 m-0 text-orange">{{ $stats['pending'] }}</div>
            <div class="text-secondary">Belum mengumpulkan</div>
        </div></div>
    </div>
    <div class="col-12">
        <div class="progress" style="height:.5rem"><div class="progress-bar bg-primary" style="width:{{ $stats['pct'] }}%" role="progressbar" aria-valuenow="{{ $stats['pct'] }}" aria-valuemin="0" aria-valuemax="100"></div></div>
        <div class="text-secondary small mt-1">{{ $stats['pct'] }}% kelas sudah mengumpulkan.</div>
    </div>
</div>

<div class="row row-cards">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <div class="mb-2"><span class="text-secondary">Deadline</span><div class="fw-bold">{{ $assignment->deadline?->translatedFormat('d M Y H:i') ?? '—' }}</div></div>
                <div class="mb-2"><span class="text-secondary">Nilai maksimal</span><div class="fw-bold">{{ $assignment->max_score }}</div></div>
                @if ($assignment->description)
                    <hr><div class="text-secondary" style="white-space:pre-line">{{ $assignment->description }}</div>
                @endif
            </div>
        </div>

        {{-- Rubrik penilaian --}}
        <div class="card mt-3">
            <div class="card-header py-2"><h3 class="card-title"><i class="ti ti-list-check me-1"></i>Rubrik Penilaian</h3></div>
            <div class="card-body">
                @if ($assignment->rubricCriteria->isEmpty())
                    <p class="text-secondary small mb-2">Belum ada kriteria. Tambahkan kriteria agar penilaian memakai rubrik (nilai akhir = jumlah poin tiap kriteria). Tanpa kriteria, penilaian memakai input nilai tunggal seperti biasa.</p>
                @else
                    @php($critMax = $assignment->rubricCriteria->sum('max_points'))
                    <div class="list-group list-group-flush mb-2">
                        @foreach ($assignment->rubricCriteria as $crit)
                            <div class="list-group-item px-0 d-flex align-items-center">
                                <span class="me-auto">{{ $crit->name }}</span>
                                <span class="badge bg-blue-lt me-2">maks {{ rtrim(rtrim(number_format($crit->max_points, 2, '.', ''), '0'), '.') }}</span>
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
                        Total bobot kriteria: <strong>{{ rtrim(rtrim(number_format($critMax, 2, '.', ''), '0'), '.') }}</strong> / nilai maksimal {{ $assignment->max_score }}.
                        @if ((float) $critMax !== (float) $assignment->max_score) <i class="ti ti-alert-triangle"></i> Sebaiknya disamakan. @endif
                    </div>
                @endif

                @unless ($course->isCompleted())
                    <form method="POST" action="{{ route('rubric.store', $assignment) }}" class="mt-2">
                        @csrf
                        <div class="row g-2">
                            <div class="col-7"><input type="text" name="name" class="form-control form-control-sm" placeholder="Nama kriteria" required aria-label="Nama kriteria"></div>
                            <div class="col-3"><input type="number" step="0.01" min="0.5" name="max_points" class="form-control form-control-sm" placeholder="Maks" required aria-label="Poin maksimal kriteria"></div>
                            <div class="col-2"><button class="btn btn-sm btn-primary w-100" aria-label="Tambah kriteria"><i class="ti ti-plus"></i></button></div>
                        </div>
                    </form>
                @endunless
            </div>
        </div>

        @if ($pending->isNotEmpty())
            <div class="card mt-3">
                <div class="card-header py-2">
                    <h3 class="card-title">Belum mengumpulkan ({{ $stats['pending'] }})</h3>
                    <button class="btn btn-sm ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#pending-list">Lihat</button>
                </div>
                <div class="collapse" id="pending-list">
                    <div class="list-group list-group-flush" style="max-height:280px;overflow:auto">
                        @foreach ($pending as $p)
                            <div class="list-group-item d-flex align-items-center py-2">
                                <x-avatar :name="$p->name" :url="$p->avatarUrl()" class="me-2" />
                                <div><div>{{ $p->name }}</div><div class="small text-secondary">{{ $p->nim_nip ?? '—' }}</div></div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Pengumpulan ({{ $submissions->count() }})</h3>
                @if (! $submissions->isEmpty())
                    <input type="text" class="form-control form-control-sm ms-auto" style="max-width:200px" placeholder="Cari mahasiswa…" data-table-search="#tbl-subs">
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
                                    <td>{!! $sub->isGraded() ? '<span class="fw-bold">'.rtrim(rtrim($sub->score,'0'),'.').'</span>' : '<span class="text-secondary">—</span>' !!}</td>
                                    <td class="text-end">
                                        <div class="btn-list justify-content-end">
                                            @if ($sub->file_path)
                                                <a href="{{ route('submissions.download', $sub) }}" class="btn btn-sm" title="Unduh" data-bs-toggle="tooltip"><i class="ti ti-download"></i></a>
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
    </div>
</div>

{{-- Modal nilai per submission --}}
@foreach ($submissions as $sub)
    <div class="modal modal-blur fade" id="grade-{{ $sub->id }}" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content" method="POST" action="{{ route('submissions.grade', $sub) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Nilai — {{ $sub->student->name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @if ($assignment->rubricCriteria->isNotEmpty())
                        <label class="form-label">Rubrik (nilai dihitung otomatis)</label>
                        @foreach ($assignment->rubricCriteria as $crit)
                            @php($cs = $sub->rubricScores->firstWhere('rubric_criterion_id', $crit->id))
                            <div class="d-flex align-items-center mb-2 gap-2">
                                <div class="me-auto small">{{ $crit->name }}
                                    <span class="text-secondary">(maks {{ rtrim(rtrim(number_format($crit->max_points, 2, '.', ''), '0'), '.') }})</span>
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
