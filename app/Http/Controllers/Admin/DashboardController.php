<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $lecturers = User::where('role', User::ROLE_DOSEN)->where('is_admin', false);
        $pendingCount = (clone $lecturers)->whereNull('lecturer_activated_at')->count();
        $activeCount = (clone $lecturers)->whereNotNull('lecturer_activated_at')->count();
        $studentCount = User::where('role', User::ROLE_MAHASISWA)->count();
        $courseCount = Course::where('status', Course::STATUS_ACTIVE)->count();
        $pendingLecturers = (clone $lecturers)->whereNull('lecturer_activated_at')->oldest()->oldest('id')->limit(5)->get();

        return view('admin.dashboard', compact('pendingCount', 'activeCount', 'studentCount', 'courseCount', 'pendingLecturers'));
    }
}
