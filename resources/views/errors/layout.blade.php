<!DOCTYPE html>
<html lang="id" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>@yield('code') — {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('tabler/css/tabler.min.css') }}">
    <link rel="stylesheet" href="{{ asset('tabler/css/tabler-icons.min.css') }}">
    <style>*{corner-shape:round !important;}</style>
</head>
<body class="border-top-wide border-primary d-flex flex-column">
    <div class="page page-center">
        <div class="container-tight py-4">
            <div class="empty">
                <div class="empty-header">@yield('code')</div>
                <p class="empty-title">@yield('title')</p>
                <p class="empty-subtitle text-secondary">@yield('desc')</p>
                <div class="empty-action">
                    <a href="{{ url('/') }}" class="btn btn-primary">
                        <i class="ti ti-arrow-left me-1"></i>Kembali ke beranda
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
