@extends('layouts.guest')

@section('title', 'Daftar')

@section('content')
<nav class="auth-tabs" aria-label="Jenis pendaftaran">
    <a href="{{ route('register') }}" aria-current="page">Mahasiswa</a>
    <a href="{{ route('register.lecturer') }}">Dosen</a>
</nav>
<form class="card card-md" method="POST" action="{{ route('register') }}" autocomplete="off">
    @csrf
    <div class="card-body">
        <h2 class="h2 text-center mb-1">Daftar Akun Mahasiswa</h2>
        <p class="text-secondary text-center mb-4">
            Masukkan <strong>kode kelas</strong> dari dosen Anda untuk membuat akun dan langsung tergabung ke kelas.
        </p>

        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                <i class="ti ti-alert-triangle me-1"></i>{{ $errors->first() }}
            </div>
        @endif

        <div class="mb-3">
            <label class="form-label required">Kode Kelas</label>
            <input type="text" name="join_code" value="{{ old('join_code') }}"
                   class="form-control text-uppercase @error('join_code') is-invalid @enderror"
                   placeholder="mis. X7K9PQ" maxlength="12" required autofocus>
        </div>

        <div class="mb-3">
            <label class="form-label required">Nama Lengkap</label>
            <input type="text" name="name" value="{{ old('name') }}"
                   class="form-control @error('name') is-invalid @enderror"
                   placeholder="Nama sesuai data kampus" required>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label required">NIM</label>
                <input type="text" name="nim_nip" value="{{ old('nim_nip') }}"
                       class="form-control @error('nim_nip') is-invalid @enderror"
                       placeholder="Nomor Induk Mahasiswa" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label required">Email</label>
                <input type="email" name="email" value="{{ old('email') }}"
                       class="form-control @error('email') is-invalid @enderror"
                       placeholder="nama@email.com" required>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label required">Kata Sandi</label>
                <input type="password" name="password"
                       class="form-control @error('password') is-invalid @enderror"
                       placeholder="Minimal 6 karakter" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label required">Ulangi Kata Sandi</label>
                <input type="password" name="password_confirmation"
                       class="form-control" placeholder="Ketik ulang kata sandi" required>
            </div>
        </div>

        <div class="form-footer">
            <button type="submit" class="btn btn-primary w-100">
                <i class="ti ti-user-plus me-1"></i>Daftar &amp; Gabung Kelas
            </button>
        </div>
    </div>
    <div class="card-footer text-center text-secondary">
        Sudah punya akun? <a href="{{ route('login') }}">Masuk di sini</a>
    </div>
</form>
@endsection
