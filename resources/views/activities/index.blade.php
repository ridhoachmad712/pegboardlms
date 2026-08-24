@extends('layouts.app')

@section('title', 'Aktivitas')
@section('page-pretitle', 'Pusat tindakan')
@section('page-title', 'Aktivitas')

@section('content')
@php($styles = [
    'assignment' => ['ti-file-text', 'blue', 'Tugas'],
    'quiz' => ['ti-help-circle', 'purple', 'Kuis'],
    'revision' => ['ti-refresh', 'orange', 'Revisi'],
    'attendance' => ['ti-qrcode', 'green', 'Absensi'],
    'grade' => ['ti-clipboard-check', 'teal', 'Nilai'],
    'announcement' => ['ti-speakerphone', 'yellow', 'Pengumuman'],
    'forum' => ['ti-messages', 'azure', 'Forum'],
])

<div class="row justify-content-center">
    <div class="col-lg-8">
        <nav class="nav nav-pills flex-nowrap overflow-x-auto gap-1 mb-3 activity-filters" aria-label="Filter aktivitas">
            @foreach (['all' => 'Semua', 'tasks' => 'Tugas & Kuis', 'attendance' => 'Absensi', 'updates' => 'Pembaruan'] as $key => $label)
                <a href="{{ route('activities.index', ['filter' => $key]) }}" class="nav-link text-nowrap {{ $filter === $key ? 'active' : '' }}">{{ $label }}</a>
            @endforeach
        </nav>

        @if ($actions->isNotEmpty())
            <section class="mb-3" aria-labelledby="actions-title">
                <div class="d-flex align-items-center mb-2">
                    <h2 class="h3 mb-0" id="actions-title">Perlu dikerjakan</h2>
                    <span class="badge bg-orange-lt ms-auto">{{ $actions->count() }}</span>
                </div>
                <div class="card overflow-hidden">
                    <div class="list-group list-group-flush">
                        @foreach ($actions as $item)
                            @php([$icon, $color, $label] = $styles[$item['kind']])
                            <a href="{{ $item['url'] }}" class="list-group-item list-group-item-action d-flex align-items-center gap-3 activity-item">
                                <span class="avatar avatar-sm bg-{{ $color }}-lt flex-shrink-0"><i class="ti {{ $icon }}"></i></span>
                                <span class="responsive-item-main">
                                    <span class="d-flex align-items-center flex-wrap gap-1"><span class="fw-bold responsive-item-title">{{ $item['title'] }}</span>@if($item['urgent'])<span class="status-dot bg-red"></span>@endif</span>
                                    <span class="d-block small text-secondary responsive-item-title">{{ $item['subtitle'] }}</span>
                                    <span class="d-block small text-{{ $item['urgent'] ? 'orange' : 'secondary' }} mt-1">{{ $item['meta'] }}</span>
                                </span>
                                <i class="ti ti-chevron-right text-secondary flex-shrink-0"></i>
                            </a>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        @if ($updates->isNotEmpty())
            <section aria-labelledby="updates-title">
                <div class="d-flex align-items-center mb-2">
                    <h2 class="h3 mb-0" id="updates-title">Pembaruan</h2>
                    <span class="badge bg-azure-lt ms-auto">{{ $updates->count() }} baru</span>
                </div>
                <div class="card overflow-hidden">
                    <div class="list-group list-group-flush">
                        @foreach ($updates as $item)
                            @php([$icon, $color, $label] = $styles[$item['kind']] ?? ['ti-bell', 'secondary', 'Info'])
                            <a href="{{ $item['url'] }}" class="list-group-item list-group-item-action d-flex align-items-start gap-3 activity-item">
                                <span class="avatar avatar-sm bg-{{ $color }}-lt flex-shrink-0"><i class="ti {{ $icon }}"></i></span>
                                <span class="responsive-item-main">
                                    <span class="d-block fw-bold responsive-item-title">{{ $item['title'] }}</span>
                                    @if($item['subtitle'])<span class="d-block small text-secondary content-prose">{{ $item['subtitle'] }}</span>@endif
                                    <span class="d-block small text-secondary mt-1">{{ $item['meta'] }}</span>
                                </span>
                                <i class="ti ti-chevron-right text-secondary flex-shrink-0 mt-1"></i>
                            </a>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        @if ($actions->isEmpty() && $updates->isEmpty())
            <div class="card"><div class="card-body"><x-empty-state icon="ti-circle-check" title="Semua beres" description="Tidak ada aktivitas yang memerlukan perhatian saat ini." /></div></div>
        @endif
    </div>
</div>
@endsection

@push('styles')
<style>
.activity-filters{scrollbar-width:none;}
.activity-filters::-webkit-scrollbar{display:none;}
.activity-item{min-height:5.25rem;}
@media(max-width:575.98px){body.student-mobile-ui .activity-item{padding:.85rem 1rem;}}
</style>
@endpush
