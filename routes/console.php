<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Pengingat tugas H-1 setiap hari pukul 07.00 WITA
Schedule::command('lms:send-reminders')
    ->dailyAt('07:00')
    ->timezone('Asia/Makassar');

// Backup database harian pukul 02.00 WITA
Schedule::command('lms:backup-db')
    ->dailyAt('02:00')
    ->timezone('Asia/Makassar');
