@extends('layouts.app')

@section('title', 'Nilai Saya')

@section('content')
@include('courses._hero')

<div class="row justify-content-center">
    <div class="col-lg-8">
        @if ($components->isEmpty())
            <div class="card"><div class="card-body"><x-empty-state icon="ti-clipboard-off" title="Belum ada komponen nilai" description="Dosen belum mengatur komponen penilaian." /></div></div>
        @else
            @php($gradedCount = $components->filter(fn($c) => ! is_null($row['components'][$c->id] ?? null))->count())
            @php($allGraded = $gradedCount === $components->count())
            <div class="card mb-3">
                <div class="card-body">
                    <div class="text-secondary small">{{ $allGraded ? 'Nilai akhir' : 'Progres penilaian' }}</div>
                    <div class="d-flex align-items-end gap-3 mt-1">
                        <div class="h1 display-6 mb-0">{{ \App\Support\Grades::num($row['final'] ?? 0) }}</div>
                        @if($allGraded)
                            @php($letter = $row['letter'] ?? '-')
                            <span class="badge bg-{{ \App\Support\Grades::color($letter) }}-lt fs-3 mb-2">{{ $letter }}</span>
                        @else
                            <span class="text-secondary mb-2">sementara</span>
                        @endif
                    </div>
                    <div class="progress progress-sm mt-3"><div class="progress-bar" style="width:{{ $components->count() ? ($gradedCount / $components->count() * 100) : 0 }}%"></div></div>
                    <div class="small text-secondary mt-1">{{ $gradedCount }} dari {{ $components->count() }} komponen sudah dinilai</div>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h3 class="card-title">Rincian per Komponen</h3></div>
                <div class="list-group list-group-flush d-md-none">
                    @foreach ($components as $c)
                        @php($score = $row['components'][$c->id] ?? null)
                        <div class="list-group-item d-flex align-items-center gap-3">
                            <span class="avatar avatar-sm bg-primary-lt"><i class="ti ti-clipboard-check"></i></span>
                            <div class="min-w-0 flex-fill"><div class="fw-semibold line-clamp-2">{{ $c->name }}</div><div class="small text-secondary">Bobot {{ $c->weight }}%</div></div>
                            <div class="text-end"><div class="fw-bold fs-3">{{ is_null($score) ? '—' : \App\Support\Grades::num($score) }}</div><div class="small text-secondary">{{ is_null($score) ? 'Belum dinilai' : 'Kontribusi '.round($score * $c->weight / 100, 2) }}</div></div>
                        </div>
                    @endforeach
                </div>
                <div class="table-responsive d-none d-md-block">
                    <table class="table table-vcenter card-table">
                        <thead><tr><th>Komponen</th><th class="text-center">Bobot</th><th class="text-center">Nilai</th><th class="text-center">Kontribusi</th></tr></thead>
                        <tbody>
                            @foreach ($components as $c)
                                @php($score = $row['components'][$c->id] ?? null)
                                <tr>
                                    <td>{{ $c->name }}<div class="small text-secondary">{{ \App\Models\GradeComponent::TYPES[$c->type] ?? ucfirst($c->type) }}</div></td>
                                    <td class="text-center">{{ $c->weight }}%</td>
                                    <td class="text-center">{{ is_null($score) ? '—' : \App\Support\Grades::num($score) }}</td>
                                    <td class="text-center text-secondary">{{ is_null($score) ? '—' : round($score * $c->weight / 100, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
