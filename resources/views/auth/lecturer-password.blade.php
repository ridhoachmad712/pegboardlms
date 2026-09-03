@extends('layouts.guest')
@section('content')
<div class="card card-md"><div class="card-body">
    <h1 class="h2 mb-3">Ganti kata sandi sementara</h1>
    <p class="text-secondary">Admin telah mereset kata sandi Anda. Buat kata sandi pribadi sebelum melanjutkan ke aplikasi.</p>
    <form method="POST" action="{{ route('lecturer.password.update') }}">
        @csrf @method('PUT')
        <div class="mb-3">
            <label for="current-password" class="form-label">Kata sandi sementara dari admin</label>
            <input id="current-password" name="current_password" type="password" class="form-control @error('current_password') is-invalid @enderror" autocomplete="current-password" required autofocus @error('current_password') aria-describedby="current-password-error" aria-invalid="true" @enderror>
            @error('current_password')<div id="current-password-error" class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label for="new-password" class="form-label">Kata sandi baru</label>
            <input id="new-password" name="password" type="password" class="form-control @error('password') is-invalid @enderror" autocomplete="new-password" required minlength="12" maxlength="72" aria-describedby="password-help @error('password') password-error @enderror" @error('password') aria-invalid="true" @enderror>
            <div id="password-help" class="form-hint">Minimal 12 karakter. Gunakan kata sandi yang berbeda dari kata sandi sementara.</div>
            @error('password')<div id="password-error" class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label for="password-confirmation" class="form-label">Ulangi kata sandi baru</label>
            <input id="password-confirmation" name="password_confirmation" type="password" class="form-control" autocomplete="new-password" required minlength="12" maxlength="72">
        </div>
        <button type="submit" class="btn btn-primary w-100">Simpan Kata Sandi Baru</button>
    </form>
    <form method="POST" action="{{ route('logout') }}" class="mt-3">
        @csrf
        <button type="submit" class="btn w-100">Keluar</button>
    </form>
</div></div>
@endsection
