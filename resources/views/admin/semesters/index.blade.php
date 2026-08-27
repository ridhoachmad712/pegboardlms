@extends('layouts.app')

@section('title', 'Kelola Semester')
@section('page-pretitle', 'Admin')
@section('page-title', 'Kelola Semester')

@section('page-actions')
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-add-semester">
        <i class="ti ti-plus me-1"></i>Tambah Semester
    </button>
@endsection

@section('content')
{{-- ===================== SEMESTER AKTIF ===================== --}}
<div class="card mb-3">
    <div class="card-body">
        <div class="row align-items-center g-3">
            <div class="col-auto">
                <span class="avatar avatar-lg rounded bg-primary-lt"><i class="ti ti-calendar-event icon-lg"></i></span>
            </div>
            <div class="col">
                <div class="text-secondary">Semester aktif saat ini</div>
                <div class="mt-1">
                    @forelse ($activeKeys as $k)
                        <span class="badge bg-green-lt fs-3 me-1"><i class="ti ti-circle-check-filled me-1"></i>{{ \App\Models\Semester::keyLabel($k) }}</span>
                    @empty
                        <span class="text-secondary">Belum ada.</span>
                    @endforelse
                </div>
                <div class="form-hint mt-1">Boleh lebih dari satu — kelas dari semua semester aktif tampil bersama di Dashboard &amp; Kelas Saya. Periode terbaru dipakai sebagai default saat membuat kelas baru. Centang di daftar bawah lalu <strong>Simpan</strong>.</div>
            </div>
        </div>
    </div>
</div>

{{-- ===================== DAFTAR PERIODE ===================== --}}
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Periode Akademik</h3>
        <div class="card-actions text-secondary">{{ $periods->count() }} semester</div>
    </div>

    @if ($periods->isEmpty())
        <div class="card-body">
            <x-empty-state icon="ti-calendar" title="Belum ada semester"
                description="Tambahkan semester lewat tombol “Tambah Semester” di kanan atas." />
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-vcenter card-table admin-table">
                <thead><tr>
                    <th style="width:70px" class="text-center">Aktif</th>
                    <th>Periode</th>
                    <th class="text-center">Kelas</th>
                    <th class="text-center">Dosen</th>
                    <th class="text-center">Mahasiswa</th>
                    <th class="text-end">Aksi</th>
                </tr></thead>
                <tbody>
                    @foreach ($periods as $p)
                        @php $isActive = in_array($p->key, $activeKeys, true); @endphp
                        <tr @class(['table-active' => $isActive])>
                            <td class="text-center" data-label="Aktif">
                                {{-- Terhubung ke form Simpan via atribut form= (hindari form bersarang) --}}
                                <input class="form-check-input m-0" type="checkbox" name="periods[]"
                                       value="{{ $p->key }}" form="active-form" @checked($isActive)
                                       aria-label="Aktifkan {{ $p->label }}">
                            </td>
                            <td>
                                <span class="fw-bold">{{ $p->label }}</span>
                                @if ($isActive)
                                    <span class="badge bg-green-lt ms-1"><i class="ti ti-circle-check-filled me-1"></i>Aktif</span>
                                @endif
                            </td>
                            <td class="text-center" data-label="Kelas">{{ $p->courses_count }}</td>
                            <td class="text-center" data-label="Dosen">{{ $p->lecturers_count }}</td>
                            <td class="text-center" data-label="Mahasiswa">{{ $p->students_count }}</td>
                            <td class="admin-table-actions">
                                <div class="btn-list justify-content-end">
                                    @if ($p->id)
                                        <form method="POST" action="{{ route('admin.semesters.destroy', $p->id) }}"
                                              data-confirm="Hapus semester {{ $p->label }} dari daftar?@if ($p->courses_count > 0) (Akan ditolak karena masih ada {{ $p->courses_count }} kelas.)@endif">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-ghost-danger" title="Hapus semester" aria-label="Hapus semester {{ $p->label }}" data-bs-toggle="tooltip">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="card-footer d-flex align-items-center flex-wrap gap-2">
            <div class="text-secondary small">Centang semester yang ingin diaktifkan (boleh lebih dari satu), lalu simpan. Semester hanya bisa dihapus bila tidak ada kelas di dalamnya.</div>
            <form id="active-form" method="POST" action="{{ route('admin.semesters.updateActive') }}" class="ms-auto">
                @csrf @method('PUT')
                <button class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i>Simpan Semester Aktif</button>
            </form>
        </div>
    @endif
</div>

{{-- ===================== MODAL TAMBAH SEMESTER ===================== --}}
<div class="modal modal-blur fade" id="modal-add-semester" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="POST" action="{{ route('admin.semesters.store') }}">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Tambah Semester</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-7 mb-3">
                        <label class="form-label required">Tahun Ajaran</label>
                        <input type="number" name="year" class="form-control" value="{{ old('year', $academicYear) }}" min="2000" max="2100" required>
                    </div>
                    <div class="col-5 mb-3">
                        <label class="form-label required">Semester</label>
                        <select name="semester" class="form-select">
                            @foreach (['Ganjil', 'Genap', 'Antara'] as $s)
                                <option value="{{ $s }}">{{ $s }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="text-secondary small">Semester baru akan muncul di daftar Periode Akademik. Aktifkan lewat tombol di daftar bila ingin menjadikannya default kelas baru.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link" data-bs-dismiss="modal">Batal</button>
                <button class="btn btn-primary"><i class="ti ti-plus me-1"></i>Tambah</button>
            </div>
        </form>
    </div>
</div>
@endsection
