@extends('layouts.app')

@section('title', 'Skala Nilai')
@section('page-pretitle', 'Pengaturan')
@section('page-title', 'Skala Nilai (Bobot Huruf)')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <form class="card" method="POST" action="{{ route('admin.gradeScale.update') }}" data-warn-unsaved
              x-data="{
                rows: @js($scale),
                add() { this.rows.push({ letter: '', min: 0 }); },
                remove(i) { this.rows.splice(i, 1); },
                ranges() {
                    let s = this.rows
                        .filter(r => String(r.letter).trim() !== '' && r.min !== '' && r.min !== null)
                        .map(r => ({ letter: r.letter, min: parseFloat(r.min) || 0 }))
                        .sort((a, b) => b.min - a.min);
                    return s.map((r, i) => ({ letter: r.letter, min: r.min, max: i === 0 ? 100 : (s[i-1].min - 1) }));
                }
              }">
            @csrf @method('PUT')
            <div class="card-body">
                <p class="text-secondary">
                    Tentukan <strong>nilai minimum</strong> tiap huruf. Mahasiswa mendapat huruf tertinggi yang nilainya ≥ batas minimum.
                    Berlaku untuk seluruh kelas (rekap nilai, transkrip, ekspor).
                </p>

                <div class="table-responsive">
                    <table class="table table-vcenter">
                        <thead><tr><th style="width:35%">Huruf</th><th style="width:45%">Nilai minimum</th><th></th></tr></thead>
                        <tbody>
                            <template x-for="(row, i) in rows" :key="i">
                                <tr>
                                    <td><input type="text" class="form-control" :name="`letter[${i}]`" x-model="row.letter" maxlength="5" placeholder="A" required></td>
                                    <td><input type="number" class="form-control" :name="`min[${i}]`" x-model="row.min" min="0" max="100" step="0.01" placeholder="85" required></td>
                                    <td class="text-end"><button type="button" class="btn btn-sm btn-ghost-danger" @click="remove(i)"><i class="ti ti-trash"></i></button></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-sm" @click="add()"><i class="ti ti-plus me-1"></i>Tambah baris</button>

                {{-- Pratinjau rentang --}}
                <div class="mt-4">
                    <div class="form-label">Pratinjau rentang</div>
                    <div class="d-flex flex-wrap gap-2">
                        <template x-for="r in ranges()" :key="r.letter + r.min">
                            <span class="badge bg-blue-lt" x-text="r.letter + ' = ' + r.min + ' – ' + r.max"></span>
                        </template>
                    </div>
                    <small class="form-hint">Huruf dengan batas terendah menjadi nilai dasar (mis. E = 0).</small>
                </div>
            </div>
            <div class="card-footer d-flex">
                <a href="{{ route('admin.settings.edit') }}" class="btn btn-link">Kembali</a>
                <button class="btn btn-primary ms-auto"><i class="ti ti-device-floppy me-1"></i>Simpan Skala Nilai</button>
            </div>
        </form>
    </div>
</div>
@endsection
