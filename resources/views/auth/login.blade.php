@extends('layouts.guest')

@section('title', 'Masuk')

@section('content')
<form class="card card-md" method="POST" action="{{ route('login') }}" autocomplete="off">
    @csrf
    <div class="card-body">
        <h2 class="h2 text-center mb-4">Masuk ke akun Anda</h2>

        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                <i class="ti ti-alert-triangle me-1"></i>{{ $errors->first() }}
            </div>
        @endif

        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" value="{{ old('email') }}"
                   class="form-control @error('email') is-invalid @enderror"
                   placeholder="nama@unm.ac.id" required autofocus>
        </div>

        <div class="mb-3">
            <label class="form-label">Kata Sandi</label>
            <div class="position-relative">
                <input type="password" name="password" id="password"
                       class="form-control @error('password') is-invalid @enderror"
                       placeholder="Kata sandi" required style="padding-right:2.75rem">
                <a href="#" tabindex="-1" title="Tampilkan / sembunyikan kata sandi"
                   class="position-absolute top-50 end-0 translate-middle-y me-2 link-secondary lh-1"
                   onclick="var i=document.getElementById('password'),e=document.getElementById('pwd-eye');if(i.type==='password'){i.type='text';e.className='ti ti-eye-off fs-2';}else{i.type='password';e.className='ti ti-eye fs-2';}return false;">
                    <i class="ti ti-eye fs-2" id="pwd-eye"></i>
                </a>
            </div>
        </div>

        <div class="form-footer">
            <button type="submit" class="btn btn-primary w-100">
                <i class="ti ti-login me-1"></i>Masuk
            </button>
        </div>
    </div>
</form>

@if (config('demo.enabled'))
    <div class="card card-md mt-3">
        <div class="card-body">
            <div class="text-center text-secondary mb-3">Atau coba langsung tanpa akun:</div>
            <div class="row g-2">
                <div class="col">
                    <form method="POST" action="{{ route('demo.login', 'dosen') }}">
                        @csrf
                        <button class="btn btn-outline-primary w-100"><i class="ti ti-school me-1"></i>Coba sebagai Dosen</button>
                    </form>
                </div>
                <div class="col">
                    <form method="POST" action="{{ route('demo.login', 'mahasiswa') }}">
                        @csrf
                        <button class="btn btn-outline-primary w-100"><i class="ti ti-user me-1"></i>Coba sebagai Mahasiswa</button>
                    </form>
                </div>
            </div>
            <div class="text-center mt-3"><small class="text-secondary"><i class="ti ti-flask me-1"></i>Mode demo — data contoh, direset berkala.</small></div>
        </div>
    </div>
@endif
@endsection
