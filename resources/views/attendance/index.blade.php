@extends('layouts.app')

@section('title', 'Kehadiran')

@section('hero-actions')
    @if ($isDosen)
        <a href="{{ route('export.absensi.excel', $course) }}" class="btn btn-outline-green"><i class="ti ti-file-spreadsheet me-1"></i>Ekspor Excel</a>
    @endif
@endsection

@section('content')
@include('courses._hero')

@php($letter = ['hadir'=>['H','green'],'izin'=>['I','azure'],'sakit'=>['S','yellow'],'alpa'=>['A','red']])

@if ($grid['meetings']->isEmpty())
    <div class="card"><div class="card-body">
        <x-empty-state icon="ti-calendar-off" title="Belum ada pertemuan"
            :description="$isDosen ? 'Absensi mengikuti pertemuan. Tambahkan pertemuan dulu di tab Materi.' : 'Dosen belum membuat pertemuan.'">
            @if ($isDosen)
                <a href="{{ route('courses.show', $course) }}" class="btn btn-primary"><i class="ti ti-plus me-1"></i>Tambah Pertemuan</a>
            @endif
        </x-empty-state>
    </div></div>
@elseif ($isDosen)
    <div class="card">
        <div class="card-header"><h3 class="card-title">Mahasiswa × Pertemuan</h3>
            <div class="ms-auto small text-secondary">H=Hadir · I=Izin · S=Sakit · A=Alpa · {{ $grid['sessions'] }} sesi · klik <strong>P1/P2</strong> untuk buka sesi</div>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter table-bordered card-table">
                <thead>
                    <tr>
                        <th>Mahasiswa</th>
                        @foreach ($grid['meetings'] as $m)
                            <th class="text-center" title="Buka sesi absensi: {{ $m->topic }}"><a href="{{ route('attendance.session', $m) }}" class="btn btn-sm btn-outline-primary px-2 py-1">P{{ $m->number }}</a></th>
                        @endforeach
                        <th class="text-center">%</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($grid['students'] as $s)
                        <tr>
                            <td>{{ $s->name }}<div class="small text-secondary">{{ $s->nim_nip }}</div></td>
                            @foreach ($grid['meetings'] as $m)
                                @php($st = $grid['matrix'][$s->id][$m->id] ?? null)
                                <td class="text-center">
                                    @if ($st)<span class="badge bg-{{ $letter[$st][1] }}-lt">{{ $letter[$st][0] }}</span>@else<span class="text-secondary">·</span>@endif
                                </td>
                            @endforeach
                            @php($pct = $grid['summary'][$s->id]['percent'])
                            <td class="text-center fw-bold {{ ! is_null($pct) && $pct < 75 ? 'text-red' : '' }}">{{ is_null($pct) ? '—' : $pct.'%' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@else
    {{-- Mahasiswa: kehadiran pribadi --}}
    @php($pct = $grid['summary'][$me->id]['percent'] ?? null)

    {{-- Cara absen + input kode manual --}}
    <div class="card mb-3">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-7">
                    <h3 class="card-title mb-1"><i class="ti ti-qrcode me-1"></i>Cara melakukan absen</h3>
                    <ol class="text-secondary mb-0 ps-3">
                        <li>Saat dosen membuka sesi, <strong>scan QR</strong> yang ditampilkan dengan kamera HP.</li>
                        <li>Halaman absen terbuka otomatis dan kehadiran Anda tercatat.</li>
                        <li>QR hanya aktif <strong>15 menit</strong> — pastikan absen tepat waktu.</li>
                    </ol>
                </div>
                <div class="col-md-5">
                    <label class="form-label">QR tidak bisa di-scan? Masukkan kode absen</label>
                    <form onsubmit="if(this.code.value.trim()){location.href='{{ url('/attend') }}/'+encodeURIComponent(this.code.value.trim());}return false;">
                        <div class="input-group">
                            <input type="text" name="code" class="form-control text-uppercase font-monospace" maxlength="6"
                                   placeholder="mis. K7P2QX" style="letter-spacing:.15em"
                                   oninput="this.value=this.value.toUpperCase()">
                            <button class="btn btn-primary" type="submit"><i class="ti ti-check me-1"></i>Absen</button>
                        </div>
                        <small class="form-hint">Kode 6 karakter yang ditampilkan dosen di layar saat sesi absensi dibuka.</small>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row row-cards">
        <div class="col-md-4">
            <div class="card"><div class="card-body text-center">
                <div class="text-secondary">Persentase Kehadiran</div>
                <div class="h1 display-6 mb-0 {{ ! is_null($pct) && $pct < 75 ? 'text-red' : 'text-green' }}">{{ is_null($pct) ? '—' : $pct.'%' }}</div>
                @if (! is_null($pct) && $pct < 75)<span class="badge bg-red-lt">Di bawah 75%</span>@endif
            </div></div>
        </div>
        <div class="col-md-8">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Riwayat per Pertemuan</h3></div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead><tr><th>Pertemuan</th><th>Tanggal</th><th>Status</th></tr></thead>
                        <tbody>
                            @foreach ($grid['meetings'] as $m)
                                @php($st = $grid['matrix'][$me->id][$m->id] ?? null)
                                <tr>
                                    <td>P{{ $m->number }} — {{ $m->topic }}</td>
                                    <td class="text-secondary">{{ $m->date?->translatedFormat('d M Y') ?? '—' }}</td>
                                    <td>@if ($st)<span class="badge bg-{{ $letter[$st][1] }}-lt">{{ ucfirst($st) }}</span>@else<span class="text-secondary">Belum ada sesi</span>@endif</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endif
@endsection
