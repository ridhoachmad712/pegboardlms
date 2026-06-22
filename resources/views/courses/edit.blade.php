@extends('layouts.app')

@section('title', 'Edit Kelas')
@section('page-pretitle', 'Administrasi')
@section('page-title', 'Edit Kelas')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <form class="card" method="POST" data-warn-unsaved action="{{ route('courses.update', $course) }}">
            @csrf
            @method('PUT')
            <div class="card-body">
                @include('courses._form-fields')
            </div>
            <div class="card-footer d-flex">
                <a href="{{ route('courses.show', $course) }}" class="btn btn-link">Batal</a>
                <button type="submit" class="btn btn-primary ms-auto"><i class="ti ti-check me-1"></i>Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>
@endsection
