@extends('layouts.app')

@section('title', 'Penilaian')

@section('hero-actions')
    <a href="{{ route('export.nilai.excel', $course) }}" class="btn btn-outline-green"><i class="ti ti-file-spreadsheet me-1"></i>Excel</a>
    <a href="{{ route('export.nilai.pdf', $course) }}" class="btn btn-outline-red"><i class="ti ti-file-type-pdf me-1"></i>PDF</a>
@endsection

@section('content')
@include('courses._hero')

@php($weightTotal = $summary['weight_total'])

{{-- Peringatan alur penilaian --}}
@if (! $components->isEmpty() && $weightTotal !== 100)
    <div class="alert alert-warning d-flex align-items-center" role="alert">
        <i class="ti ti-alert-triangle me-2 fs-3"></i>
        <div>Total bobot komponen <strong>{{ $weightTotal }}%</strong> (seharusnya 100%).
            @if ($weightTotal < 100) Nilai akhir akan lebih rendah dari semestinya karena ada bobot yang belum dialokasikan.
            @else Bobot melebihi 100% — nilai akhir bisa melampaui 100. @endif
        </div>
    </div>
@endif
@if (($unlinkedGraded ?? 0) > 0)
    <div class="alert alert-warning d-flex align-items-center" role="alert">
        <i class="ti ti-link-off me-2 fs-3"></i>
        <div>Ada <strong>{{ $unlinkedGraded }}</strong> tugas/kuis yang sudah dinilai tapi <strong>belum ditautkan</strong> ke komponen nilai, jadi nilainya tidak masuk rekap. Buka tugas → Edit → pilih <em>Komponen Nilai</em>.</div>
    </div>
@endif

<div class="row row-cards">
    {{-- Komponen nilai --}}
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Komponen Nilai</h3>
                <span class="ms-auto badge bg-{{ $weightTotal === 100 ? 'green' : 'orange' }}-lt">Total {{ $weightTotal }}%</span>
            </div>
            <div class="list-group list-group-flush">
                @forelse ($components as $c)
                    <div class="list-group-item d-flex align-items-center">
                        <div>
                            <div class="fw-bold">{{ $c->name }} <span class="badge bg-blue-lt ms-1">{{ $c->weight }}%</span></div>
                            <div class="small text-secondary text-capitalize">{{ $c->type }}{{ $c->description ? ' · '.$c->description : '' }}</div>
                        </div>
                        <form method="POST" action="{{ route('grade-components.destroy', $c) }}" class="ms-auto" data-confirm="Hapus komponen ini?">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-ghost-danger"><i class="ti ti-trash"></i></button>
                        </form>
                    </div>
                @empty
                    <div class="list-group-item text-secondary small">Belum ada komponen. Tambahkan agar nilai akhir terhitung.</div>
                @endforelse
            </div>
            @unless ($course->isCompleted())
            <form class="card-body border-top" method="POST" action="{{ route('grade-components.store', $course) }}">
                @csrf
                <div class="mb-2"><label class="form-label required">Nama</label>
                    <input type="text" name="name" class="form-control" placeholder="Tugas / UTS / UAS" required></div>
                <div class="row">
                    <div class="col-7 mb-2"><label class="form-label required">Tipe</label>
                        <select name="type" class="form-select">
                            <option value="tugas">Tugas</option><option value="kuis">Kuis</option>
                            <option value="uts">UTS</option><option value="uas">UAS</option><option value="lainnya">Lainnya</option>
                        </select></div>
                    <div class="col-5 mb-2"><label class="form-label required">Bobot %</label>
                        <input type="number" name="weight" class="form-control" min="1" max="100" required></div>
                </div>
                <button class="btn btn-primary w-100"><i class="ti ti-plus me-1"></i>Tambah Komponen</button>
            </form>
            @endunless
        </div>
    </div>

    {{-- Rekap --}}
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Rekap Nilai</h3>
                <div class="ms-auto text-secondary small">
                    Rata-rata <strong>{{ $summary['avg'] }}</strong> · Tertinggi <strong>{{ $summary['max'] }}</strong> · Terendah <strong>{{ $summary['min'] }}</strong>
                </div>
            </div>
            @if ($components->isEmpty())
                <div class="card-body"><x-empty-state icon="ti-clipboard-off" title="Atur komponen nilai dulu" description="Nilai akhir butuh komponen berbobot." /></div>
            @elseif ($rows->isEmpty())
                <div class="card-body"><x-empty-state icon="ti-users" title="Belum ada mahasiswa" /></div>
            @else
                @php($hasManual = $components->whereNotIn('id', $autoComponentIds ?? [])->count() > 0)
                <form method="POST" action="{{ route('grades.saveManual', $course) }}">
                    @csrf
                    <div class="card-body py-2 border-bottom d-flex align-items-center gap-2 flex-wrap">
                        <input type="text" class="form-control form-control-sm" style="max-width:240px" placeholder="Cari mahasiswa…" data-table-search="#tbl-rekap">
                        @if ($hasManual)
                            <span class="text-secondary small ms-auto"><i class="ti ti-pencil me-1"></i>Kolom <span class="badge bg-blue-lt">manual</span> bisa diisi langsung (0–100), lalu Simpan.</span>
                        @endif
                    </div>
                    <div class="table-responsive">
                        <table id="tbl-rekap" class="table table-vcenter card-table table-sortable">
                            <thead>
                                <tr>
                                    <th>Mahasiswa</th>
                                    @foreach ($components as $c)
                                        <th class="text-center">{{ $c->name }}
                                            <div class="small fw-normal">{{ $c->weight }}% ·
                                                @if (in_array($c->id, $autoComponentIds ?? []))
                                                    <span class="text-secondary">otomatis</span>
                                                @else
                                                    <span class="badge bg-blue-lt">manual</span>
                                                @endif
                                            </div>
                                        </th>
                                    @endforeach
                                    <th class="text-center">Akhir</th><th class="text-center">Huruf</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rows as $row)
                                    <tr>
                                        <td>{{ $row['student']->name }}<div class="small text-secondary">{{ $row['student']->nim_nip }}</div></td>
                                        @foreach ($components as $c)
                                            @php($val = $row['components'][$c->id])
                                            @if (in_array($c->id, $autoComponentIds ?? []) || $course->isCompleted())
                                                <td class="text-center">{{ is_null($val) ? '—' : rtrim(rtrim($val, '0'), '.') }}</td>
                                            @else
                                                <td class="text-center" style="min-width:84px">
                                                    <input type="number" name="scores[{{ $c->id }}][{{ $row['student']->id }}]"
                                                           value="{{ is_null($val) ? '' : rtrim(rtrim($val, '0'), '.') }}"
                                                           min="0" max="100" step="0.01" class="form-control form-control-sm text-center" placeholder="—">
                                                </td>
                                            @endif
                                        @endforeach
                                        <td class="text-center fw-bold">{{ $row['final'] }}</td>
                                        <td class="text-center"><span class="badge bg-{{ \App\Support\Grades::color($row['letter']) }}-lt">{{ $row['letter'] }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if ($hasManual && ! $course->isCompleted())
                        <div class="card-footer text-end">
                            <button class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i>Simpan Nilai Manual</button>
                        </div>
                    @endif
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
