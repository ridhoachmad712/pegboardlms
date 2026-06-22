@extends('layouts.guest')

@section('title', 'Masuk')

@section('content')
@if (config('demo.enabled'))
    {{-- ============ MODE DEMO: hanya pilihan peran (tanpa login) ============ --}}
    <style>
        .demo-choice{transition:transform .15s ease, box-shadow .15s ease; border-radius:1rem;}
        .demo-choice:hover{transform:translateY(-3px); box-shadow:0 .75rem 1.5rem rgba(0,0,0,.18);}
        .demo-choice:active{transform:translateY(-1px);}
        .demo-choice .demo-ico{width:54px; height:54px; border-radius:.85rem; background:rgba(255,255,255,.2);}
        .demo-choice .demo-ico i{font-size:1.75rem; line-height:1;}
        .demo-choice .demo-arrow{font-size:1.5rem; opacity:.7; transition:transform .15s ease;}
        .demo-choice:hover .demo-arrow{transform:translateX(4px); opacity:1;}
    </style>

    <div class="card card-md">
        <div class="card-body p-4">
            <h2 class="h2 text-center mb-1">Coba Aplikasi</h2>
            <p class="text-center text-secondary mb-4">Masuk langsung tanpa akun — pilih peran Anda:</p>

            <div class="d-grid gap-3">
                <form method="POST" action="{{ route('demo.login', 'dosen') }}">
                    @csrf
                    <button type="submit"
                            class="btn btn-primary w-100 border-0 text-white demo-choice d-flex align-items-center text-start px-3 py-3">
                        <span class="demo-ico d-inline-flex align-items-center justify-content-center flex-shrink-0">
                            <i class="ti ti-school"></i>
                        </span>
                        <span class="ms-3 flex-fill">
                            <span class="d-block fw-bold fs-2 lh-1">Dosen</span>
                            <span class="d-block small opacity-75 mt-1">Kelola kelas, materi, tugas &amp; nilai</span>
                        </span>
                        <i class="ti ti-chevron-right demo-arrow flex-shrink-0"></i>
                    </button>
                </form>

                <form method="POST" action="{{ route('demo.login', 'mahasiswa') }}">
                    @csrf
                    <button type="submit"
                            class="btn btn-azure w-100 border-0 text-white demo-choice d-flex align-items-center text-start px-3 py-3">
                        <span class="demo-ico d-inline-flex align-items-center justify-content-center flex-shrink-0">
                            <i class="ti ti-user"></i>
                        </span>
                        <span class="ms-3 flex-fill">
                            <span class="d-block fw-bold fs-2 lh-1">Mahasiswa</span>
                            <span class="d-block small opacity-75 mt-1">Lihat materi, kumpul tugas &amp; kuis</span>
                        </span>
                        <i class="ti ti-chevron-right demo-arrow flex-shrink-0"></i>
                    </button>
                </form>
            </div>

            <p class="text-center text-secondary small mt-4 mb-0">
                <i class="ti ti-flask me-1"></i>Mode demo — data contoh, direset berkala.
            </p>
        </div>
    </div>
@else
    {{-- ============ LOGIN NORMAL ============ --}}
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
@endif
@endsection
