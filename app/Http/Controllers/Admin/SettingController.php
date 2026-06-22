<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\Grades;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings', [
            'appName' => Setting::get('app_name', config('app.name')),
            'headerTitle' => Setting::get('header_title', config('app.name')),
            'hideHeaderTitle' => Setting::bool('hide_header_title', false),
            'themeColor' => Setting::get('theme_color', '#206bc4'),
            'academicYear' => Setting::get('academic_year', (string) date('Y')),
            'semester' => Setting::get('semester', 'Ganjil'),
            'emailEnabled' => Setting::bool('email_enabled', true),
            'aiConfigured' => ! empty(config('services.anthropic.key')),
            'hasLogo' => (bool) Setting::get('logo_path'),
            'hasLogoDark' => (bool) Setting::get('logo_dark_path'),
            'logoHeight' => (int) (Setting::get('logo_height') ?: 32),
            'hasFavicon' => (bool) Setting::get('favicon_path'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'app_name' => ['required', 'string', 'max:100'],
            'header_title' => ['required', 'string', 'max:100'],
            'theme_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'footer_text' => ['required', 'string', 'max:200'],
            'academic_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'semester' => ['required', 'in:Ganjil,Genap,Antara'],
            'logo' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp,svg', 'max:1024'],
            'logo_dark' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp,svg', 'max:1024'],
            'logo_height' => ['required', 'integer', 'min:16', 'max:96'],
            'favicon' => ['nullable', 'file', 'mimes:png,jpg,jpeg,svg,ico', 'max:512'],
        ]);

        Setting::put('app_name', $data['app_name']);
        Setting::put('header_title', $data['header_title']);
        Setting::put('hide_header_title', $request->boolean('hide_header_title') ? '1' : '0');
        Setting::put('theme_color', strtolower($data['theme_color']));
        Setting::put('footer_text', $data['footer_text']);
        Setting::put('academic_year', (string) $data['academic_year']);
        Setting::put('semester', $data['semester']);
        Setting::put('email_enabled', $request->boolean('email_enabled') ? '1' : '0');
        Setting::put('logo_height', (string) $data['logo_height']);

        $this->handleImage($request, 'logo', 'logo_path');
        $this->handleImage($request, 'logo_dark', 'logo_dark_path');
        $this->handleImage($request, 'favicon', 'favicon_path');

        return back()->with('status', 'Pengaturan tersimpan.');
    }

    /**
     * Simpan berkas baru, atau hapus jika tombol "hapus" ditandai.
     */
    public function editGradeScale(): View
    {
        return view('admin.grade-scale', [
            'scale' => Grades::scale(),
        ]);
    }

    public function updateGradeScale(Request $request): RedirectResponse
    {
        $letters = (array) $request->input('letter', []);
        $mins = (array) $request->input('min', []);

        $rows = [];
        foreach ($letters as $i => $letter) {
            $letter = trim((string) $letter);
            $min = $mins[$i] ?? null;
            if ($letter === '' || $min === null || $min === '') {
                continue;
            }
            $rows[] = ['letter' => mb_substr($letter, 0, 5), 'min' => max(0, min(100, (float) $min))];
        }

        if (empty($rows)) {
            return back()->with('error', 'Isi minimal satu baris skala nilai.');
        }

        usort($rows, fn ($a, $b) => $b['min'] <=> $a['min']);

        Setting::put('grade_scale', json_encode(array_values($rows)));
        Grades::flush();

        return back()->with('status', 'Skala nilai tersimpan.');
    }

    public function editAi(): View
    {
        $models = \App\Services\Ai\AiProviderFactory::models();
        $cfgKey = ['anthropic' => 'services.anthropic.key', 'openai' => 'services.openai.key', 'gemini' => 'services.gemini.key'];

        $providers = [];
        foreach (\App\Services\Ai\AiProviderFactory::labels() as $key => $label) {
            $dbKey = Setting::get('ai_key_'.$key);
            $envKey = config($cfgKey[$key]);
            $providers[$key] = [
                'label' => $label,
                'models' => $models[$key],
                'current_model' => Setting::get('ai_model_'.$key) ?: ($key === 'anthropic' ? config('services.anthropic.model') : array_key_first($models[$key])),
                'has_key' => ! empty($dbKey) || ! empty($envKey),
                'key_from_env' => empty($dbKey) && ! empty($envKey),
            ];
        }

        return view('admin.ai', [
            'active' => \App\Services\Ai\AiProviderFactory::active(),
            'providers' => $providers,
        ]);
    }

    public function updateAi(Request $request): RedirectResponse
    {
        $models = \App\Services\Ai\AiProviderFactory::models();

        $request->validate([
            'ai_provider' => ['required', 'in:anthropic,openai,gemini'],
            'model_anthropic' => ['required', 'in:'.implode(',', array_keys($models['anthropic']))],
            'model_openai' => ['required', 'in:'.implode(',', array_keys($models['openai']))],
            'model_gemini' => ['required', 'in:'.implode(',', array_keys($models['gemini']))],
            'key_anthropic' => ['nullable', 'string', 'max:255'],
            'key_openai' => ['nullable', 'string', 'max:255'],
            'key_gemini' => ['nullable', 'string', 'max:255'],
        ]);

        Setting::put('ai_provider', $request->input('ai_provider'));

        foreach (['anthropic', 'openai', 'gemini'] as $p) {
            Setting::put('ai_model_'.$p, $request->input('model_'.$p));

            if ($request->boolean('remove_key_'.$p)) {
                Setting::put('ai_key_'.$p, '');
            } elseif (! empty(trim((string) $request->input('key_'.$p, '')))) {
                Setting::put('ai_key_'.$p, trim($request->input('key_'.$p)));
            }
        }

        if ($request->input('action') === 'test') {
            $result = app(\App\Services\AiService::class)->testConnection();

            return back()->with($result['ok'] ? 'status' : 'error', $result['message']);
        }

        return back()->with('status', 'Pengaturan AI tersimpan.');
    }

    private function handleImage(Request $request, string $field, string $key): void
    {
        if ($request->hasFile($field)) {
            if ($old = Setting::get($key)) {
                Storage::disk('public')->delete($old);
            }
            Setting::put($key, $request->file($field)->store('branding', 'public'));
        } elseif ($request->boolean('remove_'.$field)) {
            if ($old = Setting::get($key)) {
                Storage::disk('public')->delete($old);
            }
            Setting::put($key, '');
        }
    }
}
