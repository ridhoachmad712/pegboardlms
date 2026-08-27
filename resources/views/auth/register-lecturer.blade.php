@extends('layouts.guest')
@section('title', 'Daftar Dosen')

@section('content')
<nav class="auth-tabs" aria-label="Jenis pendaftaran">
    <a href="{{ route('register') }}">Mahasiswa</a>
    <a href="{{ route('register.lecturer') }}" aria-current="page">Dosen</a>
</nav>
<form class="card card-md" method="POST" action="{{ route('register.lecturer') }}">
    @csrf
    <div class="card-body">
        <h1 class="h2 mb-2">Daftar Akun Dosen</h1>
        <p class="text-secondary mb-3">Buat akun terlebih dahulu. Akses mengajar terbuka setelah pembayaran satu kali dikonfirmasi dan kode admin ditukar.</p>
        @if ($errors->any())
            <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
        @endif
        <div class="mb-3">
            <label for="lecturer-name" class="form-label required">Nama lengkap</label>
            <input id="lecturer-name" name="name" type="text" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" autocomplete="name" maxlength="255" required>
        </div>
        <div class="mb-3">
            <label for="lecturer-email" class="form-label required">Email</label>
            <input id="lecturer-email" name="email" type="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" autocomplete="email" maxlength="255" required>
        </div>
        <div class="mb-3">
            <label for="lecturer-institution" class="form-label required">Institusi / kampus</label>
            <input id="lecturer-institution" name="institution" type="text" class="form-control @error('institution') is-invalid @enderror" value="{{ old('institution') }}" autocomplete="organization" maxlength="255" required>
        </div>
        <div class="row g-2">
            <div class="col-sm-6 mb-3">
                <label for="lecturer-identity" class="form-label">NIDN / NIP <span class="text-secondary">(opsional)</span></label>
                <input id="lecturer-identity" name="nim_nip" type="text" class="form-control @error('nim_nip') is-invalid @enderror" value="{{ old('nim_nip') }}" maxlength="50">
            </div>
            <div class="col-sm-6 mb-3">
                <label for="lecturer-phone" class="form-label">No. HP <span class="text-secondary">(opsional)</span></label>
                <input id="lecturer-phone" name="phone" type="tel" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}" autocomplete="tel" maxlength="30">
            </div>
        </div>
        <div class="mb-3">
            <label for="lecturer-password" class="form-label required">Kata sandi</label>
            <input id="lecturer-password" name="password" type="password" class="form-control @error('password') is-invalid @enderror" autocomplete="new-password" minlength="8" maxlength="128" aria-describedby="lecturer-password-hint" required>
            <div id="lecturer-password-hint" class="form-hint">Minimal 8 karakter.</div>
        </div>
        <div class="mb-3">
            <label for="lecturer-password-confirmation" class="form-label required">Ulangi kata sandi</label>
            <input id="lecturer-password-confirmation" name="password_confirmation" type="password" class="form-control" autocomplete="new-password" minlength="8" maxlength="128" required>
        </div>
        <button class="btn btn-primary w-100" type="submit"><i class="ti ti-user-plus me-1" aria-hidden="true"></i>Buat Akun Dosen</button>
        <p class="text-secondary small mt-3 mb-0">Pendaftaran tidak langsung mengaktifkan akun. Pembayaran dan pemberian kode dilakukan melalui admin.</p>
    </div>
    <div class="card-footer text-center">Sudah punya akun? <a href="{{ route('login') }}">Masuk</a></div>
</form>
@endsection
