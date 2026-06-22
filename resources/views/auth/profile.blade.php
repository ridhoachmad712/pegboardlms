@extends('layouts.app')

@section('title', 'Profil')
@section('page-pretitle', 'Pengaturan')
@section('page-title', 'Profil & Kata Sandi')

@section('content')
<div class="row row-cards">
    {{-- Profil --}}
    <div class="col-md-6">
        <form class="card" method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
            @csrf
            @method('PATCH')
            <div class="card-header"><h3 class="card-title">Data Diri</h3></div>
            <div class="card-body">
                <div class="mb-3 d-flex align-items-center gap-3">
                    <span class="avatar avatar-xl rounded bg-primary-lt" @if ($user->avatarUrl()) style="background-image:url('{{ $user->avatarUrl() }}')" @endif>
                        @unless ($user->avatarUrl()){{ $user->initial() }}@endunless
                    </span>
                    <div class="flex-fill">
                        <label class="form-label">Foto profil</label>
                        <input type="file" name="avatar" class="form-control" accept="image/png,image/jpeg,image/webp">
                        <small class="form-hint">JPG/PNG/WebP, maks 2 MB.</small>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label required">Nama</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label required">Email</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ $user->isDosen() ? 'NIP' : 'NIM' }}</label>
                    <input type="text" class="form-control" value="{{ $user->nim_nip }}" disabled>
                    <small class="form-hint">Hubungi admin untuk mengubah {{ $user->isDosen() ? 'NIP' : 'NIM' }}.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">No. HP</label>
                    <input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}" placeholder="08xx">
                </div>
                <div class="mb-3">
                    <label class="form-check">
                        <input type="hidden" name="email_notifications" value="0">
                        <input type="checkbox" name="email_notifications" value="1" class="form-check-input" @checked($user->email_notifications)>
                        <span class="form-check-label">Terima notifikasi via email (pengumuman & pengingat tugas)</span>
                    </label>
                </div>
            </div>
            <div class="card-footer text-end">
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>

    {{-- Password --}}
    <div class="col-md-6">
        <form class="card" method="POST" action="{{ route('profile.password') }}">
            @csrf
            @method('PUT')
            <div class="card-header"><h3 class="card-title">Ubah Kata Sandi</h3></div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label required">Kata Sandi Saat Ini</label>
                    <input type="password" name="current_password" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label required">Kata Sandi Baru</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label required">Konfirmasi Kata Sandi Baru</label>
                    <input type="password" name="password_confirmation" class="form-control" required>
                </div>
            </div>
            <div class="card-footer text-end">
                <button type="submit" class="btn btn-primary">Ubah Kata Sandi</button>
            </div>
        </form>
    </div>
</div>
@endsection
