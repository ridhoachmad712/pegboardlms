@extends('layouts.app')

@section('title', 'Nilai Saya')

@section('content')
@include('courses._hero')

<div class="row justify-content-center">
    <div class="col-lg-8">
        @if ($components->isEmpty())
            <div class="card"><div class="card-body"><x-empty-state icon="ti-clipboard-off" title="Belum ada komponen nilai" description="Dosen belum mengatur komponen penilaian." /></div></div>
        @else
            <div class="card mb-3">
                <div class="card-body text-center">
                    <div class="text-secondary">Nilai Akhir Sementara</div>
                    <div class="h1 display-6 mb-0">{{ $row['final'] ?? 0 }}</div>
                    @php($letter = $row['letter'] ?? '-')
                    <span class="badge bg-{{ \App\Support\Grades::color($letter) }}-lt fs-3">{{ $letter }}</span>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h3 class="card-title">Rincian per Komponen</h3></div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead><tr><th>Komponen</th><th class="text-center">Bobot</th><th class="text-center">Nilai</th><th class="text-center">Kontribusi</th></tr></thead>
                        <tbody>
                            @foreach ($components as $c)
                                @php($score = $row['components'][$c->id] ?? null)
                                <tr>
                                    <td>{{ $c->name }}<div class="small text-secondary text-capitalize">{{ $c->type }}</div></td>
                                    <td class="text-center">{{ $c->weight }}%</td>
                                    <td class="text-center">{{ is_null($score) ? '—' : rtrim(rtrim($score, '0'), '.') }}</td>
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
