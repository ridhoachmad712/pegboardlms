@extends('layouts.app')

@section('title', 'Analitik')

@section('content')
@include('courses._hero')

{{-- Stat summary --}}
<div class="row row-cards mb-3">
    @foreach ([
        ['Rata-rata', $summary['avg'], 'azure'],
        ['Median', $summary['median'], 'blue'],
        ['Tertinggi', $summary['max'], 'green'],
        ['Terendah', $summary['min'], 'orange'],
        ['Lulus (≥60)', $summary['pass'].' / '.$summary['count'], 'teal'],
    ] as [$label, $value, $color])
        <div class="col-6 col-md">
            <div class="card card-sm"><div class="card-body text-center">
                <div class="h2 mb-0 text-{{ $color }}">{{ $value }}</div>
                <div class="text-secondary small">{{ $label }}</div>
            </div></div>
        </div>
    @endforeach
</div>

<div class="row row-cards">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Distribusi Nilai Akhir</h3></div>
            <div class="card-body">
                <div style="position:relative;height:220px">
                    <div class="skeleton" data-skeleton-for="chartDistribution" style="position:absolute;inset:0"></div>
                    <canvas id="chartDistribution" height="220"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Tren Kehadiran per Pertemuan</h3></div>
            <div class="card-body">
                <div style="position:relative;height:220px">
                    <div class="skeleton" data-skeleton-for="chartAttendance" style="position:absolute;inset:0"></div>
                    <canvas id="chartAttendance" height="220"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Mahasiswa Berisiko</h3>
                <span class="ms-auto badge bg-red-lt">{{ $risk->count() }} mahasiswa</span>
            </div>
            @if ($risk->isEmpty())
                <div class="card-body"><x-empty-state icon="ti-mood-happy" title="Tidak ada mahasiswa berisiko" description="Semua mahasiswa di atas ambang nilai & kehadiran." /></div>
            @else
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead><tr><th>Mahasiswa</th><th class="text-center">Nilai Akhir</th><th class="text-center">Huruf</th><th class="text-center">Kehadiran</th><th>Alasan</th></tr></thead>
                        <tbody>
                            @foreach ($risk as $r)
                                <tr>
                                    <td>{{ $r['student']->name }}<div class="small text-secondary">{{ $r['student']->nim_nip }}</div></td>
                                    <td class="text-center {{ $r['final'] < 60 ? 'text-red fw-bold' : '' }}">{{ $r['final'] }}</td>
                                    <td class="text-center">{{ $r['letter'] }}</td>
                                    <td class="text-center {{ ! is_null($r['attendance']) && $r['attendance'] < 75 ? 'text-red fw-bold' : '' }}">{{ is_null($r['attendance']) ? '—' : $r['attendance'].'%' }}</td>
                                    <td>@foreach ($r['reasons'] as $reason)<span class="badge bg-red-lt me-1">{{ $reason }}</span>@endforeach</td>
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

@push('scripts')
<script src="{{ asset('js/chart.umd.min.js') }}"></script>
<script>
    const dist = @json($distribution);
    const att = @json($attendanceTrend);
    const primary = '#206bc4';

    function clearSkeleton(id) {
        var s = document.querySelector('[data-skeleton-for="' + id + '"]');
        if (s) s.remove();
    }

    new Chart(document.getElementById('chartDistribution'), {
        type: 'bar',
        data: { labels: dist.labels, datasets: [{ label: 'Jumlah mahasiswa', data: dist.values, backgroundColor: primary }] },
        options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
    });
    clearSkeleton('chartDistribution');

    new Chart(document.getElementById('chartAttendance'), {
        type: 'line',
        data: { labels: att.labels, datasets: [{ label: '% Hadir', data: att.values, borderColor: '#2fb344', backgroundColor: 'rgba(47,179,68,.1)', fill: true, tension: .3 }] },
        options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, max: 100 } } }
    });
    clearSkeleton('chartAttendance');
</script>
@endpush
