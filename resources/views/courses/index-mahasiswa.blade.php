@extends('layouts.app')

@section('title', 'Kelas Saya')
@section('page-pretitle', 'Perkuliahan')
@section('page-title', 'Kelas Saya')

@section('page-actions')
    <a href="{{ route('enrollments.join.show') }}" class="btn btn-primary"><i class="ti ti-key me-1"></i>Gabung Kelas</a>
@endsection

@section('content')
@if ($courses->isEmpty())
    <div class="card">
        <div class="card-body">
            <x-empty-state icon="ti-school" title="Belum terdaftar di kelas mana pun"
                description="Punya kode dari dosen? Klik Gabung Kelas. Atau tunggu dosen menambahkan Anda.">
                <a href="{{ route('enrollments.join.show') }}" class="btn btn-primary"><i class="ti ti-key me-1"></i>Gabung Kelas</a>
            </x-empty-state>
        </div>
    </div>
@else
    <div class="row row-cards">
        @foreach ($courses as $course)
            <div class="col-md-6 col-lg-4">
                <div class="card card-lift overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-1">
                            <span class="avatar bg-{{ $course->color() }}-lt me-2"><i class="ti ti-school"></i></span>
                            <h3 class="card-title mb-0">{{ $course->name }}</h3>
                        </div>
                        <div class="text-secondary mb-2">{{ $course->code }}@if ($course->class_name) · {{ $course->class_name }}@endif · {{ $course->semester }} {{ $course->year }}</div>
                        <div class="d-flex align-items-center text-secondary small">
                            <i class="ti ti-user me-1"></i>{{ $course->lecturer->name }}
                            <span class="ms-auto"><i class="ti ti-calendar me-1"></i>{{ $course->meetings_count }} pertemuan</span>
                        </div>
                    </div>
                    <div class="card-footer">
                        <a href="{{ route('courses.show', $course) }}" class="btn w-100">
                            <i class="ti ti-folder-open me-1"></i>Buka Kelas
                        </a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
@endsection
