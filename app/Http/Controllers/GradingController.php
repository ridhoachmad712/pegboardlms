<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ChecksCourseAccess;
use App\Models\Assignment;
use App\Models\Submission;
use App\Services\GradingQueue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Ruang Penilaian: preview jawaban + input nilai dalam satu layar, pindah antar mahasiswa. */
class GradingController extends Controller
{
    use ChecksCourseAccess;

    public function show(Request $request, Assignment $assignment, ?Submission $submission = null): View|RedirectResponse
    {
        $assignment->load('course', 'rubricCriteria');
        $this->ensureCourseOwner($request, $assignment->course);
        abort_if($assignment->isQuiz(), 404);

        $onlyUngraded = $request->boolean('ungraded');
        $queue = GradingQueue::for($assignment);

        if ($queue->isEmpty()) {
            return redirect()->route('assignments.show', $assignment)->with('error', 'Belum ada pengumpulan untuk dinilai.');
        }

        if ($submission) {
            abort_unless($submission->assignment_id === $assignment->id, 404);
            $submission = $queue->firstWhere('id', $submission->id);
        } else {
            // Tanpa pilihan: mulai dari yang pertama belum dinilai.
            $submission = $queue->first(fn (Submission $s) => ! $s->isGraded()) ?? $queue->first();
        }

        $position = $queue->search(fn (Submission $s) => $s->id === $submission->id) + 1;

        return view('assignments.grading', [
            'assignment' => $assignment,
            'course' => $assignment->course,
            'submission' => $submission,
            'queue' => $queue,
            'position' => $position,
            'onlyUngraded' => $onlyUngraded,
            'previous' => GradingQueue::previous($queue, $submission, $onlyUngraded),
            'next' => GradingQueue::next($queue, $submission, $onlyUngraded),
            'gradedCount' => $queue->filter->isGraded()->count(),
        ]);
    }
}
