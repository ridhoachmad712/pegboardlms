@extends('layouts.app')

@section('title', 'Panduan')
@section('page-pretitle', 'Bantuan')
@section('page-title', 'Panduan Penggunaan')

@section('content')
@php($isDosen = auth()->user()->isDosen())

<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="card mb-3">
            <div class="card-body">
                <p class="mb-0 text-secondary">
                    @if ($isDosen)
                        Panduan singkat untuk dosen. Hampir semua aktivitas dilakukan <strong>di dalam kelas</strong>:
                        buka <a href="{{ route('courses.index') }}">Kelas Saya</a> → pilih kelas → gunakan tab di bagian atas
                        (Materi · Tugas &amp; Kuis · Penilaian · Kehadiran · Forum · Pengumuman · RPS · Analitik).
                    @else
                        Panduan singkat untuk mahasiswa. Semua materi, tugas, dan nilai ada <strong>di dalam kelas</strong>:
                        buka <a href="{{ route('courses.index') }}">Kelas Saya</a> → pilih kelas → gunakan tab di bagian atas.
                    @endif
                </p>
            </div>
        </div>

        @php($steps = $isDosen ? [
            ['ti-school', 'Buat kelas', 'Menu Kelas Saya → tombol Buat Kelas. Isi nama, kode MK, semester, tahun.'],
            ['ti-users', 'Tambahkan mahasiswa', 'Buka kelas → tab Mahasiswa → Tambah Mahasiswa (pilih) atau Import CSV (kolom: nama, email, nim).'],
            ['ti-folder', 'Pertemuan & materi', 'Tab Materi → Tambah Pertemuan, lalu tombol Materi pada pertemuan untuk unggah berkas / tautan / video.'],
            ['ti-checklist', 'Tugas & kuis', 'Tab Tugas & Kuis → Buat Tugas atau Buat Kuis. Untuk kuis, tambahkan soal (PG/esai) atau Generate Soal (AI). Nilai pengumpulan dari halaman tugas.'],
            ['ti-qrcode', 'Absensi QR', 'Tab Materi → tombol Absensi pada pertemuan → Mulai Absensi. QR tampil 15 menit, mahasiswa memindai untuk hadir. Status bisa diubah manual.'],
            ['ti-clipboard-check', 'Penilaian & ekspor', 'Tab Penilaian → atur komponen (total 100%). Nilai akhir & huruf otomatis. Ekspor ke Excel / PDF dari tombol di kanan atas.'],
            ['ti-chart-histogram', 'Analitik, forum, pengumuman, RPS', 'Tab Analitik untuk grafik & mahasiswa berisiko. Forum & Pengumuman untuk komunikasi. RPS untuk silabus (bisa diunduh PDF).'],
        ] : [
            ['ti-school', 'Buka kelas', 'Menu Kelas Saya → klik kelas yang Anda ikuti. Semua fitur ada di tab bagian atas.'],
            ['ti-folder', 'Lihat & unduh materi', 'Tab Materi → setiap pertemuan berisi berkas/tautan. Klik Unduh atau Buka.'],
            ['ti-checklist', 'Kerjakan tugas & kuis', 'Tab Tugas & Kuis. Tugas: unggah berkas (sekali kumpul). Kuis: kerjakan dengan timer, jawaban PG dinilai otomatis.'],
            ['ti-qrcode', 'Absen', 'Saat dosen membuka sesi, pindai QR dengan kamera HP — kehadiran tercatat otomatis. Bila QR gagal, masukkan kode absen di halaman Kehadiran.'],
            ['ti-clipboard-check', 'Lihat nilai & kehadiran', 'Tab Penilaian menampilkan nilai per komponen & nilai akhir. Tab Kehadiran menampilkan persentase Anda (jaga di atas 75%).'],
            ['ti-messages', 'Forum, pengumuman & notifikasi', 'Ikut diskusi di Forum, baca Pengumuman dari dosen, dan cek lonceng notifikasi di kanan atas.'],
        ])

        <div class="card">
            <div class="card-body">
                <ul class="steps steps-vertical">
                    @foreach ($steps as $i => [$icon, $title, $desc])
                        <li class="step-item">
                            <div class="h4 m-0"><i class="ti {{ $icon }} me-2 text-primary"></i>{{ $i + 1 }}. {{ $title }}</div>
                            <div class="text-secondary">{{ $desc }}</div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="text-center text-secondary small mt-3">
            Butuh akun atau bantuan teknis? Hubungi admin/dosen pengampu.
        </div>
    </div>
</div>
@endsection
