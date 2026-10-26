@extends('layouts.app')

@section('title', 'Semua Kelas')
@section('page-pretitle', 'Admin')
@section('page-title', 'Semua kelas')

@section('content')
<div class="alert alert-info d-flex align-items-center gap-2">
    <i class="ti ti-eye fs-2"></i>
    <div>Buka kelas mana pun untuk memantau pertemuan, materi, tugas, absensi, dan nilai. Admin <strong>hanya bisa melihat</strong>; isi kelas tetap dikelola dosen pengampunya.</div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Daftar kelas <span class="text-secondary small">({{ $courses->total() }})</span></h2>
    </div>
    <div class="card-body border-bottom">
        <form method="GET" action="{{ route('admin.courses.index') }}" class="admin-filter">
            <div class="admin-filter-field"><label for="course-periode" class="form-label">Periode</label>
                <select id="course-periode" name="periode" class="form-select">
                    <option value="">Semua periode</option>
                    @foreach ($periods as $p)
                        <option value="{{ $p['key'] }}" @selected($periode === $p['key'])>{{ $p['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="admin-filter-field"><label for="course-lecturer" class="form-label">Dosen</label>
                <select id="course-lecturer" name="lecturer" class="form-select">
                    <option value="">Semua dosen</option>
                    @foreach ($lecturers as $l)
                        <option value="{{ $l->id }}" @selected($lecturerId === $l->id)>{{ $l->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="admin-filter-field"><label for="course-status" class="form-label">Status</label>
                <select id="course-status" name="status" class="form-select">
                    <option value="">Semua status</option>
                    <option value="{{ \App\Models\Course::STATUS_ACTIVE }}" @selected($status === \App\Models\Course::STATUS_ACTIVE)>Aktif</option>
                    <option value="{{ \App\Models\Course::STATUS_COMPLETED }}" @selected($status === \App\Models\Course::STATUS_COMPLETED)>Selesai</option>
                </select>
            </div>
            <div class="admin-filter-field"><label for="course-search" class="form-label">Cari</label>
                <input id="course-search" type="search" name="q" value="{{ $q }}" class="form-control" placeholder="Mata kuliah, kode, kelas, atau dosen">
            </div>
            <button type="submit" class="btn btn-primary">Tampilkan</button>
            @if ($q !== '' || $lecturerId || $status || $periode !== '')<a href="{{ route('admin.courses.index') }}" class="btn">Reset filter</a>@endif
        </form>
    </div>
    @if ($courses->isEmpty())
        <div class="card-body"><x-empty-state icon="ti-books-off" title="Tidak ada kelas" description="Coba ubah filter atau kata kunci." /></div>
    @else
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead><tr>
                    <th>Mata kuliah</th><th>Dosen</th><th>Periode</th>
                    <th class="text-center">Mahasiswa</th><th class="text-center">Pertemuan</th><th class="text-center">Tugas</th>
                    <th>Status</th><th><span class="visually-hidden">Tindakan</span></th>
                </tr></thead>
                <tbody>
                    @foreach ($courses as $c)
                        <tr>
                            <td>
                                <div class="fw-bold">{{ $c->name }}</div>
                                <div class="small text-secondary">{{ $c->code }}@if ($c->class_name) · Kelas {{ $c->class_name }}@endif</div>
                            </td>
                            <td>{{ $c->lecturer?->name ?? '—' }}</td>
                            <td class="text-nowrap">{{ $c->semester }} {{ $c->year }}</td>
                            <td class="text-center">{{ $c->students_count }}</td>
                            <td class="text-center">{{ $c->meetings_count }}</td>
                            <td class="text-center">{{ $c->assignments_count }}</td>
                            <td><span class="badge bg-{{ $c->isCompleted() ? 'secondary' : 'green' }}-lt">{{ $c->isCompleted() ? 'Selesai' : 'Aktif' }}</span></td>
                            <td class="text-end"><a href="{{ route('courses.show', $c) }}" class="btn btn-sm btn-primary"><i class="ti ti-eye me-1"></i>Pantau</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if ($courses->hasPages())
            <div class="card-footer">{{ $courses->links() }}</div>
        @endif
    @endif
</div>
@endsection
