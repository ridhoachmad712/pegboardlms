@extends('layouts.app')

@section('title', 'Integrasi AI')
@section('page-pretitle', 'Pengaturan')
@section('page-title', 'Integrasi AI')

@section('content')
@php($keys = ['anthropic' => 'key_anthropic', 'openai' => 'key_openai', 'gemini' => 'key_gemini'])
<div class="row justify-content-center">
    <div class="col-lg-9">
        <form class="card" method="POST" action="{{ route('admin.ai.update') }}" data-warn-unsaved x-data="{ active: '{{ $active }}' }">
            @csrf @method('PUT')
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-check form-switch">
                        <input type="hidden" name="ai_enabled" value="0">
                        <input type="checkbox" name="ai_enabled" value="1" class="form-check-input" @checked($aiEnabled)>
                        <span class="form-check-label fw-bold">Aktifkan Integrasi AI</span>
                    </label>
                    <small class="form-hint">Jika dimatikan, fitur AI (<strong>Ringkasan AI</strong> &amp; <strong>Materi AI</strong>) disembunyikan di seluruh aplikasi dan tidak dapat dipanggil.</small>
                </div>

                <hr class="my-3">

                <div class="mb-3">
                    <label class="form-label required">Provider Aktif</label>
                    <select name="ai_provider" class="form-select" x-model="active" style="max-width:320px">
                        @foreach ($providers as $key => $p)
                            <option value="{{ $key }}">{{ $p['label'] }}</option>
                        @endforeach
                    </select>
                    <small class="form-hint">Yang dipakai untuk ringkasan & pembuatan soal. Anda bisa menyimpan key beberapa provider dan tinggal mengganti yang aktif.</small>
                </div>

                <hr class="my-3">

                @foreach ($providers as $key => $p)
                    <div class="card mb-3" :class="active === '{{ $key }}' ? 'border-primary' : ''" x-data="{ show: false }">
                        <div class="card-header py-2">
                            <h3 class="card-title">{{ $p['label'] }}</h3>
                            <div class="ms-auto">
                                <span class="badge bg-blue-lt" x-show="active === '{{ $key }}'" x-cloak>Aktif</span>
                                @if ($p['has_key'])
                                    <span class="badge bg-green-lt">Terisi @if ($p['key_from_env'])(.env)@endif</span>
                                @else
                                    <span class="badge bg-secondary-lt">Kosong</span>
                                @endif
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">API Key</label>
                                <div class="input-group">
                                    <input :type="show ? 'text' : 'password'" name="{{ $keys[$key] }}" class="form-control" autocomplete="off"
                                           placeholder="{{ $p['has_key'] ? '•••••••• (terisi — kosongkan untuk tetap memakai yang ada)' : 'tempel API key di sini' }}">
                                    <button type="button" class="btn" @click="show = !show" tabindex="-1"><i class="ti" :class="show ? 'ti-eye-off' : 'ti-eye'"></i></button>
                                </div>
                                @if ($p['has_key'] && ! $p['key_from_env'])
                                    <label class="form-check mt-2">
                                        <input type="checkbox" name="remove_key_{{ $key }}" value="1" class="form-check-input">
                                        <span class="form-check-label text-danger">Hapus key {{ $p['label'] }}</span>
                                    </label>
                                @endif
                            </div>
                            <div>
                                <label class="form-label required">Model</label>
                                <select name="model_{{ $key }}" class="form-select">
                                    @foreach ($p['models'] as $id => $label)
                                        <option value="{{ $id }}" @selected($p['current_model'] === $id)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="card-footer d-flex">
                <button class="btn btn-outline-primary" name="action" value="test"><i class="ti ti-plug-connected me-1"></i>Simpan &amp; Uji Provider Aktif</button>
                <button class="btn btn-primary ms-auto"><i class="ti ti-device-floppy me-1"></i>Simpan</button>
            </div>
        </form>

        <div class="card mt-3">
            <div class="card-body">
                <h3 class="card-title"><i class="ti ti-bulb me-1 text-yellow"></i>Catatan</h3>
                <ul class="text-secondary mb-2">
                    <li>Key tiap provider disimpan terpisah — ganti provider aktif tanpa kehilangan key lain.</li>
                    <li>Dapatkan key: <span class="text-secondary">Claude → console.anthropic.com · OpenAI → platform.openai.com · Gemini → aistudio.google.com</span></li>
                    <li>AI dipakai untuk: <strong>ringkasan materi PDF</strong> & <strong>generate soal kuis</strong> — prompt sudah dibatasi konteks akademik Indonesia.</li>
                </ul>
                <div class="text-secondary small">Key disimpan di server &amp; tidak ditampilkan kembali. Kosongkan kolom key untuk mempertahankan yang sudah ada.</div>
            </div>
        </div>
    </div>
</div>
@endsection
