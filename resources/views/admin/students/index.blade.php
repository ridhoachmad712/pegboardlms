@extends('layouts.app')

@section('title', 'Mahasiswa')
@section('page-pretitle', 'Admin')
@section('page-title', 'Kelola mahasiswa')

@section('page-actions')
    <div class="btn-list">
        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modal-import"><i class="ti ti-file-import me-1"></i>Import CSV</button>
        <a href="{{ route('admin.students.create') }}" class="btn btn-primary"><i class="ti ti-user-plus me-1"></i>Tambah Mahasiswa</a>
    </div>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Daftar mahasiswa <span class="text-secondary small">({{ $students->total() }})</span></h2>
    </div>
    <div class="card-body border-bottom">
        <form method="GET" action="{{ route('admin.students.index') }}" class="admin-filter">
            <div class="admin-filter-field"><label for="student-course" class="form-label">Kelas</label>
            <select id="student-course" name="course" class="form-select">
                <option value="">Semua kelas</option>
                @foreach ($courses as $c)
                    <option value="{{ $c->id }}" @selected($courseId === $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
            </div>
            <div class="admin-filter-field"><label for="student-search" class="form-label">Cari mahasiswa</label>
                <input id="student-search" type="search" name="q" value="{{ $q }}" class="form-control" placeholder="Nama, NIM, atau email">
            </div><button type="submit" class="btn btn-primary">Tampilkan</button>
            @if($q !== '' || $courseId)<a href="{{ route('admin.students.index') }}" class="btn">Reset filter</a>@endif
        </form>
    </div>
    @if ($students->isEmpty())
        <div class="card-body"><x-empty-state icon="ti-users" title="Tidak ada mahasiswa" :description="($q !== '' || $courseId) ? 'Coba kata kunci lain atau reset filter kelas.' : 'Tambahkan akun mahasiswa atau import dari CSV.'" /></div>
    @else
        {{-- Toolbar aksi massal (muncul saat ada yang dipilih) --}}
        <div id="bulk-bar" class="card-body border-bottom bg-primary-lt d-none py-2">
            <div class="d-flex align-items-center flex-wrap gap-2">
                <span class="me-3"><strong id="bulk-count">0</strong> dipilih</span>
                <div class="btn-list ms-auto">
                    <button type="button" id="bulk-reset" class="btn btn-sm"><i class="ti ti-key me-1"></i>Reset kata sandi</button>
                    <button type="button" id="bulk-delete" class="btn btn-sm btn-danger"><i class="ti ti-trash me-1"></i>Hapus terpilih</button>
                </div>
            </div>
        </div>

        <label class="admin-table-select-all d-none"><input type="checkbox" id="sel-all-mobile" class="form-check-input m-0">Pilih semua pada halaman ini</label>
        <div class="table-responsive">
            <table class="table table-vcenter card-table admin-table admin-students-table">
                <thead><tr>
                    <th style="width:44px"><input type="checkbox" id="sel-all" class="form-check-input m-0" aria-label="Pilih semua pada halaman ini"></th>
                    <th style="width:23%">Nama</th><th style="width:13%">NIM</th><th>Email</th><th style="width:70px" class="text-center">Kelas</th><th style="width:170px"><span class="visually-hidden">Tindakan</span></th>
                </tr></thead>
                <tbody>
                    @foreach ($students as $s)
                        <tr>
                            <td><input id="student-select-{{ $s->id }}" type="checkbox" class="form-check-input m-0 row-select" value="{{ $s->id }}" aria-label="Pilih {{ $s->name }}"></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <x-avatar :name="$s->name" :url="$s->avatarUrl()" class="me-2" />
                                    <label for="student-select-{{ $s->id }}" class="mb-0">{{ $s->name }}</label>
                                </div>
                            </td>
                            <td data-label="NIM">{{ $s->nim_nip ?? '—' }}</td>
                            <td data-label="Email" class="text-secondary">{{ $s->email }}</td>
                            <td data-label="Kelas" class="text-center">{{ $s->enrolled_courses_count }}</td>
                            <td class="text-end admin-table-actions">
                                <div class="btn-list justify-content-end">
                                    <a href="{{ route('admin.students.edit', $s) }}" class="btn btn-sm" aria-label="Edit {{ $s->name }}" title="Edit" data-bs-toggle="tooltip"><i class="ti ti-edit" aria-hidden="true"></i></a>
                                    <form method="POST" action="{{ route('admin.students.resetPassword', $s) }}" data-confirm="Reset kata sandi {{ $s->name }} menjadi NIM-nya?">
                                        @csrf
                                        <button class="btn btn-sm" aria-label="Reset kata sandi {{ $s->name }}" title="Reset kata sandi" data-bs-toggle="tooltip"><i class="ti ti-key" aria-hidden="true"></i></button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.students.destroy', $s) }}" data-confirm="Hapus akun {{ $s->name }}? Semua data (pengumpulan, kehadiran) ikut terhapus.">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-ghost-danger" aria-label="Hapus {{ $s->name }}" title="Hapus" data-bs-toggle="tooltip"><i class="ti ti-trash" aria-hidden="true"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer d-flex align-items-center">
            {{ $students->links('pagination.admin') }}
        </div>
    @endif
</div>

{{-- Import CSV --}}
<div class="modal modal-blur fade" id="modal-import" tabindex="-1" aria-labelledby="import-students-title">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="POST" action="{{ route('admin.students.import') }}" enctype="multipart/form-data">
            @csrf
            <div class="modal-header"><h2 class="modal-title" id="import-students-title">Import mahasiswa</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
            <div class="modal-body">
                <p class="small text-secondary">Gunakan format yang tersedia. <a href="{{ route('enrollments.template') }}">Unduh template CSV</a></p>
                <div class="mb-3"><label for="student-import-file" class="form-label required">Berkas CSV</label><input id="student-import-file" type="file" name="file" class="form-control" accept=".csv,.txt" required></div>
                <div class="alert alert-info mb-0"><strong>Format:</strong> <code>nama, email, nim</code>. Akun baru otomatis dibuat (sandi = NIM). Email yang sudah ada dilewati.</div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-link" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary" data-loading="Mengimpor…">Import</button></div>
        </form>
    </div>
</div>

{{-- Form tersembunyi untuk aksi massal (ID disuntik via JS) --}}
<form id="bulk-form" method="POST" class="d-none">@csrf</form>
@endsection

@push('scripts')
<script>
(function () {
    var selAll = document.getElementById('sel-all');
    var selAllMobile = document.getElementById('sel-all-mobile');
    var bar = document.getElementById('bulk-bar');
    var countEl = document.getElementById('bulk-count');
    var form = document.getElementById('bulk-form');
    if (!form) return;
    var boxes = function () { return Array.prototype.slice.call(document.querySelectorAll('.row-select')); };
    var checked = function () { return boxes().filter(function (b) { return b.checked; }); };

    function refresh() {
        var n = checked().length;
        countEl.textContent = n;
        bar.classList.toggle('d-none', n === 0);
        [selAll, selAllMobile].filter(Boolean).forEach(function (box) { box.checked = n > 0 && n === boxes().length; box.indeterminate = n > 0 && n < boxes().length; });
    }
    if (selAll) { selAll.addEventListener('change', function (e) { boxes().forEach(function (b) { b.checked = e.target.checked; }); refresh(); }); }
    if (selAllMobile) { selAllMobile.addEventListener('change', function (e) { boxes().forEach(function (b) { b.checked = e.target.checked; }); refresh(); }); }
    boxes().forEach(function (b) { b.addEventListener('change', refresh); });

    function submitBulk(action, confirmMsg) {
        var ids = checked().map(function (b) { return b.value; });
        if (!ids.length) return;
        if (confirmMsg && !window.confirm(confirmMsg)) return;
        form.action = action;
        form.querySelectorAll('input[name="ids[]"]').forEach(function (n) { n.remove(); });
        ids.forEach(function (id) {
            var i = document.createElement('input');
            i.type = 'hidden'; i.name = 'ids[]'; i.value = id;
            form.appendChild(i);
        });
        form.submit();
    }
    var rb = document.getElementById('bulk-reset');
    var db = document.getElementById('bulk-delete');
    if (rb) { rb.addEventListener('click', function () { submitBulk(@json(route('admin.students.bulkReset')), 'Reset kata sandi mahasiswa terpilih menjadi NIM masing-masing?'); }); }
    if (db) { db.addEventListener('click', function () { submitBulk(@json(route('admin.students.bulkDestroy')), 'Hapus semua mahasiswa terpilih? Seluruh data (pengumpulan, kehadiran) ikut terhapus dan tidak bisa dibatalkan.'); }); }
})();
</script>
@endpush
