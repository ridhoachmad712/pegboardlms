@extends('layouts.app')

@section('title', 'Backup')
@section('page-pretitle', 'Admin')
@section('page-title', 'Backup Database')

@section('page-actions')
    <form method="POST" action="{{ route('admin.backups.run') }}">
        @csrf
        <button class="btn btn-primary"><i class="ti ti-database-export me-1"></i>Backup Sekarang</button>
    </form>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="alert alert-info">
            <i class="ti ti-info-circle me-1"></i>Backup juga berjalan otomatis setiap hari pukul 02.00 WITA. Berkas disimpan di <code>storage/app/backups</code>.
        </div>
        <div class="card">
            <div class="card-header"><h3 class="card-title">Daftar Backup ({{ $backups->count() }})</h3></div>
            @if ($backups->isEmpty())
                <div class="card-body"><x-empty-state icon="ti-database-off" title="Belum ada backup" description="Klik “Backup Sekarang” untuk membuat backup pertama." /></div>
            @else
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead><tr><th>Berkas</th><th>Ukuran</th><th>Dibuat</th><th></th></tr></thead>
                        <tbody>
                            @foreach ($backups as $b)
                                <tr>
                                    <td><i class="ti ti-database me-1 text-secondary"></i>{{ $b['name'] }}</td>
                                    <td class="text-secondary">{{ $b['size'] }}</td>
                                    <td class="text-secondary">{{ $b['date'] }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('admin.backups.download', $b['name']) }}" class="btn btn-sm"><i class="ti ti-download me-1"></i>Unduh</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
