<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Announcement;
use App\Models\ForumThread;
use App\Models\Material;
use App\Models\Meeting;
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
        $materials = collect();
        $meetings = collect();
        $announcements = collect();
        $threads = collect();
        $types = ['all', 'courses', 'assignments', 'materials', 'meetings', 'announcements', 'forum', 'students'];
        $type = in_array($request->query('type'), $types, true) ? $request->query('type') : 'all';

        $courseQuery = $user->isDosen() ? $user->teachingCourses() : $user->enrolledCourses();
        $accessibleCourses = (clone $courseQuery)->orderBy('name')->get(['courses.id', 'courses.name']);
        $courseId = $request->integer('course') ?: null;
        if ($courseId && ! $accessibleCourses->contains('id', $courseId)) {
            $courseId = null;
        }

        if ($q !== '') {
            $like = '%'.$q.'%';

            // Kelas yang relevan dengan user
            $scopedCourseQuery = (clone $courseQuery)->when($courseId, fn ($query) => $query->whereKey($courseId));
            $courses = (clone $scopedCourseQuery)
                ->when($type !== 'all' && $type !== 'courses', fn ($query) => $query->whereRaw('1 = 0'))
                ->where(fn ($x) => $x->where('name', 'like', $like)->orWhere('code', 'like', $like))
                ->limit(10)->get();

            $courseIds = (clone $scopedCourseQuery)->pluck('courses.id');

            $assignments = Assignment::whereIn('course_id', $courseIds)
                ->when($type !== 'all' && $type !== 'assignments', fn ($query) => $query->whereRaw('1 = 0'))
                ->where('title', 'like', $like)
                ->with('course')
                ->limit(10)->get();

            $meetings = Meeting::whereIn('course_id', $courseIds)
                ->when($type !== 'all' && $type !== 'meetings', fn ($query) => $query->whereRaw('1 = 0'))
                ->where(fn ($query) => $query->where('topic', 'like', $like)->orWhere('description', 'like', $like))
                ->with('course')->limit(10)->get();
            $materials = Material::whereHas('meeting', fn ($query) => $query->whereIn('course_id', $courseIds))
                ->when($type !== 'all' && $type !== 'materials', fn ($query) => $query->whereRaw('1 = 0'))
                ->where(fn ($query) => $query->where('title', 'like', $like)->orWhere('content', 'like', $like))
                ->with('meeting.course')->limit(10)->get();
            $announcements = Announcement::whereIn('course_id', $courseIds)
                ->when($type !== 'all' && $type !== 'announcements', fn ($query) => $query->whereRaw('1 = 0'))
                ->where(fn ($query) => $query->where('title', 'like', $like)->orWhere('content', 'like', $like))
                ->with('course')->limit(10)->get();
            $threads = ForumThread::whereIn('course_id', $courseIds)
                ->when($type !== 'all' && $type !== 'forum', fn ($query) => $query->whereRaw('1 = 0'))
                ->where(fn ($query) => $query->where('title', 'like', $like)->orWhere('content', 'like', $like))
                ->with('course')->limit(10)->get();

            if ($user->isDosen()) {
                $students = User::where('role', User::ROLE_MAHASISWA)
                    ->when($type !== 'all' && $type !== 'students', fn ($query) => $query->whereRaw('1 = 0'))
                    ->whereIn('id', function ($sub) use ($courseIds) {
                        $sub->select('user_id')->from('enrollments')->whereIn('course_id', $courseIds);
                    })
                    ->where(fn ($x) => $x->where('name', 'like', $like)
                        ->orWhere('nim_nip', 'like', $like)
                        ->orWhere('email', 'like', $like))
                    ->limit(10)->get();
            }
        }

        return view('search.index', compact(
            'q', 'courses', 'assignments', 'students', 'materials', 'meetings', 'announcements', 'threads',
            'type', 'courseId', 'accessibleCourses'
        ));
    }
}
