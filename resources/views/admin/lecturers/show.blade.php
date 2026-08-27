@extends('layouts.app')
@section('title', 'Aktivasi Dosen')
@section('page-pretitle', 'Administrasi')
@section('page-title', 'Aktivasi Dosen')
@section('page-actions')
    <a href="{{ route('admin.lecturers.index') }}" class="btn"><i class="ti ti-arrow-left me-1" aria-hidden="true"></i>Daftar Dosen</a>
@endsection

@section('content')
<div class="row justify-content-center"><div class="col-lg-8">
    <div class="card mb-3"><div class="card-body">
        <div class="d-flex align-items-start gap-3">
            <x-avatar :name="$lecturer->name" :url="$lecturer->avatarUrl()" />
            <div class="responsive-item-main">
                <h2 class="h3 mb-1">{{ $lecturer->name }}</h2>
                <div class="text-secondary">{{ $lecturer->institution ?: 'Institusi belum diisi' }}</div>
                <div class="small text-secondary">{{ $lecturer->email }}</div>
                @if ($lecturer->nim_nip)<div class="small text-secondary">NIDN / NIP: {{ $lecturer->nim_nip }}</div>@endif
                @if ($lecturer->phone)<div class="small text-secondary">No. HP: {{ $lecturer->phone }}</div>@endif
                <div class="small text-secondary mt-1">Terdaftar {{ $lecturer->created_at->translatedFormat('d M Y, H:i') }}</div>
            </div>
        </div>
    </div></div>

    @php($issued = session('issued_activation'))
    @if ($issued && (int) $issued['lecturer_id'] === $lecturer->id)
        <section class="card border-primary mb-3" aria-labelledby="issued-code-title"><div class="card-body">
            <h2 class="h3" id="issued-code-title">Kode aktivasi berhasil dibuat</h2>
            <p class="text-secondary small">Salin dan kirim kode ini secara pribadi kepada {{ $lecturer->name }}. Kode hanya ditampilkan kali ini.</p>
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
                <h2 class="h3">Menunggu aktivasi</h2>
                <p class="text-secondary">Verifikasi pembayaran di luar aplikasi terlebih dahulu. Menerbitkan kode belum membuka akses; dosen harus menukarnya melalui akun yang tertera di atas.</p>
                <form method="POST" action="{{ route('admin.lecturers.issueCode', $lecturer) }}" data-confirm="Terbitkan kode untuk dosen ini? Seluruh kode sebelumnya yang belum dipakai akan dibatalkan.">
                    @csrf
                    <label class="form-check mb-3"><input type="checkbox" name="payment_confirmed" value="1" class="form-check-input" required><span class="form-check-label">Saya sudah memverifikasi pembayaran satu kali dosen ini.</span></label>
                    <button class="btn btn-primary w-100" type="submit" data-loading="Menerbitkan kode…">{{ $codes->total() ? 'Terbitkan Kode Baru' : 'Terbitkan Kode Aktivasi' }}</button>
                </form>
                <p class="small text-secondary mt-2 mb-0">Kode berlaku {{ config('licensing.code_valid_days') }} hari, hanya sekali pakai, dan tidak bisa dipakai akun lain. Penerbitan ulang membatalkan kode lama.</p>
            </div>
        </div>
    @else
        <div class="alert alert-success" role="status"><strong>Akun aktif · Sekali bayar</strong><div>Aktif sejak {{ $lecturer->lecturer_activated_at->translatedFormat('d M Y, H:i') }}. Tidak perlu perpanjangan atau kode tambahan.</div></div>
    @endif

    <div class="card">
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
                <div class="card-body small text-secondary">Belum ada kode diterbitkan.</div>
            @endforelse
        </div>
    </div>
    <div class="mt-3">{{ $codes->links() }}</div>
</div></div>
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
