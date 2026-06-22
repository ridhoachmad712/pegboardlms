<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ChecksCourseAccess;
use App\Models\Assignment;
use App\Models\QuizQuestion;
use App\Models\Submission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuizController extends Controller
{
    use ChecksCourseAccess;

    // ---------- Dosen: kelola soal ----------

    public function questions(Request $request, Assignment $assignment): View
    {
        $this->ensureCourseOwner($request, $assignment->course);
        abort_unless($assignment->isQuiz(), 404);

        $assignment->load('questions');

        // Materi PDF di kelas yang sama (untuk generate soal AI)
        $pdfMaterials = \App\Models\Material::whereHas('meeting', fn ($q) => $q->where('course_id', $assignment->course_id))
            ->where('type', 'file')
            ->where('path', 'like', '%.pdf')
            ->get(['id', 'title']);

        $aiEnabled = app(\App\Services\AiService::class)->isConfigured();

        return view('quizzes.questions', compact('assignment', 'pdfMaterials', 'aiEnabled'));
    }

    public function storeQuestion(Request $request, Assignment $assignment): RedirectResponse
    {
        $this->ensureCourseOwner($request, $assignment->course);
        abort_unless($assignment->isQuiz(), 404);

        $data = $request->validate([
            'type' => ['required', 'in:pg,essay'],
            'question' => ['required', 'string'],
            'points' => ['required', 'integer', 'min:1', 'max:100'],
            'options' => ['required_if:type,pg', 'array'],
            'options.*' => ['nullable', 'string'],
            'correct_answer' => ['required_if:type,pg', 'nullable', 'string'],
        ]);

        $attributes = [
            'type' => $data['type'],
            'question' => $data['question'],
            'points' => $data['points'],
        ];

        if ($data['type'] === QuizQuestion::TYPE_PG) {
            // pertahankan key A,B,C,D; buang opsi kosong
            $options = array_filter($data['options'], fn ($o) => trim((string) $o) !== '');

            if (count($options) < 2 || ! array_key_exists($data['correct_answer'], $options)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'options' => 'Isi minimal 2 opsi dan pastikan opsi jawaban benar terisi.',
                ]);
            }

            $attributes['options'] = $options;
            $attributes['correct_answer'] = $data['correct_answer'];
        }

        $assignment->questions()->create($attributes);

        return back()->with('status', 'Soal ditambahkan.');
    }

    public function updateQuestion(Request $request, QuizQuestion $question): RedirectResponse
    {
        $this->ensureCourseOwner($request, $question->assignment->course);

        $data = $request->validate([
            'type' => ['required', 'in:pg,essay'],
            'question' => ['required', 'string'],
            'points' => ['required', 'integer', 'min:1', 'max:100'],
            'options' => ['required_if:type,pg', 'array'],
            'options.*' => ['nullable', 'string'],
            'correct_answer' => ['required_if:type,pg', 'nullable', 'string'],
        ]);

        $attributes = [
            'type' => $data['type'],
            'question' => $data['question'],
            'points' => $data['points'],
            'options' => null,
            'correct_answer' => null,
        ];

        if ($data['type'] === QuizQuestion::TYPE_PG) {
            $options = array_filter($data['options'], fn ($o) => trim((string) $o) !== '');
            if (count($options) < 2 || ! array_key_exists($data['correct_answer'], $options)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'options' => 'Isi minimal 2 opsi dan pastikan opsi jawaban benar terisi.',
                ]);
            }
            $attributes['options'] = $options;
            $attributes['correct_answer'] = $data['correct_answer'];
        }

        $question->update($attributes);

        return back()->with('status', 'Soal diperbarui.');
    }

    public function destroyQuestion(Request $request, QuizQuestion $question): RedirectResponse
    {
        $this->ensureCourseOwner($request, $question->assignment->course);
        $question->delete();

        return back()->with('status', 'Soal dihapus.');
    }

    /** Ekspor seluruh soal kuis sebagai berkas JSON (bank soal). */
    public function exportQuestions(Request $request, Assignment $assignment)
    {
        $this->ensureCourseOwner($request, $assignment->course);
        abort_unless($assignment->isQuiz(), 404);

        $assignment->load('questions');

        $payload = [
            'quiz' => $assignment->title,
            'exported_at' => now()->toIso8601String(),
            'questions' => $assignment->questions->map(fn (QuizQuestion $q) => [
                'type' => $q->type,
                'question' => $q->question,
                'points' => $q->points,
                'options' => $q->options,
                'correct_answer' => $q->correct_answer,
            ])->values(),
        ];

        $name = 'soal-'.\Illuminate\Support\Str::slug($assignment->title).'.json';

        return response()->json($payload, 200, [
            'Content-Disposition' => 'attachment; filename="'.$name.'"',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /** Impor soal dari berkas JSON hasil ekspor (ditambahkan ke kuis ini). */
    public function importQuestions(Request $request, Assignment $assignment): RedirectResponse
    {
        $this->ensureCourseOwner($request, $assignment->course);
        abort_unless($assignment->isQuiz(), 404);

        $request->validate(['file' => ['required', 'file', 'mimes:json,txt', 'max:1024']]);

        $json = json_decode(file_get_contents($request->file('file')->getRealPath()), true);
        $items = $json['questions'] ?? (is_array($json) ? $json : null);

        if (! is_array($items)) {
            return back()->with('error', 'Format berkas tidak valid — JSON soal tidak ditemukan.');
        }

        $created = 0;
        $skipped = 0;

        foreach ($items as $item) {
            if (! is_array($item)) {
                $skipped++;

                continue;
            }

            $type = ($item['type'] ?? 'pg') === 'essay' ? 'essay' : 'pg';
            $question = trim((string) ($item['question'] ?? ''));
            $points = max(1, (int) ($item['points'] ?? 1));

            if ($question === '') {
                $skipped++;

                continue;
            }

            $attr = ['type' => $type, 'question' => $question, 'points' => $points, 'options' => null, 'correct_answer' => null];

            if ($type === QuizQuestion::TYPE_PG) {
                $options = is_array($item['options'] ?? null)
                    ? array_filter($item['options'], fn ($o) => trim((string) $o) !== '')
                    : [];
                $correct = (string) ($item['correct_answer'] ?? '');

                if (count($options) < 2 || ! array_key_exists($correct, $options)) {
                    $skipped++;

                    continue;
                }
                $attr['options'] = $options;
                $attr['correct_answer'] = $correct;
            }

            $assignment->questions()->create($attr);
            $created++;
        }

        return back()->with('status', "Impor soal selesai: {$created} ditambahkan, {$skipped} dilewati.");
    }

    // ---------- Mahasiswa: kerjakan ----------

    public function take(Request $request, Assignment $assignment): View|RedirectResponse
    {
        $assignment->load(['course', 'questions']);
        $this->ensureCourseAccess($request, $assignment->course);
        $user = $request->user();
        abort_unless($user->isMahasiswa(), 403);
        abort_unless($assignment->isQuiz(), 404);
        abort_if($assignment->questions->isEmpty(), 404, 'Kuis belum memiliki soal.');

        $submission = $assignment->submissionFor($user);

        // Sudah selesai → ke review
        if ($submission && $submission->submitted_at) {
            return redirect()->route('quizzes.review', $submission);
        }

        // Mulai attempt baru
        if (! $submission) {
            $submission = $assignment->submissions()->create([
                'user_id' => $user->id,
                'status' => $assignment->isPastDeadline() ? 'late' : 'ontime',
                'started_at' => now(),
            ]);
        }

        $endsAt = $assignment->duration_minutes
            ? $submission->started_at->copy()->addMinutes($assignment->duration_minutes)
            : null;

        return view('quizzes.take', compact('assignment', 'submission', 'endsAt'));
    }

    public function submit(Request $request, Assignment $assignment): RedirectResponse
    {
        $assignment->load(['course', 'questions']);
        $this->ensureCourseAccess($request, $assignment->course);
        $user = $request->user();
        abort_unless($user->isMahasiswa(), 403);

        $submission = $assignment->submissionFor($user);
        abort_if(! $submission, 404);
        abort_if($submission->submitted_at !== null, 403, 'Kuis sudah dikumpulkan.');

        $answers = $request->input('answers', []); // [question_id => value]

        foreach ($assignment->questions as $q) {
            $value = $answers[$q->id] ?? null;
            $score = null;

            if ($q->isPg()) {
                $score = ($value !== null && $value === $q->correct_answer) ? $q->points : 0;
            }

            $submission->answers()->updateOrCreate(
                ['quiz_question_id' => $q->id],
                ['answer' => $value, 'score' => $score],
            );
        }

        $submission->update(['submitted_at' => now()]);
        $this->recalculateScore($submission->fresh('answers'), $assignment);

        return redirect()->route('quizzes.review', $submission)
            ->with('status', 'Kuis berhasil dikumpulkan.');
    }

    public function review(Request $request, Submission $submission): View
    {
        $submission->load(['assignment.course', 'assignment.questions', 'answers']);
        $assignment = $submission->assignment;

        $user = $request->user();
        $isOwnerStudent = $submission->user_id === $user->id;
        $isDosen = $user->isDosen() && $assignment->course->user_id === $user->id;
        abort_unless($isOwnerStudent || $isDosen, 403);

        $answers = $submission->answers->keyBy('quiz_question_id');

        return view('quizzes.review', compact('submission', 'assignment', 'answers', 'isDosen'));
    }

    /** Dosen menilai jawaban esai. */
    public function gradeEssays(Request $request, Submission $submission): RedirectResponse
    {
        $submission->load('assignment.course', 'assignment.questions');
        $this->ensureCourseOwner($request, $submission->assignment->course);

        $scores = $request->input('scores', []); // [answer_id => score]

        foreach ($submission->answers as $answer) {
            if (array_key_exists($answer->id, $scores) && $scores[$answer->id] !== null && $scores[$answer->id] !== '') {
                $max = $answer->question->points;
                $answer->update(['score' => min((float) $scores[$answer->id], $max)]);
            }
        }

        $this->recalculateScore($submission->fresh('answers'), $submission->assignment);

        return back()->with('status', 'Nilai esai tersimpan.');
    }

    /** Hitung skor akhir submission = (poin diperoleh / total poin) * max_score. */
    private function recalculateScore(Submission $submission, Assignment $assignment): void
    {
        $total = $assignment->totalPoints();
        if ($total <= 0) {
            return;
        }

        // Jika masih ada jawaban yang belum dinilai (esai) → biarkan null
        $hasUngraded = $submission->answers->contains(fn ($a) => is_null($a->score));
        if ($hasUngraded) {
            $submission->update(['score' => null]);

            return;
        }

        $earned = (float) $submission->answers->sum(fn ($a) => (float) $a->score);
        $final = round($earned / $total * $assignment->max_score, 2);

        $submission->update(['score' => $final]);
    }
}
