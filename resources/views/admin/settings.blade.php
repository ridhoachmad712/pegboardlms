@extends('layouts.app')

@section('title', 'Tampilan')
@section('page-pretitle', 'Pengaturan')
@section('page-title', 'Tampilan')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <form class="card" method="POST" data-warn-unsaved action="{{ route('admin.settings.update') }}" enctype="multipart/form-data">
            @csrf @method('PUT')

            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs" data-bs-toggle="tabs">
                    <li class="nav-item">
                        <a href="#tab-identitas" class="nav-link active" data-bs-toggle="tab">
                            <i class="ti ti-id me-1"></i>Identitas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#tab-branding" class="nav-link" data-bs-toggle="tab">
                            <i class="ti ti-palette me-1"></i>Tampilan &amp; Logo
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#tab-akademik" class="nav-link" data-bs-toggle="tab">
                            <i class="ti ti-calendar me-1"></i>Akademik
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#tab-integrasi" class="nav-link" data-bs-toggle="tab">
                            <i class="ti ti-plug me-1"></i>Notifikasi &amp; AI
                        </a>
                    </li>
                </ul>
            </div>

            <div class="card-body">
                <div class="tab-content">
                    {{-- ===================== IDENTITAS ===================== --}}
                    <div class="tab-pane active show" id="tab-identitas">
                        <div class="mb-3">
                            <label class="form-label required">Nama Aplikasi</label>
                            <input type="text" name="app_name" class="form-control" value="{{ old('app_name', $appName) }}" required>
                            <small class="form-hint">Tampil di judul tab browser.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label required">Judul Header</label>
                            <input type="text" name="header_title" class="form-control" value="{{ old('header_title', $headerTitle) }}" required>
                            <small class="form-hint">Teks brand di navbar atas & halaman login.</small>
                            <label class="form-check mt-2">
                                <input type="hidden" name="hide_header_title" value="0">
                                <input type="checkbox" name="hide_header_title" value="1" class="form-check-input" @checked(old('hide_header_title', $hideHeaderTitle))>
                                <span class="form-check-label">Sembunyikan teks ini di header (tampilkan logo saja)</span>
                            </label>
                        </div>
                        <div class="mb-3">
                            <label class="form-label required">Teks Footer / Institusi</label>
                            <input type="text" name="footer_text" class="form-control" value="{{ old('footer_text', $footerText) }}" maxlength="200" required>
                            <small class="form-hint">Tampil di footer setiap halaman &amp; sebagai kop pada ekspor PDF (rekap nilai, silabus).</small>
                        </div>
                    </div>

                    {{-- ===================== TAMPILAN & LOGO ===================== --}}
                    <div class="tab-pane" id="tab-branding">
                        <div class="mb-3">
                            <label class="form-label required">Warna Tema</label>
                            <div class="d-flex align-items-center flex-wrap gap-2">
                                @foreach (['#206bc4','#4263eb','#ae3ec9','#d6336c','#d63939','#f76707','#0ca678','#2fb344','#17a2b8','#74b816'] as $preset)
                                    <button type="button" class="btn p-0 rounded-circle border" style="width:30px;height:30px;background:{{ $preset }}"
                                            title="{{ $preset }}" onclick="document.getElementById('theme_color').value='{{ $preset }}'"></button>
                                @endforeach
                                <input type="color" id="theme_color" name="theme_color" class="form-control form-control-color ms-2" value="{{ old('theme_color', $themeColor) }}" style="width:48px">
                            </div>
                            <small class="form-hint">Klik swatch atau pilih warna kustom. Berlaku untuk tombol, tautan, badge, dll.</small>
                        </div>

                        <hr class="my-4">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Logo — Mode Terang</label>
                                <div class="d-flex align-items-center gap-3 mb-2">
                                    <span class="avatar avatar-md rounded" style="background-image:url('{{ $logoUrl }}');background-size:contain;background-repeat:no-repeat;background-position:center;background-color:#ffffff;border:1px solid var(--tblr-border-color)"></span>
                                    <div class="flex-fill">
                                        <input type="file" name="logo" class="form-control" accept=".png,.jpg,.jpeg,.webp,.svg">
                                    </div>
                                </div>
                                <small class="form-hint">Dipakai pada tema terang &amp; halaman login. PNG/JPG/WEBP/SVG, maks 1 MB. Kosongkan untuk tetap memakai yang sekarang.</small>
                                @if ($hasLogo)
                                    <label class="form-check mt-2">
                                        <input type="checkbox" name="remove_logo" value="1" class="form-check-input">
                                        <span class="form-check-label text-danger">Hapus logo (kembali ke ikon bawaan)</span>
                                    </label>
                                @endif
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Logo — Mode Gelap</label>
                                <div class="d-flex align-items-center gap-3 mb-2">
                                    <span class="avatar avatar-md rounded" style="background-image:url('{{ $logoDarkUrl ?? $logoUrl }}');background-size:contain;background-repeat:no-repeat;background-position:center;background-color:#182433;border:1px solid var(--tblr-border-color)"></span>
                                    <div class="flex-fill">
                                        <input type="file" name="logo_dark" class="form-control" accept=".png,.jpg,.jpeg,.webp,.svg">
                                    </div>
                                </div>
                                <small class="form-hint">Dipakai pada tema gelap (mis. logo versi putih). Jika kosong, logo mode terang dipakai untuk kedua tema.</small>
                                @if ($hasLogoDark)
                                    <label class="form-check mt-2">
                                        <input type="checkbox" name="remove_logo_dark" value="1" class="form-check-input">
                                        <span class="form-check-label text-danger">Hapus logo mode gelap</span>
                                    </label>
                                @endif
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label d-flex justify-content-between">
                                <span>Ukuran Logo di Header</span>
                                <span class="text-secondary"><span id="logo_height_val">{{ old('logo_height', $logoHeight) }}</span> px</span>
                            </label>
                            <input type="range" name="logo_height" class="form-range" min="16" max="96" step="2"
                                   value="{{ old('logo_height', $logoHeight) }}"
                                   oninput="document.getElementById('logo_height_val').textContent=this.value">
                            <small class="form-hint">Tinggi logo pada navbar atas &amp; halaman login. Default 32 px.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Favicon (ikon tab browser)</label>
                            <div class="d-flex align-items-center gap-3 mb-2">
                                <span class="avatar avatar-sm rounded" style="background-image:url('{{ $faviconUrl }}');background-size:contain;background-repeat:no-repeat;background-position:center;background-color:var(--tblr-bg-surface-secondary)"></span>
                                <div class="flex-fill">
                                    <input type="file" name="favicon" class="form-control" accept=".png,.jpg,.jpeg,.svg,.ico">
                                </div>
                            </div>
                            <small class="form-hint">PNG/SVG/ICO, maks 512 KB. Idealnya bujur sangkar (mis. 64×64).</small>
                            @if ($hasFavicon)
                                <label class="form-check mt-2">
                                    <input type="checkbox" name="remove_favicon" value="1" class="form-check-input">
                                    <span class="form-check-label text-danger">Hapus favicon (kembali ke ikon bawaan)</span>
                                </label>
                            @endif
                        </div>
                    </div>

                    {{-- ===================== AKADEMIK ===================== --}}
                    <div class="tab-pane" id="tab-akademik">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label required">Tahun Ajaran Aktif</label>
                                <input type="number" name="academic_year" class="form-control" value="{{ old('academic_year', $academicYear) }}" min="2000" max="2100" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label required">Semester Aktif</label>
                                <select name="semester" class="form-select">
                                    @foreach (['Ganjil', 'Genap', 'Antara'] as $s)
                                        <option value="{{ $s }}" @selected(old('semester', $semester) === $s)>{{ $s }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <small class="form-hint d-block">Dipakai sebagai nilai default saat membuat kelas baru.</small>
                            </div>
                        </div>
                    </div>

                    {{-- ===================== NOTIFIKASI & AI ===================== --}}
                    <div class="tab-pane" id="tab-integrasi">
                        <div class="mb-3">
                            <label class="form-check form-switch">
                                <input type="hidden" name="email_enabled" value="0">
                                <input type="checkbox" name="email_enabled" value="1" class="form-check-input" @checked($emailEnabled)>
                                <span class="form-check-label">Kirim notifikasi email (pengumuman & pengingat tugas)</span>
                            </label>
                        </div>
                        <div>
                            <span class="text-secondary">Status Integrasi AI:</span>
                            @if ($aiConfigured)
                                <span class="badge bg-green-lt">Aktif</span>
                            @else
                                <span class="badge bg-secondary-lt">Nonaktif</span> <small class="text-secondary">— isi <code>ANTHROPIC_API_KEY</code> di <code>.env</code></small>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer text-end">
                <button class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i>Simpan Pengaturan</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Jika ada field wajib yang kosong di tab non-aktif, buka tabnya agar pesan validasi terlihat.
    (function () {
        var form = document.querySelector('form[data-warn-unsaved]');
        if (!form || !window.bootstrap) return;
        form.addEventListener('invalid', function (e) {
            var pane = e.target.closest('.tab-pane');
            if (!pane || pane.classList.contains('active')) return;
            var trigger = document.querySelector('[data-bs-toggle="tab"][href="#' + pane.id + '"]');
            if (trigger) bootstrap.Tab.getOrCreateInstance(trigger).show();
        }, true);
    })();
</script>
@endpush
