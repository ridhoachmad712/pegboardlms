<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PrivatizeFiles extends Command
{
    /** Folder berkas yang hanya boleh diakses lewat route berotorisasi. */
    public const DIRECTORIES = ['materials', 'submissions'];

    protected $signature = 'lms:privatize-files {--reverse : Kembalikan berkas ke disk public (untuk rollback)}';

    protected $description = 'Pindahkan berkas materi & pengumpulan lama dari disk public ke disk privat (local)';

    public function handle(): int
    {
        [$from, $to] = $this->option('reverse')
            ? [Storage::disk('local'), Storage::disk('public')]
            : [Storage::disk('public'), Storage::disk('local')];

        $moved = 0;
        $failed = 0;

        foreach (self::DIRECTORIES as $dir) {
            foreach ($from->allFiles($dir) as $path) {
                // Aman diulang: berkas yang sudah ada di tujuan tidak ditimpa, cukup hapus sisa di asal.
                if (! $to->exists($path)) {
                    $stream = $from->readStream($path);
                    $ok = $stream && $to->writeStream($path, $stream);
                    if (is_resource($stream)) {
                        fclose($stream);
                    }
                    if (! $ok) {
                        $this->error('Gagal memindahkan: '.$path);
                        $failed++;

                        continue;
                    }
                }

                $from->delete($path);
                $moved++;
            }

            foreach (array_reverse($from->allDirectories($dir)) as $sub) {
                if ($from->allFiles($sub) === []) {
                    $from->deleteDirectory($sub);
                }
            }
            if ($from->exists($dir) && $from->allFiles($dir) === []) {
                $from->deleteDirectory($dir);
            }
        }

        $this->info("Berkas dipindahkan: {$moved}".($failed ? ", gagal: {$failed}" : ''));

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
