<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Daftar semua kelas untuk admin; tiap kelas dibuka dalam mode pantau (hanya lihat). */
class CourseController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $lecturerId = $request->integer('lecturer') ?: null;
        $status = in_array($request->query('status'), [Course::STATUS_ACTIVE, Course::STATUS_COMPLETED], true)
            ? $request->query('status') : null;
        $periode = (string) $request->query('periode', '');

        $courses = Course::query()
            ->with('lecturer:id,name')
            ->withCount(['students', 'meetings', 'assignments'])
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w
                ->where('courses.name', 'like', "%$q%")
                ->orWhere('courses.code', 'like', "%$q%")
                ->orWhere('courses.class_name', 'like', "%$q%")
                ->orWhereHas('lecturer', fn ($l) => $l->where('name', 'like', "%$q%"))))
            ->when($lecturerId, fn ($query) => $query->where('user_id', $lecturerId))
            ->when($status, fn ($query) => $query->where('courses.status', $status))
            ->when(str_contains($periode, '-'), function ($query) use ($periode) {
                [$year, $semester] = explode('-', $periode, 2);
                $query->where('year', (int) $year)->where('semester', $semester);
            })
            ->orderByDesc('year')
            ->orderBy('semester')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $lecturers = User::where('role', User::ROLE_DOSEN)->whereHas('teachingCourses')->orderBy('name')->get(['id', 'name']);
        $periods = Course::query()->select('year', 'semester')->distinct()->orderByDesc('year')->orderBy('semester')->get()
            ->map(fn ($c) => ['key' => $c->year.'-'.$c->semester, 'label' => $c->semester.' '.$c->year]);

        return view('admin.courses.index', compact('courses', 'lecturers', 'periods', 'q', 'lecturerId', 'status', 'periode'));
    }
}
