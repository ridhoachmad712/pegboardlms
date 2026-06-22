# Analisis & Keunggulan Pegboard LMS

> Pegboard LMS by ridhoachmad_ — Learning Management System untuk Prodi Manajemen, Fakultas Ekonomi dan Bisnis, Universitas Negeri Makassar.

## Ringkasan Aplikasi

**Pegboard** (nama lengkap: *Pegboard LMS by ridhoachmad_*) adalah Learning Management System berbasis **Laravel + Tabler (Bootstrap 5) + Alpine.js**, dengan dua peran: **Dosen** (sekaligus admin) dan **Mahasiswa**. Cakupannya lengkap: dari pengelolaan kelas, materi, absensi QR, tugas/kuis, penilaian, sampai RPS, analitik, dan integrasi AI.

**Teknologi inti:** Laravel (PHP), Blade + Tabler UI, Alpine.js, Vite + Tailwind, DomPDF (ekspor PDF), Maatwebsite Excel (ekspor Excel), Endroid QR Code, serta AI multi-provider (Anthropic Claude / OpenAI / Gemini).

---

## Keunggulan dari Segi Fitur (Umum)

1. **Satu platform, alur akademik utuh.** Mencakup seluruh siklus perkuliahan dalam satu tempat: RPS → pertemuan & materi → absensi → tugas/kuis → penilaian → analitik → ekspor. Tidak perlu berpindah aplikasi.

2. **Terintegrasi AI multi-provider.** Mendukung **Anthropic (Claude), OpenAI, dan Gemini** yang bisa dipilih & diuji koneksinya dari pengaturan. AI dipakai untuk **meringkas materi PDF** dan **membuat soal kuis otomatis dari PDF** — fitur yang jarang ada di LMS sederhana.

3. **Absensi QR + kode cadangan.** Sesi absensi menghasilkan QR (berlaku 15 menit) plus **kode 6 karakter** untuk input manual bila QR gagal dipindai — praktis dan anti-macet di lapangan.

4. **Penilaian terstruktur & otomatis.** Komponen nilai berbobot (wajib total 100%), skala huruf yang bisa dikonfigurasi, nilai akhir terhitung otomatis, dan ekspor **Excel + PDF berkop institusi**.

5. **White-label / branding penuh.** Logo mode terang & gelap, favicon, warna tema, nama & judul aplikasi, teks footer institusi, hingga kop pada PDF — bisa diubah tanpa menyentuh kode.

6. **Tata kelola data yang matang.** Ada **log aktivitas**, **backup database** (unduh), throttling login & submit, penguncian kelas "Selesai" (read-only), serta gerbang penyelesaian kelas (16 pertemuan + semua dinilai).

7. **UX modern.** Dark mode, responsif untuk HP, notifikasi lonceng, pengingat H-1 otomatis (in-app + email), pencarian global, kalender, sapaan menurut waktu, peringatan perubahan belum tersimpan.

---

## Keunggulan dari Segi Dosen

| Area | Keunggulan Konkret |
|------|--------------------|
| **Manajemen kelas** | Buat kelas dengan **kode gabung** (mahasiswa daftar sendiri), **impor mahasiswa via CSV** (akun otomatis dibuat, sandi = NIM), reset sandi, kelola RPS langsung saat membuat kelas. |
| **Materi** | Unggah file/tautan/video per pertemuan, **ringkasan AI** untuk PDF sekali klik. |
| **Absensi** | Buka sesi → QR + kode otomatis; bisa **edit status manual** (hadir/izin/sakit/alpa); rekap grid + ekspor Excel. |
| **Tugas & Kuis** | Kuis **PG dinilai otomatis**, esai dinilai manual; **bank soal**, **impor/ekspor soal Excel**, **generate soal dari PDF via AI**; unduh semua pengumpulan sekaligus; buka ulang submission. |
| **Penilaian** | Komponen berbobot, input nilai manual, skala huruf konfigurabel, ekspor nilai Excel & PDF berkop. |
| **Analitik** | **Distribusi nilai** (Chart.js), **tren kehadiran per pertemuan**, dan deteksi otomatis **mahasiswa berisiko** (nilai &lt;60 atau kehadiran &lt;75%) — alat pengambilan keputusan, bukan sekadar data mentah. |
| **Administrasi** | Pengaturan branding & tahun ajaran, konfigurasi AI, skala nilai, manajemen mahasiswa massal, backup DB, log aktivitas. |

**Inti keunggulan dosen:** menghemat waktu pekerjaan administratif (impor, koreksi otomatis, ekspor berkop) dan memberi **wawasan dini** lewat analitik mahasiswa berisiko.

---

## Keunggulan dari Segi Mahasiswa

- **Onboarding mudah** — gabung kelas cukup dengan kode 6 karakter, tanpa menunggu didaftarkan manual.
- **Absen anti-ribet** — pindai QR; bila gagal, tinggal ketik kode absen.
- **Transparansi nilai** — melihat komponen nilai, persentase kehadiran pribadi, dan **peringatan bila kehadiran di bawah 75%** sebelum terlambat.
- **Tidak ketinggalan deadline** — **pengingat H-1 otomatis** (lonceng in-app + email, bisa dimatikan sendiri), plus kalender tugas & jadwal.
- **Kuis instan** — hasil kuis pilihan ganda langsung keluar (dinilai otomatis), bisa **review jawaban**.
- **Akses materi terpusat** — materi per pertemuan dalam kartu yang bisa dibuka-tutup, file/tautan/video, plus **ringkasan AI** untuk membantu belajar.
- **Komunikasi & info** — forum diskusi kelas dan pengumuman dengan notifikasi.
- **Akses RPS** — bisa melihat & mengunduh RPS (CPL/CPMK/Sub-CPMK, referensi) dalam PDF rapi.
- **Nyaman dipakai** — dashboard ringkas, dark mode, dan tampilan responsif di ponsel.

**Inti keunggulan mahasiswa:** kejelasan (nilai & kehadiran transparan), ketepatan waktu (pengingat otomatis), dan kemudahan akses (kode gabung, QR, materi + ringkasan AI).

---

## Pembeda Utama Dibanding LMS Sederhana Lain

Tiga hal yang paling menonjol:

1. **AI multi-provider** untuk ringkasan materi & pembuatan soal.
2. **Analitik mahasiswa berisiko** yang *actionable*.
3. **Absensi QR + kode cadangan** yang tahan kendala lapangan.

Ketiganya membuat aplikasi ini terasa lebih dari sekadar repositori materi.
