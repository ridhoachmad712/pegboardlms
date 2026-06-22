@extends('layouts.app')

@section('title', 'Absensi')
@section('page-title', 'Absensi via QR')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body text-center py-5">
                @if ($ok)
                    <span class="avatar avatar-xl bg-green text-white mb-3"><i class="ti ti-circle-check" style="font-size:2.5rem"></i></span>
                    <h2 class="mb-1">{{ $message }}</h2>
                    @isset($meeting)
                        <div class="text-secondary">{{ $meeting->course->name }} — Pertemuan {{ $meeting->number }}</div>
                    @endisset
                @else
                    <span class="avatar avatar-xl bg-red text-white mb-3"><i class="ti ti-circle-x" style="font-size:2.5rem"></i></span>
                    <h2 class="mb-1">Gagal Absen</h2>
                    <div class="text-secondary">{{ $message }}</div>
                @endif
                <div class="mt-4">
                    <a href="{{ route('dashboard') }}" class="btn">Ke Dashboard</a>
                    @isset($meeting)
                        <a href="{{ route('courses.show', $meeting->course) }}" class="btn btn-primary">Buka Kelas</a>
                    @endisset
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
