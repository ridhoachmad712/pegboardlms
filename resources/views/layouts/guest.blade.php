<!DOCTYPE html>
<html lang="id" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @if (config('demo.enabled'))<meta name="robots" content="noindex,nofollow">@endif
    <link rel="icon" href="{{ $faviconUrl }}">
    <title>{{ $appName }}</title>
    <link rel="stylesheet" href="{{ asset('tabler/css/tabler.min.css') }}">
    <link rel="stylesheet" href="{{ asset('tabler/css/tabler-icons.min.css') }}">
    <style>
        .page.page-center{min-height:100svh;height:auto;justify-content:flex-start;}
        .page-center>.container-tight{flex-shrink:0;margin-top:auto;margin-bottom:auto;}
        .auth-tabs{display:grid;grid-template-columns:1fr 1fr;gap:.5rem;margin-bottom:1rem;}
        .auth-tabs a{display:flex;align-items:center;justify-content:center;min-height:2.75rem;padding:.6rem;border:1px solid var(--tblr-border-color);border-radius:.75rem;color:var(--tblr-secondary-color);background:var(--tblr-bg-surface);text-decoration:none;font-weight:600;}
        .auth-tabs a[aria-current="page"]{color:var(--tblr-primary);background:rgba(var(--tblr-primary-rgb),.08);border-color:rgba(var(--tblr-primary-rgb),.35);}
        .auth-tabs a:hover{color:var(--tblr-primary);background:rgba(var(--tblr-primary-rgb),.08);}
        .activation-code-input{font-family:monospace;text-transform:uppercase;letter-spacing:.08em;}
        .activation-code-input::placeholder{font-family:var(--tblr-font-sans-serif);font-size:.875rem;text-transform:none;letter-spacing:normal;}
        .navbar-brand span{white-space:normal;}
        .card,.card-body,.navbar-brand{min-width:0;max-width:100%;overflow-wrap:anywhere;}
        :where(a,button,input):focus-visible{outline:3px solid rgba(var(--tblr-primary-rgb),.4);outline-offset:2px;}
        @media(max-width:575.98px){
            .card-md>.card-body{padding:1.25rem;}
            .form-control,.btn{min-height:2.75rem;}
            .form-control{font-size:1rem;}
            .container-tight{padding-left:1rem;padding-right:1rem;}
        }
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
