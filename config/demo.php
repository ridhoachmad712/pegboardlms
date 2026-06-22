<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Mode Demo
    |--------------------------------------------------------------------------
    | Saat true: muncul tombol "Coba sebagai Dosen/Mahasiswa" di halaman login,
    | banner demo, dan akses 1-klik tanpa kata sandi. Di produksi biarkan false.
    */
    'enabled' => (bool) env('DEMO_MODE', false),

    // Email akun demo yang dipakai untuk akses 1-klik.
    'dosen_email' => env('DEMO_DOSEN_EMAIL', 'dosen@demo.test'),
    'mahasiswa_email' => env('DEMO_MAHASISWA_EMAIL', 'mahasiswa@demo.test'),

    // Branding demo (opsional). Di-set ulang DemoSeeder tiap reset agar tidak hilang.
    // logo_path: relatif ke storage/app/public, mis. 'branding/logo.png'
    'theme_color' => env('DEMO_THEME_COLOR'),
    'footer_text' => env('DEMO_FOOTER_TEXT'),
    'logo_path' => env('DEMO_LOGO_PATH'),
];
