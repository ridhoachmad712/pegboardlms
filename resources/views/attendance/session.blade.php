@extends('layouts.app')

@section('title', 'Absensi Pertemuan ' . $meeting->number)
@section('page-pretitle', $meeting->course->name)
@section('page-title', 'Absensi — Pertemuan ' . $meeting->number)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('courses.index') }}">Kelas Saya</a></li>
    <li class="breadcrumb-item"><a href="{{ route('courses.show', $meeting->course) }}">{{ $meeting->course->name }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('attendance.index', $meeting->course) }}">Kehadiran</a></li>
    <li class="breadcrumb-item active" aria-current="page">Pertemuan {{ $meeting->number }}</li>
@endsection

@section('page-actions')
    <a href="{{ route('attendance.index', $meeting->course) }}" class="btn"><i class="ti ti-table me-1"></i>Rekap Kehadiran</a>
@endsection

@section('content')
<div class="row row-cards">
    {{-- Kontrol presensi --}}
    @if ($meeting->isMandiri())
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><h3 class="card-title"><i class="ti ti-device-laptop me-1"></i>Swa-presensi (Mandiri)</h3></div>
            <div class="card-body text-center">
                @if ($token)
                    <div class="mb-2"><span class="badge bg-green-lt">Dibuka</span></div>
                    <div class="text-secondary small mb-3">Mahasiswa bisa menandai hadir sampai<br><strong>{{ $token->expires_at->translatedFormat('d M Y, H:i') }}</strong></div>
                    <div class="d-flex gap-2 justify-content-center">
                        <form method="POST" action="{{ route('attendance.start', $meeting) }}">
                            @csrf <input type="hidden" name="days" value="7">
                            <button class="btn btn-sm"><i class="ti ti-refresh me-1"></i>Perpanjang 7 hari</button>
                        </form>
                        <form method="POST" action="{{ route('attendance.close', $meeting) }}">
                            @csrf
                            <button class="btn btn-sm btn-outline-danger"><i class="ti ti-lock me-1"></i>Tutup</button>
                        </form>
                    </div>
                @else
                    <x-empty-state icon="ti-device-laptop" title="Presensi belum dibuka" description="Buka jendela agar mahasiswa bisa menandai kehadiran mandiri." />
                    <form method="POST" action="{{ route('attendance.start', $meeting) }}">
                        @csrf
                        <div class="input-group mb-2 mx-auto" style="max-width:240px">
                            <span class="input-group-text">Buka</span>
                            <input type="number" name="days" class="form-control text-center" value="7" min="1" max="60">
                            <span class="input-group-text">hari</span>
                        </div>
                        <button class="btn btn-primary w-100"><i class="ti ti-player-play me-1"></i>Buka Presensi</button>
                    </form>
                @endif
                <div class="text-secondary small mt-3">Mahasiswa menandai hadir dari halaman <strong>kelas</strong> (pada pertemuan ini).</div>
            </div>
        </div>
    </div>
    @else
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><h3 class="card-title">QR Absensi</h3></div>
            <div class="card-body text-center">
                @if ($token && $qr)
                    <img src="{{ $qr }}" alt="QR Absensi" class="img-fluid" style="max-width:280px">
                    <div class="mt-3">
                        <span class="badge bg-green-lt">Aktif</span>
                        <div class="text-secondary small mt-1">Berlaku sampai {{ $token->expires_at->translatedFormat('H:i') }}
                            (<span x-data="{ s: {{ max(0, (int) now()->diffInSeconds($token->expires_at, false)) }} }"
                                   x-init="setInterval(()=>{ if(s>0)s--; $el.textContent = Math.floor(s/60)+':'+String(s%60).padStart(2,'0') }, 1000)">—</span>)
                        </div>
                        @if ($token->code)
                            <div class="mt-3">
                                <div class="text-secondary small mb-1">Kode absen (bila QR gagal dipindai)</div>
                                <div class="d-flex align-items-center justify-content-center gap-2">
                                    <span class="display-6 fw-bold font-monospace" style="letter-spacing:.2em">{{ $token->code }}</span>
                                    <button type="button" class="btn btn-icon btn-sm" title="Salin kode"
                                            onclick="navigator.clipboard.writeText('{{ $token->code }}').then(()=>{this.querySelector('i').className='ti ti-check'})">
                                        <i class="ti ti-copy"></i>
                                    </button>
                                </div>
                                <small class="text-secondary">Mahasiswa memasukkannya di menu Absensi kelas.</small>
                            </div>
                        @endif
                        <div class="input-group mt-2">
                            <input type="text" class="form-control form-control-sm" value="{{ route('attendance.attend', $token->token) }}" readonly onclick="this.select()">
                        </div>
                    </div>
                    <form method="POST" action="{{ route('attendance.start', $meeting) }}" class="mt-3">
                        @csrf
                        <button class="btn btn-sm w-100"><i class="ti ti-refresh me-1"></i>Buat QR Baru</button>
                    </form>
                @else
                    <x-empty-state icon="ti-qrcode" title="Belum ada sesi aktif" description="Mulai absensi untuk menampilkan QR yang dipindai mahasiswa." />
                    <form method="POST" action="{{ route('attendance.start', $meeting) }}">
                        @csrf
                        <button class="btn btn-primary w-100"><i class="ti ti-player-play me-1"></i>Mulai Absensi</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- Edit manual --}}
    <div class="col-lg-7">
        <form class="card" method="POST" action="{{ route('attendance.update', $meeting) }}">
            @csrf
            <div class="card-header"><h3 class="card-title">Daftar Hadir ({{ $students->count() }})</h3>
                <div class="card-actions"><button class="btn btn-sm btn-primary">Simpan</button></div>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead><tr><th>Mahasiswa</th><th>Metode</th><th style="width:160px">Status</th></tr></thead>
                    <tbody>
                        @foreach ($students as $s)
                            @php($att = $attendances[$s->id] ?? null)
                            <tr>
                                <td>{{ $s->name }}<div class="small text-secondary">{{ $s->nim_nip }}</div></td>
                                <td>@if ($att)<span class="badge bg-{{ ['qr' => 'green', 'mandiri' => 'azure'][$att->method] ?? 'secondary' }}-lt">{{ ['qr' => 'QR', 'mandiri' => 'Mandiri', 'manual' => 'Manual'][$att->method] ?? strtoupper($att->method) }}</span>@else<span class="text-secondary">—</span>@endif</td>
                                <td>
                                    <select name="statuses[{{ $s->id }}]" class="form-select form-select-sm">
                                        <option value="">—</option>
                                        @foreach (['hadir'=>'Hadir','izin'=>'Izin','sakit'=>'Sakit','alpa'=>'Alpa'] as $val=>$lbl)
                                            <option value="{{ $val }}" @selected($att && $att->status === $val)>{{ $lbl }}</option>
                                        @endforeach
                                    </select>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer text-end"><button class="btn btn-primary">Simpan Perubahan</button></div>
        </form>
    </div>
</div>
@endsection
