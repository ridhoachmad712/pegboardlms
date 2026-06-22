# Pegboard LMS by ridhoachmad_

Learning Management System untuk Prodi Manajemen, FEB Universitas Negeri Makassar.
Aplikasi web mobile-friendly: 1 dosen + ±120 mahasiswa.

## Tech Stack

- **Laravel 13** (PHP 8.3) — backend
- **Tabler UI** (Bootstrap 5) + **Alpine.js** — frontend, tanpa build step
- **SQLite** (dev) / **MySQL 8** (produksi)
- Blade server-side rendering

> Aset Tabler & Alpine **di-host lokal** di `public/tabler/` dan `public/js/` (tanpa CDN/npm saat runtime). Lihat bagian *Membangun ulang aset Tabler* di bawah.

## Menjalankan (development)

```bash
cd mini-lms
composer install            # jika vendor belum ada
php artisan migrate:fresh --seed
php artisan serve
```

Buka http://127.0.0.1:8000

### Akun uji (dari seeder)

| Role | Email | Kata sandi |
|------|-------|-----------|
| Dosen | `dosen@test.com` | `password` |
| Mahasiswa | `mhs001@test.com` … `mhs030@test.com` | `password` |

## Status fitur — FASE 1 (Fondasi & MVP) ✅

- [x] Autentikasi manual (login/logout) + middleware role (`dosen`/`mahasiswa`)
- [x] Profil & ubah kata sandi
- [x] Layout Tabler responsif (sidebar + topbar), redirect sesuai role
- [x] Manajemen kelas: CRUD, arsip, detail
- [x] Enroll mahasiswa: manual (pilih) + import CSV
- [x] Pertemuan (meetings) per kelas
- [x] Materi: upload berkas (PDF/PPT/DOC/XLS, maks 20 MB) / tautan / video, unduh
- [x] Dashboard dosen & mahasiswa
- [x] Seeder data uji

## Status fitur — FASE 2 (Pengajaran) ✅

- [x] **Tugas:** dosen CRUD; mahasiswa submit berkas (status tepat waktu/terlambat, sekali kumpul); dosen input nilai + feedback
- [x] **Kuis:** soal PG + esai, durasi + timer (auto-submit), auto-score PG, penilaian esai manual, review jawaban, sekali kerjakan
- [x] **Penilaian:** komponen berbobot (validasi total ≤100%), nilai akhir otomatis, konversi huruf (A/B+/B/C+/C/D/E), rekap kelas + transparansi nilai mahasiswa
- [x] **Forum:** thread + balasan, pin (dosen), hapus (penulis/dosen)
- [x] **Pengumuman:** satu arah dari dosen
- [x] **Notifikasi in-app:** lonceng + badge belum dibaca, untuk pengumuman, balasan forum, dan nilai

## Status fitur — FASE 3 (Administrasi) ✅

- [x] **Absensi & QR:** dosen mulai sesi per pertemuan → token UUID (kedaluwarsa 15 menit) + QR (SVG, endroid/qr-code); mahasiswa scan `/attend/{token}` → tercatat hadir; edit status manual (hadir/izin/sakit/alpa)
- [x] **Rekap kehadiran:** grid mahasiswa × pertemuan (H/I/S/A + %), highlight <75%
- [x] **Ekspor:** rekap nilai `.xlsx`, rekap absensi `.xlsx` (Maatwebsite/Excel, header biru), nilai akhir PDF (DomPDF, kop + tanda tangan)
- [x] **Dashboard mahasiswa lanjutan:** stat (kelas/pending/% hadir/notif), tugas mendatang (badge merah <2 hari), nilai terbaru, jadwal, alert kehadiran <75%
- [x] **Silabus/RPS:** input per kelas, jadwal pertemuan, unduh PDF

Sub-navigasi per-kelas: Materi · Tugas & Kuis · Penilaian · Kehadiran · Forum · Pengumuman · RPS.

**Library tambahan (Fase 3):** `maatwebsite/excel`, `barryvdh/laravel-dompdf`, `endroid/qr-code`.

## Status fitur — FASE 4 (Enhancement & Launch) ✅

- [x] **Analitik kelas:** distribusi nilai (bar) & tren kehadiran (line) via Chart.js (self-host), tabel mahasiswa berisiko (nilai <60 / hadir <75%), ringkasan statistik; endpoint JSON `/api/course/{id}/analytics`
- [x] **Notifikasi email:** `ReminderTugas` (H-1) & `PengumumanBaru` (mail, queued), command `lms:send-reminders`, scheduler harian 07.00 WITA, preferensi email per user
- [x] **Integrasi AI (Claude):** `ClaudeService` (Messages API via Guzzle, model `claude-sonnet-4-6`), ringkasan materi PDF (smalot/pdfparser), generate soal PG dari materi/teks; aktif bila `ANTHROPIC_API_KEY` diisi (graceful bila kosong)
- [x] **Keamanan & deploy:** rate limiting login (6/mnt) & submit/kuis (20/mnt), validasi whitelist berkas, CSRF aktif, command `lms:backup-db` + jadwal harian 02.00, panduan deploy (`../docs/deploy.md`)

**Library tambahan (Fase 4):** `smalot/pdfparser`; aset Chart.js di `public/js/chart.umd.min.js`.

🎉 **Keempat fase selesai.** Untuk produksi, lihat [panduan deploy](../docs/deploy.md) dan isi `ANTHROPIC_API_KEY` bila ingin fitur AI.

## Struktur kunci

```
app/Http/Controllers/   Auth/, Course, Enrollment, Meeting, Material, Dashboard
app/Http/Middleware/    RoleMiddleware.php  (alias 'role', didaftarkan di bootstrap/app.php)
app/Models/             User, Course, Enrollment, Meeting, Material
resources/views/        layouts/{app,guest}, auth/, courses/, dashboard/, partials/, components/
public/tabler/          CSS/JS/font Tabler (hasil build)
public/js/alpine.min.js Alpine.js (vendored)
```

## Format CSV import mahasiswa

Kolom: `nama, email, nim` (satu mahasiswa per baris; baris header opsional).
Akun baru otomatis dibuat dengan kata sandi = NIM.

## Membangun ulang aset Tabler

Sumber Tabler ada di `../tabler-dev/` (monorepo v1.4.0, pnpm). Untuk membangun ulang:

```bash
cd ../tabler-dev
pnpm install
cd core && pnpm run build      # menghasilkan core/dist/css & core/dist/js
```

Lalu salin `tabler.min.css`, `tabler-vendors.min.css`, `tabler.min.js`, font Geist
(`core/fonts/geist-*`), dan icon webfont ke `mini-lms/public/tabler/`.

> Catatan: pada Node 25 langkah `terser` (minify JS) dengan opsi source-map gagal;
> minify manual tanpa source-map berhasil: `pnpm exec terser dist/js/tabler.js --compress --mangle -o dist/js/tabler.min.js`.
