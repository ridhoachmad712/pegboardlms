@extends('layouts.app')

@section('title', $assignment->title)
@section('page-pretitle', $assignment->course->name . ' · Kuis eksternal')
@section('page-title', $assignment->title)

@section('page-actions')
    <div class="btn-list">
        <a href="{{ $assignment->external_url }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline-purple">
            <i class="ti ti-external-link me-1"></i>Buka Kuis
        </a>
        <a href="{{ route('assignments.edit', $assignment) }}" class="btn"><i class="ti ti-edit me-1"></i>Edit</a>
        <form method="POST" action="{{ route('assignments.destroy', $assignment) }}" data-confirm="Hapus kuis eksternal ini beserta nilai yang sudah diinput?">
            @csrf @method('DELETE')
            <button class="btn btn-danger" aria-label="Hapus kuis"><i class="ti ti-trash"></i></button>
        </form>
    </div>
@endsection

@section('content')
<div class="row row-cards">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <span class="badge bg-purple-lt mb-3"><i class="ti ti-external-link me-1"></i>Kuis eksternal</span>
                @if ($assignment->description)<div class="mb-3" style="white-space:pre-line">{{ $assignment->description }}</div>@endif
                <dl class="row mb-0 small">
                    <dt class="col-5 text-secondary">Pertemuan</dt><dd class="col-7">{{ $assignment->meeting?->number }} · {{ $assignment->meeting?->topic }}</dd>
                    <dt class="col-5 text-secondary">Nilai maksimal</dt><dd class="col-7">{{ $assignment->max_score }}</dd>
                    <dt class="col-5 text-secondary">Deadline</dt><dd class="col-7">{{ $assignment->deadline?->translatedFormat('d M Y H:i') ?? 'Tidak ada' }}</dd>
                    <dt class="col-5 text-secondary">Komponen</dt><dd class="col-7">{{ $assignment->gradeComponent?->name ?? 'Belum dikaitkan' }}</dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <form class="card" method="POST" action="{{ route('quizzes.externalScores', $assignment) }}" data-warn-unsaved>
            @csrf
            <div class="card-header">
                <div>
                    <h3 class="card-title">Nilai Peserta</h3>
                    <div class="text-secondary small">Masukkan hasil dari platform eksternal. Kosongkan nilai untuk menghapus hasil.</div>
                </div>
                <div class="ms-auto text-secondary small">{{ $submissions->count() }}/{{ $assignment->course->students->count() }} dinilai</div>
            </div>
            @if ($assignment->course->students->isEmpty())
                <div class="card-body"><x-empty-state icon="ti-users" title="Belum ada mahasiswa" description="Tambahkan mahasiswa ke kelas sebelum menginput nilai." /></div>
            @else
                <div class="card-body py-2 border-bottom">
                    <input type="text" class="form-control form-control-sm" style="max-width:240px" placeholder="Cari mahasiswa…" data-table-search="#tbl-external-scores">
                </div>
                <div class="table-responsive">
                    <table id="tbl-external-scores" class="table table-vcenter card-table">
                        <thead><tr><th>Mahasiswa</th><th style="width:170px" class="text-center">Nilai / {{ $assignment->max_score }}</th></tr></thead>
                        <tbody>
                            @foreach ($assignment->course->students as $student)
                                @php($submission = $submissions->get($student->id))
                                <tr>
                                    <td>{{ $student->name }}<div class="small text-secondary">{{ $student->nim_nip }}</div></td>
                                    <td>
                                        <input type="number" name="scores[{{ $student->id }}]"
                                               value="{{ old('scores.'.$student->id, is_null($submission?->score) ? '' : \App\Support\Grades::num($submission->score)) }}"
                                               min="0" max="{{ $assignment->max_score }}" step="0.01"
                                               class="form-control form-control-sm text-center @error('scores.'.$student->id) is-invalid @enderror" placeholder="—"
                                               @disabled($assignment->course->isCompleted())>
                                        @error('scores.'.$student->id)<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @unless ($assignment->course->isCompleted())
                    <div class="card-footer text-end">
                        <button class="btn btn-primary" data-loading="Menyimpan…"><i class="ti ti-device-floppy me-1"></i>Simpan Nilai</button>
                    </div>
                @endunless
            @endif
        </form>
    </div>
</div>
@endsection
