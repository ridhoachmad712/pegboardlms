<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ChecksCourseAccess;
use App\Models\Assignment;
use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AssignmentController extends Controller
{
    use ChecksCourseAccess;

    public function index(Request $request, Course $course): View
    {
        $this->ensureCourseAccess($request, $course);

        $assignments = $course->assignments()
            ->withCount('submissions')
            ->get();

        $user = $request->user();
        $mySubs = $user->isMahasiswa()
            ? $user->submissions()
                ->whereIn('assignment_id', $assignments->pluck('id'))
                ->get()
                ->keyBy('assignment_id')
            : collect();

        return view('assignments.index', compact('course', 'assignments', 'mySubs'));
    }

    public function create(Request $request, Course $course): View
    {
        $this->ensureCourseOwner($request, $course);

        $type = $request->query('type') === Assignment::TYPE_KUIS
            ? Assignment::TYPE_KUIS
            : Assignment::TYPE_TUGAS;

        $components = $course->gradeComponents()->get();
        $meetings = $course->meetings()->get();
        $meetingId = $request->integer('meeting') ?: null;

        return view('assignments.create', compact('course', 'type', 'components', 'meetings', 'meetingId'));
    }

    public function store(Request $request, Course $course): RedirectResponse
    {
        $this->ensureCourseOwner($request, $course);

        $data = $this->validateData($request, $course);
        $assignment = $course->assignments()->create($data);

        if ($assignment->isQuiz()) {
            return redirect()->route('quizzes.questions', $assignment)
                ->with('status', 'Kuis dibuat. Tambahkan soal sekarang.');
        }

        return redirect()->route('assignments.show', $assignment)
            ->with('status', 'Tugas berhasil dibuat.');
    }

    public function show(Request $request, Assignment $assignment): View
    {
        $assignment->load('course');
        $this->ensureCourseAccess($request, $assignment->course);

        $user = $request->user();

        if ($assignment->isQuiz()) {
            return $this->showQuiz($request, $assignment);
        }

        if ($user->isDosen()) {
            $assignment->load('course.students', 'rubricCriteria');

            $submissions = $assignment->submissions()
                ->with('student', 'rubricScores')
                ->get()
                ->sortBy('student.name');

            $pending = $assignment->course->students
                ->whereNotIn('id', $submissions->pluck('user_id'))
                ->sortBy('name')
                ->values();

            $total = $assignment->course->students->count();
            $stats = [
                'total' => $total,
                'submitted' => $submissions->count(),
                'late' => $submissions->filter->isLate()->count(),
                'graded' => $submissions->filter->isGraded()->count(),
                'pending' => $pending->count(),
                'pct' => $total > 0 ? (int) round($submissions->count() / $total * 100) : 0,
            ];

            return view('assignments.show-dosen', compact('assignment', 'submissions', 'pending', 'stats'));
        }

        $submission = $assignment->submissionFor($user);

        return view('assignments.show-mahasiswa', compact('assignment', 'submission'));
    }

    public function edit(Request $request, Assignment $assignment): View
    {
        $this->ensureCourseOwner($request, $assignment->course);
        $course = $assignment->course;
        $type = $assignment->type;
        $components = $course->gradeComponents()->get();
        $meetings = $course->meetings()->get();

        return view('assignments.edit', compact('assignment', 'course', 'type', 'components', 'meetings'));
    }

    public function update(Request $request, Assignment $assignment): RedirectResponse
    {
        $this->ensureCourseOwner($request, $assignment->course);

        $assignment->update($this->validateData($request, $assignment->course));

        return redirect()->route('assignments.show', $assignment)
            ->with('status', 'Berhasil diperbarui.');
    }

    public function destroy(Request $request, Assignment $assignment): RedirectResponse
    {
        $this->ensureCourseOwner($request, $assignment->course);
        $course = $assignment->course;
        $assignment->delete();

        return redirect()->route('courses.show', $course)
            ->with('status', 'Berhasil dihapus.');
    }

    // --- helpers ---

    private function showQuiz(Request $request, Assignment $assignment): View
    {
        $user = $request->user();

        if ($user->isDosen()) {
            $assignment->loadCount('questions', 'submissions');
            $submissions = $assignment->submissions()->with('student')->get()->sortBy('student.name');

            return view('quizzes.show-dosen', compact('assignment', 'submissions'));
        }

        $submission = $assignment->submissionFor($user);

        return view('quizzes.show-mahasiswa', compact('assignment', 'submission'));
    }

    private function validateData(Request $request, Course $course): array
    {
        $rules = [
            'meeting_id' => ['required', 'integer', Rule::exists('meetings', 'id')->where('course_id', $course->id)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'in:tugas,kuis'],
            'deadline' => ['nullable', 'date'],
            'max_score' => ['required', 'integer', 'min:1', 'max:1000'],
            'grade_component_id' => ['nullable', 'integer', 'exists:grade_components,id'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:600'],
        ];

        return $request->validate($rules);
    }
}
