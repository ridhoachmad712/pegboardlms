@extends('layouts.app')
@section('title', 'Detail Dosen')
@section('page-pretitle', 'Administrasi')
@section('page-title', 'Detail dosen')
@section('page-actions')
    <a href="{{ route('admin.lecturers.index') }}" class="btn"><i class="ti ti-arrow-left me-1" aria-hidden="true"></i>Daftar Dosen</a>
@endsection

@section('content')
<div class="admin-detail-grid">
    <div class="card"><div class="card-body">
        <div class="d-flex align-items-start gap-3">
            <x-avatar :name="$lecturer->name" :url="$lecturer->avatarUrl()" class="flex-shrink-0" />
            <div class="responsive-item-main">
                <h2 class="h3 mb-1 responsive-item-title">{{ $lecturer->name }}</h2>
                <div class="text-secondary">{{ $lecturer->institution ?: 'Institusi belum diisi' }}</div>
                <span class="badge bg-{{ $lecturer->isLecturerDisabled() ? 'red' : ($lecturer->needsLecturerActivation() ? 'orange' : 'green') }}-lt mt-2">{{ $lecturer->isLecturerDisabled() ? 'Nonaktif' : ($lecturer->needsLecturerActivation() ? 'Menunggu aktivasi' : ($lecturer->isAdmin() ? 'Administrator' : 'Aktif')) }}</span>
            </div>
        </div>
        <dl class="admin-detail-meta">
            <div><dt>Email</dt><dd>{{ $lecturer->email }}</dd></div>
            <div><dt>Terdaftar</dt><dd>{{ $lecturer->created_at->translatedFormat('d M Y, H:i') }}</dd></div>
            @if($lecturer->nim_nip)<div><dt>NIDN / NIP</dt><dd>{{ $lecturer->nim_nip }}</dd></div>@endif
            @if($lecturer->phone)<div><dt>No. HP</dt><dd>{{ $lecturer->phone }}</dd></div>@endif
        </dl>
    </div></div>

    <div>
    @php($issued = session('issued_activation'))
    @if ($issued && (int) $issued['lecturer_id'] === $lecturer->id && ! $lecturer->isLecturerDisabled())
        <section class="card border-primary mb-3" aria-labelledby="issued-code-title"><div class="card-body">
            <h2 class="h3" id="issued-code-title">Kode aktivasi berhasil dibuat</h2>
            <p class="text-secondary small">Kirim secara pribadi kepada {{ $lecturer->name }}. <strong>Kode hanya ditampilkan kali ini.</strong></p>
            <label for="issued-code" class="form-label">Kode 20 karakter</label>
            <input id="issued-code" class="form-control font-monospace text-center fw-bold mb-2" type="text" readonly value="{{ $issued['code'] }}" autocomplete="off" spellcheck="false">
            <button data-copy-target="issued-code" data-copy-status="copy-code-status" class="btn btn-primary w-100" type="button"><i class="ti ti-copy me-1" aria-hidden="true"></i>Salin Kode</button>
            <div id="copy-code-status" class="small text-secondary mt-2" role="status"></div>
            <div class="small text-secondary mt-2">Tukar sebelum {{ $issued['expires_at'] }}. Batas ini hanya untuk kode; akses akun yang sudah aktif tidak kedaluwarsa.</div>
        </div></section>
    @endif

    @php($passwordReset = session('lecturer_password_reset'))
    @if ($passwordReset && (int) $passwordReset['lecturer_id'] === $lecturer->id && ! $lecturer->isAdmin())
        <section class="card border-primary mb-3" aria-labelledby="reset-password-title"><div class="card-body">
            <h2 class="h3" id="reset-password-title">Password sementara dibuat</h2>
            <p class="text-secondary small">Kirim secara pribadi kepada {{ $lecturer->name }}. <strong>Password hanya ditampilkan kali ini.</strong> Dosen wajib menggantinya setelah login.</p>
            <label for="temporary-password" class="form-label">Password sementara</label>
            <input id="temporary-password" class="form-control font-monospace mb-2" type="text" readonly value="{{ \Illuminate\Support\Facades\Crypt::decryptString($passwordReset['encrypted_password']) }}" autocomplete="off" spellcheck="false">
            <button data-copy-target="temporary-password" data-copy-status="copy-password-status" class="btn btn-primary w-100" type="button"><i class="ti ti-copy me-1" aria-hidden="true"></i>Salin Password</button>
            <div id="copy-password-status" class="small text-secondary mt-2" role="status"></div>
            @if($lecturer->isLecturerDisabled())<p class="small text-secondary mt-2 mb-0">Akun masih nonaktif. Aktifkan kembali aksesnya sebelum dosen dapat login.</p>@endif
        </div></section>
    @endif

    @if ($lecturer->isLecturerDisabled())
        <div class="card mb-3"><div class="card-body">
            <span class="badge bg-red-lt mb-3">Akses dinonaktifkan</span>
            <h2 class="h3">Dosen tidak dapat login</h2>
            <p class="small text-secondary mb-0">Dinonaktifkan pada {{ $lecturer->lecturer_disabled_at->translatedFormat('d M Y, H:i') }}. Kelas, materi, dan riwayat aktivasi tetap tersimpan.</p>
        </div></div>
    @elseif ($lecturer->needsLecturerActivation())
        <div class="card mb-3">
            <div class="card-body">
                <h2 class="h3">{{ $codes->total() ? 'Kelola kode aktivasi' : 'Aktifkan akses dosen' }}</h2>
                <p class="text-secondary small">Verifikasi pembayaran, lalu terbitkan kode. Dosen membuka akses dengan menukarkan kode di akunnya.</p>
                <form method="POST" action="{{ route('admin.lecturers.issueCode', $lecturer) }}" data-confirm="Terbitkan kode untuk dosen ini? Seluruh kode sebelumnya yang belum dipakai akan dibatalkan.">
                    @csrf
                    <label class="form-check mb-3"><input type="checkbox" name="payment_confirmed" value="1" class="form-check-input @error('payment_confirmed') is-invalid @enderror" required @checked(old('payment_confirmed'))><span class="form-check-label">Pembayaran satu kali sudah saya verifikasi.</span></label>
                    @error('payment_confirmed')<p class="text-danger small" role="alert">{{ $message }}</p>@enderror
                    <button class="btn btn-primary w-100" type="submit" data-loading="Menerbitkan kode…">{{ $codes->total() ? 'Terbitkan Kode Baru' : 'Terbitkan Kode Aktivasi' }}</button>
                </form>
                <p class="small text-secondary mt-2 mb-0">Kode berlaku {{ config('licensing.code_valid_days') }} hari, hanya sekali pakai, dan tidak bisa dipakai akun lain. Penerbitan ulang membatalkan kode lama.</p>
            </div>
        </div>
    @else
        <div class="card"><div class="card-body"><span class="badge bg-green-lt mb-3"><i class="ti ti-circle-check me-1" aria-hidden="true"></i>Akses aktif</span><h2 class="h3">Tidak perlu kode tambahan</h2><p class="small text-secondary mb-0">Aktif sejak {{ $lecturer->lecturer_activated_at->translatedFormat('d M Y, H:i') }}. Akses berlaku tanpa perpanjangan.</p></div></div>
    @endif

    @unless($lecturer->isAdmin())
        <section class="card mt-3" aria-labelledby="account-security-title"><div class="card-body">
            <h2 class="h3" id="account-security-title">Keamanan akun</h2>
            <p class="small text-secondary">Reset password membatalkan sesi login lama dan membuat password sementara baru. Status aktivasi akun tidak berubah.</p>
            @if($lecturer->must_change_password)<p class="small text-secondary"><i class="ti ti-key me-1" aria-hidden="true"></i>Dosen belum mengganti password sementara.</p>@endif
            <form method="POST" action="{{ route('admin.lecturers.resetPassword', $lecturer) }}" data-confirm="Reset password dosen ini? Password dan sesi login lama tidak dapat digunakan lagi. Password sementara baru akan ditampilkan sekali untuk Anda bagikan secara pribadi.">
                @csrf
                <button type="submit" class="btn w-100" data-loading="Mereset password…"><i class="ti ti-key me-1" aria-hidden="true"></i>Reset Password</button>
            </form>
            <hr class="my-3">
            @if($lecturer->isLecturerDisabled())
                <p class="small text-secondary">{{ $lecturer->needsLecturerActivation() ? 'Setelah akses dibuka kembali, dosen tetap harus menukarkan kode aktivasi pembayaran.' : 'Buka kembali akses dosen tanpa perlu pembayaran atau kode aktivasi baru.' }}</p>
                <form method="POST" action="{{ route('admin.lecturers.enable', $lecturer) }}" data-confirm="Aktifkan kembali akses akun dosen ini? Status pembayaran sebelumnya tetap berlaku.">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn btn-primary w-100" data-loading="Mengaktifkan akses…"><i class="ti ti-user-check me-1" aria-hidden="true"></i>Aktifkan Kembali Akun</button>
                </form>
            @else
                <p class="small text-secondary">Nonaktifkan untuk memblokir login dan akses dosen. Data kelas tidak dihapus dan akun dapat diaktifkan kembali.</p>
                <form method="POST" action="{{ route('admin.lecturers.disable', $lecturer) }}" data-confirm="Nonaktifkan akun dosen ini? Akses dan sesi login lama akan diblokir. Data kelas tetap tersimpan; akun dapat diaktifkan kembali.">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn btn-outline-danger w-100" data-loading="Menonaktifkan akun…"><i class="ti ti-user-off me-1" aria-hidden="true"></i>Nonaktifkan Akun</button>
                </form>
            @endif
        </div></section>
    @endunless
    </div>

    <div class="card admin-detail-wide">
        <div class="card-header"><h2 class="card-title">Riwayat kode aktivasi</h2></div>
        <div class="list-group list-group-flush">
            @forelse ($codes as $code)
                <div class="list-group-item">
                    <div class="d-flex flex-wrap justify-content-between gap-2">
                        <div class="fw-semibold">{{ $code->created_at->translatedFormat('d M Y, H:i') }}</div>
                        @if ($code->used_at)<span class="badge bg-green-lt">Sudah digunakan</span>
                        @elseif ($code->revoked_at)<span class="badge bg-secondary-lt">Dibatalkan</span>
                        @elseif ($code->expires_at->isPast())<span class="badge bg-red-lt">Kedaluwarsa</span>
                        @else<span class="badge bg-blue-lt">Menunggu penukaran</span>@endif
                    </div>
                    <div class="small text-secondary">Diterbitkan oleh {{ $code->creator?->name ?? 'Admin' }}</div>
                    <div class="small text-secondary">{{ $code->used_at ? 'Ditukar '.$code->used_at->translatedFormat('d M Y, H:i') : 'Batas penukaran '.$code->expires_at->translatedFormat('d M Y, H:i') }}</div>
                </div>
            @empty
                <div class="card-body"><x-empty-state icon="ti-key" title="Belum ada kode" description="Kode yang diterbitkan akan tercatat di sini." /></div>
            @endforelse
        </div>
    </div>
    @if($codes->hasPages())<div class="admin-detail-wide">{{ $codes->links('pagination.admin') }}</div>@endif
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('[data-copy-target]').forEach(button => button.addEventListener('click', async function () {
    const input = document.getElementById(this.dataset.copyTarget);
    const status = document.getElementById(this.dataset.copyStatus);
    try {
        await navigator.clipboard.writeText(input.value);
        status.textContent = 'Disalin. Bagikan hanya kepada dosen pemilik akun.';
    } catch (error) {
        input.focus(); input.select();
        status.textContent = 'Pilih Salin pada perangkat Anda untuk menyalin teks yang disorot.';
    }
}));
</script>
@endpush
