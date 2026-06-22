<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $user = $request->user();

        $courses = collect();
        $assignments = collect();
        $students = collect();

        if ($q !== '') {
            $like = '%'.$q.'%';

            // Kelas yang relevan dengan user
            $courseQuery = $user->isDosen()
                ? $user->teachingCourses()
                : $user->enrolledCourses();

            $courses = (clone $courseQuery)
                ->where(fn ($x) => $x->where('name', 'like', $like)->orWhere('code', 'like', $like))
                ->limit(10)->get();

            $courseIds = (clone $courseQuery)->pluck('courses.id');

            $assignments = Assignment::whereIn('course_id', $courseIds)
                ->where('title', 'like', $like)
                ->with('course')
                ->limit(10)->get();

            if ($user->isDosen()) {
                $students = User::where('role', User::ROLE_MAHASISWA)
                    ->whereIn('id', function ($sub) use ($courseIds) {
                        $sub->select('user_id')->from('enrollments')->whereIn('course_id', $courseIds);
                    })
                    ->where(fn ($x) => $x->where('name', 'like', $like)
                        ->orWhere('nim_nip', 'like', $like)
                        ->orWhere('email', 'like', $like))
                    ->limit(10)->get();
            }
        }

        return view('search.index', compact('q', 'courses', 'assignments', 'students'));
    }
}
