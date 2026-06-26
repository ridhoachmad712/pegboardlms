<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // Di balik SSL hosting, PHP kadang mengira request http → URL (iframe/aset) jadi http
        // dan diblokir browser sebagai mixed content. Paksa https bila APP_URL https.
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        // Identitas & tema (dari pengaturan) untuk semua view
        View::share('appName', \App\Models\Setting::get('app_name', config('app.name')));
        View::share('headerTitle', \App\Models\Setting::get('header_title', config('app.name')));
        View::share('hideHeaderTitle', \App\Models\Setting::bool('hide_header_title', false));
        $themeColor = \App\Models\Setting::get('theme_color', '#206bc4');
        View::share('themeColor', $themeColor);
        $rgb = sscanf(ltrim($themeColor, '#'), '%02x%02x%02x');
        View::share('themeColorRgb', implode(',', $rgb && count($rgb) === 3 ? $rgb : [32, 107, 196]));

        // Logo, favicon & footer institusi
        $logo = \App\Models\Setting::get('logo_path');
        $logoDark = \App\Models\Setting::get('logo_dark_path');
        $favicon = \App\Models\Setting::get('favicon_path');
        View::share('logoUrl', $logo ? Storage::url($logo) : asset('favicon.svg'));
        View::share('logoDarkUrl', $logoDark ? Storage::url($logoDark) : null);
        View::share('hasLogoDark', (bool) $logoDark);
        View::share('logoHeight', (int) (\App\Models\Setting::get('logo_height') ?: 32));
        View::share('faviconUrl', $favicon ? Storage::url($favicon) : asset('favicon.svg'));
        View::share('footerText', \App\Models\Setting::get('footer_text', 'Prodi Manajemen · Fakultas Ekonomi dan Bisnis · UNM'));

        // Sapaan menurut waktu (WITA)
        $hour = (int) now()->timezone('Asia/Makassar')->format('H');
        View::share('greeting', match (true) {
            $hour < 11 => 'Selamat pagi',
            $hour < 15 => 'Selamat siang',
            $hour < 18 => 'Selamat sore',
            default => 'Selamat malam',
        });

        // Logo sebagai data URI untuk kop PDF (hanya raster — SVG tidak didukung dompdf)
        $logoData = null;
        if ($logo) {
            $abs = Storage::disk('public')->path($logo);
            $ext = strtolower(pathinfo($abs, PATHINFO_EXTENSION));
            if (is_file($abs) && in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp'])) {
                $mime = $ext === 'jpg' ? 'jpeg' : $ext;
                $logoData = 'data:image/'.$mime.';base64,'.base64_encode(file_get_contents($abs));
            }
        }
        View::share('logoData', $logoData);

        // Lonceng notifikasi pada layout utama
        View::composer('layouts.app', function ($view) {
            $user = auth()->user();
            $unread = $user ? $user->notifications()->unread()->count() : 0;
            $recent = $user ? $user->notifications()->take(6)->get() : collect();

            $view->with('navUnreadCount', $unread)->with('navNotifications', $recent);
        });

        $this->registerActivityLogging();
    }

    /** Catat otomatis pembuatan & penghapusan entitas penting (riwayat aktivitas). */
    private function registerActivityLogging(): void
    {
        $logged = [
            \App\Models\Course::class => ['kelas', 'name'],
            \App\Models\Assignment::class => ['tugas/kuis', 'title'],
            \App\Models\Material::class => ['materi', 'title'],
            \App\Models\Announcement::class => ['pengumuman', 'title'],
        ];

        foreach ($logged as $class => [$noun, $attr]) {
            $class::created(function ($model) use ($noun, $attr) {
                \App\Services\Activity::log('create', 'Menambahkan '.$noun.': "'.($model->{$attr} ?? '-').'"');
            });
            $class::updated(function ($model) use ($noun, $attr) {
                \App\Services\Activity::log('update', 'Memperbarui '.$noun.': "'.($model->{$attr} ?? '-').'"');
            });
            $class::deleted(function ($model) use ($noun, $attr) {
                \App\Services\Activity::log('delete', 'Menghapus '.$noun.': "'.($model->{$attr} ?? '-').'"');
            });
        }
    }
}
