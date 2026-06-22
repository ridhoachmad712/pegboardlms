@extends('layouts.app')

@section('title', 'Tong Sampah Kelas')
@section('page-pretitle', 'Administrasi')
@section('page-title', 'Tong Sampah Kelas')

@section('page-actions')
    <a href="{{ route('courses.index') }}" class="btn"><i class="ti ti-arrow-left me-1"></i>Kembali ke Kelas Saya</a>
@endsection

@section('content')
@if ($courses->isEmpty())
    <div class="card">
        <div class="card-body">
            <x-empty-state icon="ti-trash" title="Tong sampah kosong"
                description="Kelas yang Anda hapus akan muncul di sini dan bisa dipulihkan." />
        </div>
    </div>
@else
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Kelas terhapus</h3>
            <div class="card-actions text-secondary small">Pulihkan kelas, atau hapus permanen beserta seluruh datanya.</div>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead>
                    <tr>
                        <th>Kelas</th><th>Kode</th><th>Semester</th>
                        <th class="text-center">Mahasiswa</th><th class="text-center">Pertemuan</th>
                        <th>Dihapus</th><th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($courses as $course)
                        <tr>
                            <td class="fw-bold">{{ $course->name }}</td>
                            <td><span class="text-secondary">{{ $course->code }}</span></td>
                            <td>{{ $course->semester }} {{ $course->year }}</td>
                            <td class="text-center">{{ $course->students_count }}</td>
                            <td class="text-center">{{ $course->meetings_count }}</td>
                            <td class="text-secondary">{{ $course->deleted_at?->translatedFormat('d M Y H:i') }}</td>
                            <td class="text-end">
                                <div class="btn-list justify-content-end">
                                    <form method="POST" action="{{ route('courses.restore', $course->id) }}">
                                        @csrf @method('PATCH')
                                        <button class="btn btn-sm btn-success"><i class="ti ti-restore me-1"></i>Pulihkan</button>
                                    </form>
                                    <form method="POST" action="{{ route('courses.forceDestroy', $course->id) }}"
                                          data-confirm="Hapus PERMANEN kelas &quot;{{ $course->name }}&quot; beserta semua pertemuan, materi, tugas, nilai, dan absensi? Tindakan ini TIDAK dapat dibatalkan.">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-ghost-danger" title="Hapus permanen" data-bs-toggle="tooltip" aria-label="Hapus permanen {{ $course->name }}"><i class="ti ti-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection
