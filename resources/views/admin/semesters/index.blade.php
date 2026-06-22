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
                <div class="h1 mb-0">{{ $semester }} {{ $academicYear }}</div>
                <div class="form-hint mt-1">Dipakai sebagai nilai default saat membuat kelas baru. Ubah lewat tombol <strong>Aktifkan</strong> di daftar bawah.</div>
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
            <table class="table table-vcenter card-table">
                <thead><tr>
                    <th>Periode</th>
                    <th class="text-center">Kelas</th>
                    <th class="text-center">Dosen</th>
                    <th class="text-center">Mahasiswa</th>
                    <th class="text-end">Aksi</th>
                </tr></thead>
                <tbody>
                    @foreach ($periods as $p)
                        @php $isActive = $p->key === $activePeriod; @endphp
                        <tr @class(['table-active' => $isActive])>
                            <td>
                                <span class="fw-bold">{{ $p->label }}</span>
                                @if ($isActive)
                                    <span class="badge bg-green-lt ms-1"><i class="ti ti-circle-check-filled me-1"></i>Aktif</span>
                                @endif
                            </td>
                            <td class="text-center">{{ $p->courses_count }}</td>
                            <td class="text-center">{{ $p->lecturers_count }}</td>
                            <td class="text-center">{{ $p->students_count }}</td>
                            <td>
                                <div class="btn-list justify-content-end">
                                    @if ($isActive)
                                        <button class="btn btn-sm" disabled><i class="ti ti-check me-1"></i>Aktif</button>
                                    @else
                                        <form method="POST" action="{{ route('admin.semesters.updateActive') }}">
                                            @csrf @method('PUT')
                                            <input type="hidden" name="academic_year" value="{{ $p->year }}">
                                            <input type="hidden" name="semester" value="{{ $p->semester }}">
                                            <button class="btn btn-sm btn-outline-primary" title="Jadikan semester aktif" data-bs-toggle="tooltip">
                                                <i class="ti ti-circle-check me-1"></i>Aktifkan
                                            </button>
                                        </form>
                                    @endif

                                    @if ($p->id)
                                        <form method="POST" action="{{ route('admin.semesters.destroy', $p->id) }}"
                                              data-confirm="Hapus semester {{ $p->label }} dari daftar?@if ($p->courses_count > 0) (Akan ditolak karena masih ada {{ $p->courses_count }} kelas.)@endif">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-ghost-danger" title="Hapus semester" data-bs-toggle="tooltip">
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

        <div class="card-footer bg-transparent">
            <div class="alert alert-warning mb-0" role="alert">
                <div class="d-flex">
                    <div class="me-2"><i class="ti ti-info-circle fs-2"></i></div>
                    <div>
                        Semester hanya bisa dihapus jika <strong>tidak ada kelas</strong> di dalamnya. Jika masih ada kelas,
                        pindahkan atau hapus kelasnya terlebih dahulu.
                    </div>
                </div>
            </div>
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
