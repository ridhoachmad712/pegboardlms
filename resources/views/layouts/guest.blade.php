<!DOCTYPE html>
<html lang="id" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ $faviconUrl }}">
    <title>{{ $appName }}</title>
    <link rel="stylesheet" href="{{ asset('tabler/css/tabler.min.css') }}">
    <link rel="stylesheet" href="{{ asset('tabler/css/tabler-icons.min.css') }}">
    <style>
        *{corner-shape:round !important;}
        @supports (corner-shape: squircle) {
            .card{border-radius:var(--tblr-card-border-radius) !important;}
            .card-header:first-child{border-radius:var(--tblr-card-inner-border-radius) var(--tblr-card-inner-border-radius) 0 0 !important;}
            .card-footer:last-child{border-radius:0 0 var(--tblr-card-inner-border-radius) var(--tblr-card-inner-border-radius) !important;}
        }
        :root, [data-bs-theme="light"], [data-bs-theme="dark"]{
            --tblr-primary: {{ $themeColor }};
            --tblr-primary-rgb: {{ $themeColorRgb }};
            --tblr-primary-darken: color-mix(in srgb, {{ $themeColor }}, #000 12%);
        }
    </style>
</head>
<body class="d-flex flex-column">
    <div class="page page-center">
        <div class="container container-tight py-4">
            @php($loginLogoHeight = max(48, (int) round($logoHeight * 1.5)))
            <div class="text-center mb-4">
                <a href="{{ route('login') }}" class="navbar-brand navbar-brand-autodark d-inline-flex flex-column align-items-center">
                    <img src="{{ $logoUrl }}" height="{{ $loginLogoHeight }}" alt="{{ $headerTitle }}" style="max-height:{{ $loginLogoHeight }}px;width:auto;">
                    @unless ($hideHeaderTitle)
                        <span class="fw-bold fs-1 text-primary mt-2">{{ $headerTitle }}</span>
                    @endunless
                </a>
            </div>

            @yield('content')

            <div class="text-center text-secondary mt-3 small">
                {{ $footerText }}
            </div>
        </div>
    </div>

    <script src="{{ asset('tabler/js/tabler.min.js') }}" defer></script>
</body>
</html>
