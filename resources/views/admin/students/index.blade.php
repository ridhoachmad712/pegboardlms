@extends('layouts.app')

@section('title', 'Mahasiswa')
@section('page-pretitle', 'Admin')
@section('page-title', 'Manajemen Mahasiswa')

@section('page-actions')
    <div class="btn-list">
        <a href="{{ route('enrollments.template') }}" class="btn"><i class="ti ti-download me-1"></i>Template CSV</a>
        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modal-import"><i class="ti ti-file-import me-1"></i>Import CSV</button>
        <a href="{{ route('admin.students.create') }}" class="btn btn-primary"><i class="ti ti-user-plus me-1"></i>Tambah Mahasiswa</a>
    </div>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Total: {{ $students->total() }} mahasiswa</h3>
        <form method="GET" action="{{ route('admin.students.index') }}" class="ms-auto d-flex gap-2">
            <select name="course" class="form-select" onchange="this.form.submit()" style="min-width:160px">
                <option value="">Semua kelas</option>
                @foreach ($courses as $c)
                    <option value="{{ $c->id }}" @selected($courseId === $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
            <div class="input-icon">
                <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="Cari nama / NIM / email…">
            </div>
        </form>
    </div>
    @if ($students->isEmpty())
        <div class="card-body"><x-empty-state icon="ti-users" title="Tidak ada mahasiswa" :description="$q ? 'Tidak ada hasil untuk pencarian.' : 'Tambahkan akun mahasiswa.'" /></div>
    @else
        {{-- Toolbar aksi massal (muncul saat ada yang dipilih) --}}
        <div id="bulk-bar" class="card-body border-bottom bg-primary-lt d-none py-2">
            <div class="d-flex align-items-center">
                <span class="me-3"><strong id="bulk-count">0</strong> dipilih</span>
                <div class="btn-list ms-auto">
                    <button type="button" id="bulk-reset" class="btn btn-sm"><i class="ti ti-key me-1"></i>Reset kata sandi</button>
                    <button type="button" id="bulk-delete" class="btn btn-sm btn-danger"><i class="ti ti-trash me-1"></i>Hapus terpilih</button>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead><tr>
                    <th class="w-1"><input type="checkbox" id="sel-all" class="form-check-input m-0"></th>
                    <th>Nama</th><th>NIM</th><th>Email</th><th class="text-center">Kelas</th><th></th>
                </tr></thead>
                <tbody>
                    @foreach ($students as $s)
                        <tr>
                            <td><input type="checkbox" class="form-check-input m-0 row-select" value="{{ $s->id }}"></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <x-avatar :name="$s->name" :url="$s->avatarUrl()" class="me-2" />
                                    {{ $s->name }}
                                </div>
                            </td>
                            <td>{{ $s->nim_nip ?? '—' }}</td>
                            <td class="text-secondary">{{ $s->email }}</td>
                            <td class="text-center">{{ $s->enrolled_courses_count }}</td>
                            <td class="text-end">
                                <div class="btn-list justify-content-end">
                                    <a href="{{ route('admin.students.edit', $s) }}" class="btn btn-sm" title="Edit" data-bs-toggle="tooltip"><i class="ti ti-edit"></i></a>
                                    <form method="POST" action="{{ route('admin.students.resetPassword', $s) }}" data-confirm="Reset kata sandi {{ $s->name }} menjadi NIM-nya?">
                                        @csrf
                                        <button class="btn btn-sm" title="Reset kata sandi" data-bs-toggle="tooltip"><i class="ti ti-key"></i></button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.students.destroy', $s) }}" data-confirm="Hapus akun {{ $s->name }}? Semua data (pengumpulan, kehadiran) ikut terhapus.">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-ghost-danger" title="Hapus" data-bs-toggle="tooltip"><i class="ti ti-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer d-flex align-items-center">
            {{ $students->links() }}
        </div>
    @endif
</div>

{{-- Import CSV --}}
<div class="modal modal-blur fade" id="modal-import" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="POST" action="{{ route('admin.students.import') }}" enctype="multipart/form-data">
            @csrf
            <div class="modal-header"><h5 class="modal-title">Import Mahasiswa (CSV)</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label required">Berkas CSV</label><input type="file" name="file" class="form-control" accept=".csv,.txt" required></div>
                <div class="alert alert-info mb-0"><strong>Format:</strong> <code>nama, email, nim</code>. Akun baru otomatis dibuat (sandi = NIM). Email yang sudah ada dilewati.</div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-link" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Import</button></div>
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
        if (selAll) { selAll.checked = n > 0 && n === boxes().length; }
    }
    if (selAll) { selAll.addEventListener('change', function (e) { boxes().forEach(function (b) { b.checked = e.target.checked; }); refresh(); }); }
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
