@extends('layouts.app')
@section('title', 'Aktivasi Dosen')
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
                <span class="badge bg-{{ $lecturer->needsLecturerActivation() ? 'orange' : 'green' }}-lt mt-2">{{ $lecturer->needsLecturerActivation() ? 'Menunggu aktivasi' : ($lecturer->isAdmin() ? 'Administrator' : 'Aktif') }}</span>
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
    @if ($issued && (int) $issued['lecturer_id'] === $lecturer->id)
        <section class="card border-primary mb-3" aria-labelledby="issued-code-title"><div class="card-body">
            <h2 class="h3" id="issued-code-title">Kode aktivasi berhasil dibuat</h2>
            <p class="text-secondary small">Kirim secara pribadi kepada {{ $lecturer->name }}. <strong>Kode hanya ditampilkan kali ini.</strong></p>
            <label for="issued-code" class="form-label">Kode 20 karakter</label>
            <input id="issued-code" class="form-control font-monospace text-center fw-bold mb-2" type="text" readonly value="{{ $issued['code'] }}" autocomplete="off" spellcheck="false">
            <button id="copy-activation-code" class="btn btn-primary w-100" type="button"><i class="ti ti-copy me-1" aria-hidden="true"></i>Salin Kode</button>
            <div id="copy-code-status" class="small text-secondary mt-2" role="status"></div>
            <div class="small text-secondary mt-2">Tukar sebelum {{ $issued['expires_at'] }}. Batas ini hanya untuk kode; akses akun yang sudah aktif tidak kedaluwarsa.</div>
        </div></section>
    @endif

    @if ($lecturer->needsLecturerActivation())
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
    </div>

    <div class="card admin-detail-wide">
        <div class="card-header"><h2 class="card-title">Riwayat kode aktivasi</h2></div>
        <div class="list-group list-group-flush">
            @forelse ($codes as $code)
                <div class="list-group-item">
                    <div class="d-flex flex-wrap justify-content-between gap-2">
                        <div class="fw-semibold">{{ $code->created_at->translatedFormat('d M Y, H:i') }}</div>
                        @if ($code->used_at)<span class="badge bg-green-lt">Sudah digunakan</span>
                        @elseif ($code->revoked_at)<span class="badge bg-secondary-lt">Diganti kode baru</span>
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
document.getElementById('copy-activation-code')?.addEventListener('click', async function () {
    const input = document.getElementById('issued-code');
    const status = document.getElementById('copy-code-status');
    try {
        await navigator.clipboard.writeText(input.value);
        status.textContent = 'Kode disalin. Bagikan hanya kepada dosen pemilik akun.';
    } catch (error) {
        input.focus(); input.select();
        status.textContent = 'Pilih Salin pada perangkat Anda untuk menyalin kode yang disorot.';
    }
});
</script>
@endpush
