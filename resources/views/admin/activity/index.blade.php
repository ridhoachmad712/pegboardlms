@extends('layouts.app')

@section('title', 'Riwayat Aktivitas')
@section('page-pretitle', 'Admin')
@section('page-title', 'Riwayat Aktivitas')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Aktivitas terbaru</h3>
        <span class="text-secondary small ms-auto">{{ $logs->total() }} entri</span>
    </div>
    @if ($logs->isEmpty())
        <div class="card-body"><x-empty-state icon="ti-history" title="Belum ada aktivitas" description="Aktivitas membuat/menghapus kelas, tugas, materi, dan pengumuman akan tercatat di sini." /></div>
    @else
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead><tr><th class="w-1">Aksi</th><th>Keterangan</th><th>Oleh</th><th>Waktu</th></tr></thead>
                <tbody>
                    @foreach ($logs as $log)
                        @php($color = $log->action === 'delete' ? 'red' : ($log->action === 'create' ? 'green' : 'blue'))
                        <tr>
                            <td><span class="badge bg-{{ $color }}-lt text-uppercase">{{ $log->action }}</span></td>
                            <td>{{ $log->description }}</td>
                            <td class="text-secondary">{{ $log->user?->name ?? 'Sistem' }}</td>
                            <td class="text-secondary small" title="{{ $log->created_at?->translatedFormat('d M Y H:i') }}">
                                {{ $log->created_at?->diffForHumans() }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer d-flex align-items-center">
            {{ $logs->links() }}
        </div>
    @endif
</div>
@endsection
