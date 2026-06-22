<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\View\View;

class ActivityController extends Controller
{
    public function index(): View
    {
        $logs = ActivityLog::with('user')
            ->latest('created_at')
            ->paginate(30);

        return view('admin.activity.index', compact('logs'));
    }
}
