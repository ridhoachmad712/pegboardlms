<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class BackupDatabase extends Command
{
    protected $signature = 'lms:backup-db';

    protected $description = 'Backup database (SQLite/MySQL) ke storage/app/backups';

    public function handle(): int
    {
        $connection = config('database.default');
        $stamp = now()->format('Y-m-d_His');
        $dir = storage_path('app/backups');
        File::ensureDirectoryExists($dir);

        if ($connection === 'sqlite') {
            $src = config('database.connections.sqlite.database');
            if (! is_file($src)) {
                $this->error('Berkas SQLite tidak ditemukan: '.$src);

                return self::FAILURE;
            }
            $dest = $dir.DIRECTORY_SEPARATOR."backup_{$stamp}.sqlite";
            copy($src, $dest);
            $this->info('Backup SQLite dibuat: '.$dest);

            return self::SUCCESS;
        }

        if ($connection === 'mysql') {
            $c = config('database.connections.mysql');
            $dest = $dir.DIRECTORY_SEPARATOR."backup_{$stamp}.sql";
            $cmd = sprintf(
                'mysqldump --host=%s --port=%s --user=%s --password=%s %s > %s',
                escapeshellarg($c['host']),
                escapeshellarg((string) $c['port']),
                escapeshellarg($c['username']),
                escapeshellarg($c['password']),
                escapeshellarg($c['database']),
                escapeshellarg($dest),
            );
            exec($cmd, $out, $code);
            if ($code !== 0) {
                $this->error('mysqldump gagal (kode '.$code.'). Pastikan mysqldump tersedia di PATH.');

                return self::FAILURE;
            }
            $this->info('Backup MySQL dibuat: '.$dest);

            return self::SUCCESS;
        }

        $this->error('Koneksi database tidak didukung untuk backup: '.$connection);

        return self::FAILURE;
    }
}
