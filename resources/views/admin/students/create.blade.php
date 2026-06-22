@extends('layouts.app')

@section('title', 'Tambah Mahasiswa')
@section('page-pretitle', 'Admin')
@section('page-title', 'Tambah Mahasiswa')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.students.index') }}">Mahasiswa</a></li>
    <li class="breadcrumb-item active">Tambah</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <form class="card" method="POST" data-warn-unsaved action="{{ route('admin.students.store') }}">
            @csrf
            <div class="card-body">
                <div class="mb-3"><label class="form-label required">Nama</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3"><label class="form-label required">Email</label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required>
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3"><label class="form-label">NIM</label>
                        <input type="text" name="nim_nip" class="form-control @error('nim_nip') is-invalid @enderror" value="{{ old('nim_nip') }}">
                        @error('nim_nip')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 mb-3"><label class="form-label">No. HP</label>
                        <input type="text" name="phone" class="form-control" value="{{ old('phone') }}">
                    </div>
                </div>
                <div class="mb-1"><label class="form-label">Kata Sandi</label>
                    <input type="text" name="password" class="form-control" placeholder="Kosongkan = pakai NIM (atau 'password')">
                    <small class="form-hint">Mahasiswa bisa menggantinya nanti di profil.</small>
                </div>
            </div>
            <div class="card-footer text-end">
                <a href="{{ route('admin.students.index') }}" class="btn btn-link">Batal</a>
                <button class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection
