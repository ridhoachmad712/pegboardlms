<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupController extends Controller
{
    private function dir(): string
    {
        return storage_path('app/backups');
    }

    public function index(): \Illuminate\View\View
    {
        File::ensureDirectoryExists($this->dir());

        $backups = collect(File::files($this->dir()))
            ->sortByDesc(fn ($f) => $f->getMTime())
            ->map(fn ($f) => [
                'name' => $f->getFilename(),
                'size' => round($f->getSize() / 1024, 1).' KB',
                'date' => date('d M Y H:i', $f->getMTime()),
            ])
            ->values();

        return view('admin.backups', compact('backups'));
    }

    public function run(): RedirectResponse
    {
        Artisan::call('lms:backup-db');

        return back()->with('status', trim(Artisan::output()) ?: 'Backup dibuat.');
    }

    public function download(string $name): BinaryFileResponse
    {
        // Cegah path traversal: hanya nama berkas di folder backups
        $base = basename($name);
        $path = $this->dir().DIRECTORY_SEPARATOR.$base;
        abort_unless(is_file($path), 404);

        return response()->download($path);
    }
}
