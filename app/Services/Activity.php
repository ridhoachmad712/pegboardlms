<?php

namespace App\Services;

use App\Models\ActivityLog;

class Activity
{
    /** Catat satu baris aktivitas (pelaku = pengguna login saat ini). */
    public static function log(string $action, string $description): void
    {
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'description' => mb_substr($description, 0, 255),
        ]);
    }
}
