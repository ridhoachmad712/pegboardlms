@extends('layouts.guest')
@section('title', 'Aktivasi Akun Dosen')

@section('content')
<div class="card card-md">
    <div class="card-body">
        <span class="avatar bg-primary-lt mb-3"><i class="ti ti-lock" aria-hidden="true"></i></span>
        <div class="mb-2"><span class="badge bg-orange-lt">Menunggu aktivasi</span></div>
        <h1 class="h2 mb-2">Aktifkan akun dosen</h1>
        <p class="text-secondary">Sekali bayar, akses tanpa masa kedaluwarsa. Hubungi admin untuk konfirmasi pembayaran dan kode aktivasi Anda.</p>
        <div class="border rounded p-3 mb-3">
            <div class="fw-bold">{{ $user->name }}</div>
            <div class="small text-secondary">{{ $user->email }}</div>
            @if ($user->institution)<div class="small text-secondary">{{ $user->institution }}</div>@endif
        </div>
        @if (session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif
        @if ($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif
        <form method="POST" action="{{ route('activation.store') }}" autocomplete="off">
            @csrf
            <label for="activation-code" class="form-label required">Kode aktivasi</label>
            <input id="activation-code" name="activation_code" type="text" class="form-control activation-code-input @error('activation_code') is-invalid @enderror" placeholder="20 karakter huruf dan angka" maxlength="30" spellcheck="false" autocapitalize="characters" autocomplete="off" aria-describedby="activation-hint" required @error('activation_code') aria-invalid="true" @enderror>
            <div class="form-hint mb-3" id="activation-hint">Kode khusus akun ini, hanya sekali pakai. Ini bukan kode gabung kelas mahasiswa.</div>
            <button type="submit" class="btn btn-primary w-100"><i class="ti ti-key me-1" aria-hidden="true"></i>Aktifkan Akun</button>
        </form>
        @if (config('licensing.support_email'))
            <a class="btn btn-outline-primary w-100 mt-2" href="mailto:{{ config('licensing.support_email') }}">Hubungi Admin</a>
        @endif
        <p class="small text-secondary mt-3 mb-0">Belum menerima kode atau kode sudah tidak berlaku? Minta admin memeriksa akun dan menerbitkan kode baru. Jangan membuat akun baru.</p>
    </div>
    <div class="card-footer">
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn w-100" type="submit">Keluar / ganti akun</button></form>
    </div>
</div>
@endsection
