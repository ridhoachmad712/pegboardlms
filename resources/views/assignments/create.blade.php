@extends('layouts.app')

@section('title', 'Buat ' . ($type === 'kuis' ? 'Kuis' : 'Tugas'))
@section('page-pretitle', $course->name)
@section('page-title', 'Buat ' . ($type === 'kuis' ? 'Kuis' : 'Tugas'))

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <form class="card" method="POST" data-warn-unsaved action="{{ route('assignments.store', $course) }}">
            @csrf
            <div class="card-body">
                @include('assignments._form-fields')
            </div>
            <div class="card-footer text-end">
                <a href="{{ route('courses.show', $course) }}" class="btn btn-link">Batal</a>
                <button class="btn btn-primary"><i class="ti ti-check me-1"></i>{{ $type === 'kuis' ? 'Lanjut: Tambah Soal' : 'Simpan' }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
