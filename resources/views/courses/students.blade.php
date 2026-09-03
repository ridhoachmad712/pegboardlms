@extends('layouts.app')

@section('title', 'Mahasiswa — ' . $course->name)

@section('content')
@include('courses._hero')

{{-- Kode gabung kelas --}}
<div class="card bg-primary-lt mb-3">
    <div class="card-body d-flex align-items-center flex-wrap gap-3">
        <div>
            <div class="text-secondary small">Kode gabung kelas</div>
            <div class="h2 mb-0 fw-bold" style="letter-spacing:.2em">{{ $course->join_code ?? '—' }}</div>
        </div>
        <div class="text-secondary small me-auto">Bagikan kode ini agar mahasiswa gabung sendiri lewat menu <strong>Kelas Saya → Gabung Kelas</strong>.</div>
        @unless ($course->isCompleted())
        <div class="btn-list">
            <a href="{{ route('enrollments.template') }}" class="btn btn-sm"><i class="ti ti-download me-1"></i>Template CSV</a>
            <form method="POST" action="{{ route('courses.regenerateCode', $course) }}" data-confirm="Buat kode gabung baru? Kode lama tidak berlaku lagi.">
                @csrf @method('PATCH')
                <button class="btn btn-sm"><i class="ti ti-refresh me-1"></i>Kode baru</button>
            </form>
        </div>
        @endunless
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="mb-3 d-flex gap-2 align-items-center">
            @if (! $students->isEmpty())
                <input type="text" class="form-control form-control-sm" style="max-width:240px" placeholder="Cari mahasiswa…" data-table-search="#tbl-students">
            @endif
            @unless ($course->isCompleted())
            <div class="btn-list ms-auto">
                <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modal-import">
                    <i class="ti ti-file-import me-1"></i>Import CSV
                </button>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-enroll">
                    <i class="ti ti-user-plus me-1"></i>Tambah Mahasiswa
                </button>
            </div>
            @endunless
        </div>

        @unless ($course->isCompleted() || $students->isEmpty())
            <div id="bulk-bar" class="alert alert-primary d-none d-flex align-items-center gap-2 py-2 mb-3">
                <span><strong id="bulk-count">0</strong> mahasiswa dipilih</span>
                <form id="bulk-unenroll-form" method="POST" action="{{ route('enrollments.bulkDestroy', $course) }}" class="ms-auto mb-0" data-confirm="Keluarkan semua mahasiswa terpilih dari kelas?">
                    @csrf
                    <span id="bulk-ids"></span>
                    <button type="submit" class="btn btn-sm btn-danger" data-loading="Memproses…"><i class="ti ti-user-minus me-1"></i>Keluarkan terpilih</button>
                </form>
            </div>
        @endunless

        @if ($students->isEmpty())
            <x-empty-state icon="ti-users" title="Belum ada mahasiswa"
                description="Tambahkan mahasiswa secara manual atau impor dari berkas CSV." />
        @else
            <div class="table-responsive">
                <table id="tbl-students" class="table table-vcenter table-sortable">
                    <thead><tr>@unless ($course->isCompleted())<th class="w-1 no-sort"><input type="checkbox" id="check-all" class="form-check-input" aria-label="Pilih semua mahasiswa"></th>@endunless<th class="w-1 no-sort">#</th><th>Nama</th><th>NIM</th><th>Email</th><th class="no-sort"></th></tr></thead>
                    <tbody>
                        @foreach ($students as $i => $student)
                            <tr>
                                @unless ($course->isCompleted())<td><input type="checkbox" class="form-check-input student-check" value="{{ $student->id }}" aria-label="Pilih {{ $student->name }}"></td>@endunless
                                <td class="text-secondary">{{ $i + 1 }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <x-avatar :name="$student->name" :url="$student->avatarUrl()" class="me-2" />
                                        {{ $student->name }}
                                    </div>
                                </td>
                                <td>{{ $student->nim_nip ?? '—' }}</td>
                                <td class="text-secondary">{{ $student->email }}</td>
                                <td class="text-end">
                                    @unless ($course->isCompleted())
                                    <div class="btn-list justify-content-end">
                                        @if (auth()->user()->isAdmin())
                                        <form method="POST" action="{{ route('enrollments.resetPassword', [$course, $student]) }}"
                                              data-confirm="Reset kata sandi {{ $student->name }} menjadi NIM-nya?">
                                            @csrf
                                            <button class="btn btn-sm" title="Reset kata sandi" data-bs-toggle="tooltip"><i class="ti ti-key"></i></button>
                                        </form>
                                        @endif
                                        <form method="POST" action="{{ route('enrollments.destroy', [$course, $student]) }}"
                                              data-confirm="Keluarkan {{ $student->name }} dari kelas?">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-ghost-danger" title="Keluarkan" data-bs-toggle="tooltip"><i class="ti ti-user-minus"></i></button>
                                        </form>
                                    </div>
                                    @endunless
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

{{-- ============ MODALS ============ --}}
@unless ($course->isCompleted())
    {{-- Enroll mahasiswa --}}
    <div class="modal modal-blur fade" id="modal-enroll" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <form class="modal-content" method="POST" action="{{ route('enrollments.store', $course) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Mahasiswa ke Kelas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @if ($availableStudents->isEmpty())
                        <div class="text-secondary">Semua mahasiswa terdaftar sudah ada di kelas ini. Gunakan Import CSV untuk menambah akun baru.</div>
                    @else
                        <p class="text-secondary">Pilih mahasiswa yang ingin ditambahkan:</p>
                        <div style="max-height:320px;overflow:auto;" x-data>
                            @foreach ($availableStudents as $s)
                                <label class="form-check">
                                    <input type="checkbox" name="user_ids[]" value="{{ $s->id }}" class="form-check-input">
                                    <span class="form-check-label">{{ $s->name }} <span class="text-secondary">({{ $s->nim_nip ?? '—' }})</span></span>
                                </label>
                            @endforeach
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" @disabled($availableStudents->isEmpty())>Tambahkan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Import CSV --}}
    <div class="modal modal-blur fade" id="modal-import" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content" method="POST" action="{{ route('enrollments.import', $course) }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Import Mahasiswa dari CSV</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label required">Berkas CSV</label>
                        <input type="file" name="file" class="form-control" accept=".csv,.txt" required>
                    </div>
                    <div class="alert alert-info mb-0">
                        <strong>Format kolom:</strong> <code>nama, email, nim</code> (satu mahasiswa per baris).
                        Akun baru otomatis dibuat dengan kata sandi = NIM.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" data-loading="Mengimpor…">Import</button>
                </div>
            </form>
        </div>
    </div>
@endunless
@endsection

@push('scripts')
<script>
(function () {
    var bar = document.getElementById('bulk-bar');
    if (!bar) return;
    var all = document.getElementById('check-all');
    var countEl = document.getElementById('bulk-count');
    var idsBox = document.getElementById('bulk-ids');
    function checks() { return Array.from(document.querySelectorAll('.student-check')); }
    function sync() {
        var sel = checks().filter(function (c) { return c.checked; });
        countEl.textContent = sel.length;
        bar.classList.toggle('d-none', sel.length === 0);
        idsBox.innerHTML = '';
        sel.forEach(function (c) {
            var h = document.createElement('input');
            h.type = 'hidden'; h.name = 'ids[]'; h.value = c.value;
            idsBox.appendChild(h);
        });
        if (all) {
            all.checked = sel.length > 0 && sel.length === checks().length;
            all.indeterminate = sel.length > 0 && sel.length < checks().length;
        }
    }
    document.addEventListener('change', function (e) {
        if (e.target === all) { checks().forEach(function (c) { c.checked = all.checked; }); }
        if (e.target === all || (e.target.classList && e.target.classList.contains('student-check'))) sync();
    });
    sync();
})();
</script>
@endpush
