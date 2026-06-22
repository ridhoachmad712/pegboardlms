@extends('layouts.app')

@section('title', 'Edit')
@section('page-pretitle', $course->name)
@section('page-title', 'Edit ' . ($type === 'kuis' ? 'Kuis' : 'Tugas'))

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <form class="card" method="POST" data-warn-unsaved action="{{ route('assignments.update', $assignment) }}">
            @csrf @method('PUT')
            <div class="card-body">
                @include('assignments._form-fields')
            </div>
            <div class="card-footer text-end">
                <a href="{{ route('assignments.show', $assignment) }}" class="btn btn-link">Batal</a>
                <button class="btn btn-primary"><i class="ti ti-check me-1"></i>Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>
@endsection
