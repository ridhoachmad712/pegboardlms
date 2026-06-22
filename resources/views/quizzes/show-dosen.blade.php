@extends('layouts.app')

@section('title', $assignment->title)
@section('page-pretitle', $assignment->course->name . ' · Kuis')
@section('page-title', $assignment->title)

@section('page-actions')
    <div class="btn-list">
        <a href="{{ route('quizzes.questions', $assignment) }}" class="btn btn-primary"><i class="ti ti-list-check me-1"></i>Kelola Soal</a>
        <a href="{{ route('assignments.edit', $assignment) }}" class="btn"><i class="ti ti-edit me-1"></i>Edit</a>
        <form method="POST" action="{{ route('assignments.destroy', $assignment) }}" data-confirm="Hapus kuis ini?">
            @csrf @method('DELETE')
            <button class="btn btn-danger"><i class="ti ti-trash"></i></button>
        </form>
    </div>
@endsection

@section('content')
<div class="row row-cards">
    <div class="col-md-4">
        <div class="card"><div class="card-body">
            <div class="row text-center">
                <div class="col"><div class="h2 mb-0">{{ $assignment->questions_count }}</div><div class="text-secondary small">Soal</div></div>
                <div class="col"><div class="h2 mb-0">{{ $assignment->duration_minutes ?? '∞' }}</div><div class="text-secondary small">Menit</div></div>
                <div class="col"><div class="h2 mb-0">{{ $assignment->submissions_count }}</div><div class="text-secondary small">Peserta</div></div>
            </div>
            @if ($assignment->deadline)<hr><div class="text-secondary small"><i class="ti ti-clock"></i> Deadline {{ $assignment->deadline->translatedFormat('d M Y H:i') }}</div>@endif
        </div></div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Hasil Peserta</h3></div>
            @if ($submissions->isEmpty())
                <div class="card-body"><x-empty-state icon="ti-users" title="Belum ada yang mengerjakan" /></div>
            @else
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead><tr><th>Mahasiswa</th><th>Status</th><th>Nilai</th><th></th></tr></thead>
                        <tbody>
                            @foreach ($submissions as $sub)
                                <tr>
                                    <td>{{ $sub->student->name }}</td>
                                    <td>
                                        @if ($sub->submitted_at)
                                            <span class="badge bg-green-lt">Selesai</span>
                                        @else
                                            <span class="badge bg-yellow-lt">Sedang mengerjakan</span>
                                        @endif
                                    </td>
                                    <td>{!! is_null($sub->score) ? '<span class="text-secondary">Menunggu nilai esai</span>' : '<span class="fw-bold">'.rtrim(rtrim($sub->score,'0'),'.').'</span>' !!}</td>
                                    <td class="text-end">
                                        @if ($sub->submitted_at)
                                            <a href="{{ route('quizzes.review', $sub) }}" class="btn btn-sm">Periksa</a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
