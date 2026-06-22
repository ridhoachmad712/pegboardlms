@extends('layouts.app')

@section('title', 'Edit Mahasiswa')
@section('page-pretitle', 'Admin')
@section('page-title', 'Edit Mahasiswa')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.students.index') }}">Mahasiswa</a></li>
    <li class="breadcrumb-item active">{{ $student->name }}</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <form class="card" method="POST" data-warn-unsaved action="{{ route('admin.students.update', $student) }}">
            @csrf @method('PUT')
            <div class="card-body">
                <div class="mb-3"><label class="form-label required">Nama</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $student->name) }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3"><label class="form-label required">Email</label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $student->email) }}" required>
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3"><label class="form-label">NIM</label>
                        <input type="text" name="nim_nip" class="form-control @error('nim_nip') is-invalid @enderror" value="{{ old('nim_nip', $student->nim_nip) }}">
                        @error('nim_nip')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 mb-3"><label class="form-label">No. HP</label>
                        <input type="text" name="phone" class="form-control" value="{{ old('phone', $student->phone) }}">
                    </div>
                </div>
                <div class="text-secondary small">Untuk mengubah kata sandi, gunakan tombol <i class="ti ti-key"></i> Reset di daftar mahasiswa.</div>
            </div>
            <div class="card-footer text-end">
                <a href="{{ route('admin.students.index') }}" class="btn btn-link">Batal</a>
                <button class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>
@endsection
