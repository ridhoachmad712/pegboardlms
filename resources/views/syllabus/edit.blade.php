@extends('layouts.app')

@section('title', 'Edit RPS')
@section('page-pretitle', $course->name)
@section('page-title', 'Edit Silabus / RPS')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-9">
        <form class="card" method="POST" data-warn-unsaved action="{{ route('syllabus.update', $course) }}">
            @csrf @method('PUT')
            <div class="card-body">
                {{-- Bernomor: tiap baris jadi satu item bernomor --}}
                <x-numbered-list name="cpl" label="CPL — Capaian Pembelajaran Lulusan"
                    :value="old('cpl', $syllabus->cpl ?? '')" placeholder="Satu CPL per baris" />
                <x-numbered-list name="cpmk" label="CPMK — Capaian Pembelajaran Mata Kuliah"
                    :value="old('cpmk', $syllabus->cpmk ?? '')" placeholder="Satu CPMK per baris" />
                <x-numbered-list name="sub_cpmk" label="Sub-CPMK"
                    :value="old('sub_cpmk', $syllabus->sub_cpmk ?? '')" placeholder="Satu Sub-CPMK per baris" />

                @foreach ([
                    ['description', 'Deskripsi Mata Kuliah', 'Gambaran umum mata kuliah'],
                ] as [$field, $label, $hint])
                    <div class="mb-3">
                        <label class="form-label">{{ $label }}</label>
                        <textarea name="{{ $field }}" class="form-control" rows="4" placeholder="{{ $hint }}">{{ old($field, $syllabus->$field ?? '') }}</textarea>
                    </div>
                @endforeach

                <x-numbered-list name="references" label="Referensi / Pustaka"
                    :value="old('references', $syllabus->references ?? '')" placeholder="Satu referensi per baris (buku, jurnal, dll.)" />

                @foreach ([
                    ['assessment', 'Metode Penilaian', 'Komponen & bobot penilaian'],
                    ['rules', 'Aturan Kelas', 'Tata tertib, kehadiran minimum, dll.'],
                ] as [$field, $label, $hint])
                    <div class="mb-3">
                        <label class="form-label">{{ $label }}</label>
                        <textarea name="{{ $field }}" class="form-control" rows="4" placeholder="{{ $hint }}">{{ old($field, $syllabus->$field ?? '') }}</textarea>
                    </div>
                @endforeach
            </div>
            <div class="card-footer text-end">
                <a href="{{ route('syllabus.show', $course) }}" class="btn btn-link">Batal</a>
                <button class="btn btn-primary"><i class="ti ti-check me-1"></i>Simpan RPS</button>
            </div>
        </form>
    </div>
</div>
@endsection
