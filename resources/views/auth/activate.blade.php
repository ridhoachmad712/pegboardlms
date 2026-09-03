@extends('layouts.guest')
@section('title', 'Aktivasi Akun Dosen')

@section('content')
<div class="card card-md">
    <div class="card-body">
        <span class="avatar bg-orange-lt mb-3"><i class="ti ti-clock-hour-4" aria-hidden="true"></i></span>
        <div class="mb-2"><span class="badge bg-orange-lt">Menunggu aktivasi</span></div>
        <h1 class="h2 mb-2">Akun menunggu persetujuan admin</h1>
        <p class="text-secondary">Pendaftaran Anda sudah kami terima. Admin akan memeriksa dan mengaktifkan akun Anda. Setelah aktif, Anda bisa langsung masuk — <strong>tanpa kode aktivasi</strong>.</p>
        <div class="border rounded p-3 mb-3">
            <div class="fw-bold">{{ $user->name }}</div>
            <div class="small text-secondary">{{ $user->email }}</div>
            @if ($user->institution)<div class="small text-secondary">{{ $user->institution }}</div>@endif
        </div>
        @if (session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif
        <a href="{{ route('dashboard') }}" class="btn btn-primary w-100"><i class="ti ti-refresh me-1" aria-hidden="true"></i>Periksa Status</a>
        @if (config('licensing.support_email'))
            <a class="btn btn-outline-primary w-100 mt-2" href="mailto:{{ config('licensing.support_email') }}">Hubungi Admin</a>
        @endif
        <p class="small text-secondary mt-3 mb-0">Sudah menghubungi admin tetapi belum aktif? Tunggu beberapa saat lalu tekan Periksa Status. Jangan membuat akun baru.</p>
    </div>
    <div class="card-footer">
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn w-100" type="submit">Keluar / ganti akun</button></form>
    </div>
</div>
@endsection
