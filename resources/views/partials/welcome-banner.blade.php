@php($isDosen = auth()->user()->isDosen())

<div x-data="{ show: localStorage.getItem('lms_welcome_v1') !== '1' }" x-show="show" x-cloak class="mb-3">
    <div class="card card-link bg-primary-lt">
        <div class="card-body">
            <div class="d-flex align-items-start">
                <span class="avatar bg-primary text-white me-3"><i class="ti ti-sparkles fs-2"></i></span>
                <div class="me-auto">
                    <h3 class="card-title mb-1">Selamat datang di {{ $headerTitle }} 👋</h3>
                    @if ($isDosen)
                        <p class="text-secondary mb-2">Mulai cepat: <strong>Buat Kelas</strong>, lalu kelola <strong>Materi, Tugas/Kuis, dan Absensi</strong> langsung dari tiap pertemuan di dalam kelas.</p>
                    @else
                        <p class="text-secondary mb-2">Mulai cepat: buka <strong>Kelas Saya</strong>, pilih kelas, lalu lihat Materi, kerjakan Tugas/Kuis, dan absen dengan memindai QR dari dosen.</p>
                    @endif
                    <div class="btn-list">
                        <a href="{{ route('panduan') }}" class="btn btn-primary btn-sm"><i class="ti ti-help-circle me-1"></i>Lihat panduan lengkap</a>
                        <a href="{{ route('courses.index') }}" class="btn btn-sm"><i class="ti ti-school me-1"></i>Kelas Saya</a>
                    </div>
                </div>
                <button type="button" class="btn-close" aria-label="Tutup"
                        @click="show = false; localStorage.setItem('lms_welcome_v1','1')"></button>
            </div>
        </div>
    </div>
</div>
