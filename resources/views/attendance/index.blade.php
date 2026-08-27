@extends('layouts.app')

@section('title', 'Kehadiran')

@section('hero-actions')
    @if ($isDosen)
        <a href="{{ route('export.absensi.excel', $course) }}" class="btn btn-outline-green"><i class="ti ti-file-spreadsheet me-1"></i>Excel</a>
        <a href="{{ route('export.absensi.pdf', $course) }}" class="btn btn-outline-red"><i class="ti ti-file-type-pdf me-1"></i>PDF</a>
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
    @php($focusStatus = $focusMeeting ? ($grid['matrix'][$me->id][$focusMeeting->id] ?? null) : null)

    {{-- Prioritas utama: status dan aksi absensi yang relevan sekarang --}}
    <div class="card mb-3 border-primary">
        <div class="card-body">
            <div class="d-flex align-items-start gap-3">
                <span class="avatar bg-primary-lt text-primary"><i class="ti ti-calendar-check fs-2"></i></span>
                <div class="flex-fill min-w-0">
                    <div class="text-secondary small fw-semibold text-uppercase">{{ $focusMeeting?->attendanceOpen() ? 'Absensi aktif' : 'Absensi hari ini' }}</div>
                    @if ($focusMeeting)
                        <h2 class="h3 mt-1 mb-1">Pertemuan {{ $focusMeeting->number }} · {{ $focusMeeting->topic }}</h2>
                        <div class="text-secondary mb-3">
                            {{ $focusMeeting->typeLabel() }}
                            @if ($focusMeeting->attendanceOpen() && $focusMeeting->attendClosesAt())
                                · Dibuka sampai {{ $focusMeeting->attendClosesAt()->translatedFormat('d M, H.i') }}
                            @elseif (! $focusMeeting->attendanceOpen())
                                · Sesi absensi belum dibuka
                            @endif
                        </div>

                        @if ($focusStatus)
                            <span class="badge bg-{{ $letter[$focusStatus][1] }}-lt px-3 py-2"><i class="ti ti-circle-check me-1"></i>{{ ucfirst($focusStatus) }}</span>
                        @elseif ($focusMeeting->attendanceOpen() && $focusMeeting->isMandiri())
                            <form method="POST" action="{{ route('attendance.selfAttend', $focusMeeting) }}">
                                @csrf
                                <button class="btn btn-primary w-100 w-sm-auto" type="submit"><i class="ti ti-hand-click me-1"></i>Tandai Saya Hadir</button>
                            </form>
                        @elseif ($focusMeeting->attendanceOpen())
                            <div class="alert alert-info mb-0 py-2"><i class="ti ti-qrcode me-1"></i>Scan QR atau masukkan kode yang ditampilkan dosen.</div>
                        @endif
                    @else
                        <h2 class="h3 mt-1 mb-1">Tidak ada absensi aktif</h2>
                        <div class="text-secondary">Absensi akan muncul di sini saat dosen membuka sesi.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row row-cards">
        <div class="col-md-4">
            <div class="card h-100"><div class="card-body">
                <div class="text-secondary">Kehadiran keseluruhan</div>
                <div class="d-flex align-items-end gap-2 mt-1">
                    <div class="h1 display-6 mb-0 {{ ! is_null($pct) && $pct < 75 ? 'text-red' : 'text-green' }}">{{ is_null($pct) ? '—' : $pct.'%' }}</div>
                    <div class="text-secondary mb-2">{{ $grid['summary'][$me->id]['hadir'] ?? 0 }} dari {{ $grid['sessions'] }} sesi</div>
                </div>
                @if (! is_null($pct) && $pct < 75)<span class="badge bg-red-lt mt-2">Di bawah batas 75%</span>@endif
            </div></div>
        </div>
        <div class="col-md-8">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Riwayat per Pertemuan</h3></div>
                <div class="list-group list-group-flush d-md-none">
                    @foreach ($grid['meetings']->sortByDesc('number') as $m)
                        @php($st = $grid['matrix'][$me->id][$m->id] ?? null)
                        <div class="list-group-item py-3">
                            <div class="d-flex align-items-center gap-3">
                                <span class="avatar avatar-sm bg-{{ $st ? $letter[$st][1] : 'secondary' }}-lt">P{{ $m->number }}</span>
                                <div class="flex-fill min-w-0">
                                    <div class="fw-semibold line-clamp-2">{{ $m->topic }}</div>
                                    <div class="small text-secondary">{{ $m->date?->translatedFormat('d M Y') ?? 'Tanggal belum ditentukan' }}</div>
                                </div>
                                @if ($st)<span class="badge bg-{{ $letter[$st][1] }}-lt">{{ ucfirst($st) }}</span>@else<span class="small text-secondary">Belum ada sesi</span>@endif
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="table-responsive d-none d-md-block">
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

    <details class="card mt-3">
        <summary class="card-body d-flex align-items-center gap-2 fw-semibold" style="cursor:pointer">
            <i class="ti ti-keyboard"></i>Masukkan kode absensi
        </summary>
        <div class="card-body border-top pt-3">
            <form onsubmit="if(this.code.value.trim()){location.href='{{ url('/attend') }}/'+encodeURIComponent(this.code.value.trim());}return false;">
                <div class="input-group">
                    <input type="text" name="code" class="form-control text-uppercase font-monospace" maxlength="6"
                           aria-label="Kode absensi" placeholder="Contoh: K7P2QX" style="letter-spacing:.15em"
                           oninput="this.value=this.value.toUpperCase()">
                    <button class="btn btn-primary" type="submit">Absen</button>
                </div>
                <small class="form-hint">Gunakan kode 6 karakter yang ditampilkan dosen.</small>
            </form>
        </div>
    </details>
@endif
@endsection
