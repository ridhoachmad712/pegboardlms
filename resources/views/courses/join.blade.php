@extends('layouts.app')

@section('title', 'Gabung Kelas')
@section('page-pretitle', 'Perkuliahan')
@section('page-title', 'Gabung Kelas')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <form class="card" method="POST" action="{{ route('enrollments.join') }}">
            @csrf
            <div class="card-body text-center">
                <span class="avatar avatar-lg bg-primary-lt mb-3"><i class="ti ti-key fs-1"></i></span>
                <h3 class="mb-1">Masukkan kode kelas</h3>
                <p class="text-secondary">Minta kode 6 karakter dari dosen pengampu.</p>
                <input type="text" name="join_code" class="form-control form-control-lg text-center text-uppercase fw-bold @error('join_code') is-invalid @enderror"
                       style="letter-spacing:.3em" maxlength="6" placeholder="XXXXXX" value="{{ old('join_code') }}" autofocus required>
                @error('join_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="card-footer text-end">
                <a href="{{ route('courses.index') }}" class="btn btn-link">Batal</a>
                <button class="btn btn-primary"><i class="ti ti-login me-1"></i>Gabung</button>
            </div>
        </form>
    </div>
</div>
@endsection
