@extends('layouts.app')

@section('title', 'Buat Kelas')
@section('page-pretitle', 'Administrasi')
@section('page-title', 'Buat Kelas Baru')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <form class="card" method="POST" data-warn-unsaved action="{{ route('courses.store') }}">
            @csrf
            <div class="card-body">
                @include('courses._form-fields')
            </div>
            <div class="card-footer text-end">
                <a href="{{ route('courses.index') }}" class="btn btn-link">Batal</a>
                <button type="submit" class="btn btn-primary"><i class="ti ti-check me-1"></i>Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection
