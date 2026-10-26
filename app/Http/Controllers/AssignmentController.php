<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ChecksCourseAccess;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssignmentController extends Controller
{
    use ChecksCourseAccess;

    public function index(Request $request, Course $course): View
    {
        $this->ensureCourseAccess($request, $course);

        $user = $request->user();
        $assignments = $course->assignments()
            ->when($user->isMahasiswa(), fn ($query) => $query->where('published', true))
            ->withCount('submissions')
            ->get();

        $mySubs = $user->isMahasiswa()
            ? $user->submissions()
                ->whereIn('assignment_id', $assignments->pluck('id'))
                ->get()
                ->keyBy('assignment_id')
            : collect();

        // Tugas kelompok: pengumpulan bersama terbaca oleh semua anggota (bukan hanya pengupload).
        if ($user->isMahasiswa()) {
            foreach ($assignments->where('mode', Assignment::MODE_KELOMPOK) as $a) {
                if (! $mySubs->has($a->id) && ($gsub = $a->submissionFor($user))) {
                    $mySubs->put($a->id, $gsub);
                }
            }
        }

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
        $newPath = null;

        if (($data['type'] ?? null) === Assignment::TYPE_TUGAS && $request->hasFile('instruction_file')) {
            $file = $request->file('instruction_file');
            $newPath = $file->store('assignment-instructions/'.$course->id, 'local');
            $data = array_merge($data, $this->instructionFileData($file, $newPath));
        }

        try {
            $assignment = $course->assignments()->create($data);
        } catch (\Throwable $exception) {
            if ($newPath) {
                Storage::disk('local')->delete($newPath);
            }

            throw $exception;
        }

        if ($assignment->isInternalQuiz()) {
            return redirect()->route('quizzes.questions', $assignment)
                ->with('status', 'Kuis dibuat. Tambahkan soal sekarang.');
        }

        if ($assignment->isExternalQuiz()) {
            return redirect()->route('assignments.show', $assignment)
                ->with('status', 'Kuis eksternal dibuat. Nilai peserta dapat diinput dari halaman ini.');
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
            return $this->showDosen($assignment);
        }

        return $this->showMahasiswa($assignment, $user);
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

        $data = $this->validateData($request, $assignment->course, $assignment);
        $oldPath = $assignment->instruction_file_path;
        $newPath = null;
        $shouldRemoveInstruction = ($data['type'] ?? null) !== Assignment::TYPE_TUGAS
            || $request->boolean('remove_instruction_file');

        if (($data['type'] ?? null) === Assignment::TYPE_TUGAS && $request->hasFile('instruction_file')) {
            $file = $request->file('instruction_file');
            $newPath = $file->store('assignment-instructions/'.$assignment->course_id, 'local');
            $data = array_merge($data, $this->instructionFileData($file, $newPath));
        } elseif ($shouldRemoveInstruction) {
            $data = array_merge($data, [
                'instruction_file_path' => null,
                'instruction_file_name' => null,
                'instruction_file_mime' => null,
                'instruction_file_size' => null,
            ]);
        }

        try {
            $assignment->update($data);
        } catch (\Throwable $exception) {
            if ($newPath) {
                Storage::disk('local')->delete($newPath);
            }

            throw $exception;
        }

        if ($oldPath && ($newPath || $shouldRemoveInstruction)) {
            Storage::disk('local')->delete($oldPath);
        }

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

    public function downloadInstruction(Request $request, Assignment $assignment): StreamedResponse
    {
        $assignment->load('course');
        $this->ensureCourseAccess($request, $assignment->course);
        abort_unless($assignment->hasInstructionFile(), 404);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($assignment->instruction_file_path), 404, 'Berkas instruksi tidak ditemukan.');

        return $disk->download(
            $assignment->instruction_file_path,
            $assignment->instruction_file_name ?: 'instruksi-'.Str::slug($assignment->title),
            [
                'Content-Type' => $assignment->instruction_file_mime ?: 'application/octet-stream',
                'Cache-Control' => 'private, no-store',
            ],
        );
    }

    // --- helpers ---

    /** Halaman tugas untuk dosen — mode individu (per mahasiswa) atau kelompok (per kelompok). */
    private function showDosen(Assignment $assignment): View
    {
        $assignment->load('course.students', 'rubricCriteria');

        if ($assignment->isGroup()) {
            $assignment->load(['groups.members', 'groups.submission.student', 'groups.submission.rubricScores']);
            $groups = $assignment->groups->sortBy('name')->values();

            $groupedIds = $groups->pluck('members')->flatten()->pluck('id')->unique();
            $ungrouped = $assignment->course->students->whereNotIn('id', $groupedIds)->sort(User::compareByNim(...))->values();

            $submittedGroups = $groups->filter(fn ($g) => $g->submission && $g->submission->submitted_at);
            $stats = [
                'total' => $groups->count(),
                'submitted' => $submittedGroups->count(),
                'late' => $groups->filter(fn ($g) => $g->submission?->isLate())->count(),
                'graded' => $groups->filter(fn ($g) => $g->submission?->isGraded())->count(),
                'pending' => $ungrouped->count(),
                'pct' => $groups->count() > 0 ? (int) round($submittedGroups->count() / $groups->count() * 100) : 0,
            ];

            // Untuk modal nilai/preview & tombol unduh (dipakai bersama layout): pengumpulan kelompok.
            $submissions = $groups->map(fn ($g) => $g->submission)->filter()->values();
            $pending = $ungrouped; // "belum mengumpulkan" = mahasiswa belum berkelompok

            return view('assignments.show-dosen', compact('assignment', 'groups', 'ungrouped', 'stats', 'submissions', 'pending'));
        }

        $submissions = $assignment->submissions()
            ->with('student', 'rubricScores')
            ->get()
            ->sort(fn ($a, $b) => User::compareByNim($a->student, $b->student));

        $pending = $assignment->course->students
            ->whereNotIn('id', $submissions->pluck('user_id'))
            ->sort(User::compareByNim(...))
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

    /** Halaman tugas untuk mahasiswa — sertakan konteks kelompok bila tugas kelompok. */
    private function showMahasiswa(Assignment $assignment, User $user): View
    {
        $submission = $assignment->submissionFor($user);
        $myGroup = null;
        $groupmateCandidates = collect();

        if ($assignment->isGroup()) {
            $assignment->load('course.students');
            $myGroup = $assignment->groupFor($user);
            $myGroup?->load('members', 'submission');

            // Calon anggota = peserta kelas yang belum masuk kelompok mana pun pada tugas ini.
            $groupedIds = $assignment->groups()->with('members:id')->get()
                ->pluck('members')->flatten()->pluck('id')->unique();
            $groupmateCandidates = $assignment->course->students
                ->whereNotIn('id', $groupedIds)
                ->where('id', '!=', $user->id)
                ->sort(User::compareByNim(...))
                ->values();
        }

        return view('assignments.show-mahasiswa', compact('assignment', 'submission', 'myGroup', 'groupmateCandidates'));
    }

    private function showQuiz(Request $request, Assignment $assignment): View
    {
        $user = $request->user();

        if ($assignment->isExternalQuiz()) {
            if ($user->isDosen()) {
                $assignment->load('course.students');
                $submissions = $assignment->submissions()->get()->keyBy('user_id');

                return view('quizzes.show-external-dosen', compact('assignment', 'submissions'));
            }

            $submission = $assignment->submissionFor($user);

            return view('quizzes.show-external-mahasiswa', compact('assignment', 'submission'));
        }

        if ($user->isDosen()) {
            $assignment->loadCount('questions', 'submissions');
            $submissions = $assignment->submissions()->with('student')->get()->sort(fn ($a, $b) => User::compareByNim($a->student, $b->student));

            return view('quizzes.show-dosen', compact('assignment', 'submissions'));
        }

        $submission = $assignment->submissionFor($user);

        return view('quizzes.show-mahasiswa', compact('assignment', 'submission'));
    }

    private function validateData(Request $request, Course $course, ?Assignment $assignment = null): array
    {
        if ($request->input('type') === Assignment::TYPE_KUIS && ! $request->filled('delivery_mode')) {
            // Permintaan dari form/klien lama tetap membuat kuis internal.
            $request->merge(['delivery_mode' => Assignment::DELIVERY_INTERNAL]);
        }

        $rules = [
            'meeting_id' => ['required', 'integer', Rule::exists('meetings', 'id')->where('course_id', $course->id)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'in:tugas,kuis'],
            'deadline' => ['nullable', 'date'],
            'max_score' => ['required', 'integer', 'min:1', 'max:1000'],
            'grade_component_id' => ['nullable', 'integer', Rule::exists('grade_components', 'id')->where('course_id', $course->id)],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:600'],
        ];

        if ($request->input('type') === Assignment::TYPE_KUIS) {
            $rules['delivery_mode'] = ['required', Rule::in([Assignment::DELIVERY_INTERNAL, Assignment::DELIVERY_EXTERNAL])];
            $rules['external_url'] = [
                'nullable',
                'required_if:delivery_mode,'.Assignment::DELIVERY_EXTERNAL,
                'url',
                'max:2048',
                'starts_with:http://,https://',
            ];
        }

        // Bentuk jawaban & bentuk pengerjaan hanya untuk tugas (kuis punya alur soal sendiri).
        if ($request->input('type') !== Assignment::TYPE_KUIS) {
            $rules['submission_mode'] = ['required', Rule::in(array_keys(Assignment::SUBMISSION_MODES))];
            $rules['mode'] = ['required', 'in:individu,kelompok'];
            $rules['group_max'] = ['nullable', 'integer', 'min:2', 'max:20', 'required_if:mode,kelompok'];
            $rules['instruction_file'] = [
                'nullable', 'file', 'max:20480',
                'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,csv,txt,jpg,jpeg,png,zip',
            ];
            $rules['remove_instruction_file'] = ['nullable', 'boolean'];
        } else {
            $rules['instruction_file'] = ['prohibited'];
            $rules['remove_instruction_file'] = ['prohibited'];
        }

        $data = $request->validate($rules, [
            'instruction_file.mimes' => 'Format lampiran harus PDF, Word, PowerPoint, Excel, CSV, TXT, gambar JPG/PNG, atau ZIP.',
            'instruction_file.max' => 'Ukuran lampiran instruksi maksimal 20 MB.',
            'instruction_file.prohibited' => 'Lampiran instruksi hanya tersedia untuk tugas, bukan kuis.',
            'remove_instruction_file.prohibited' => 'Lampiran instruksi hanya tersedia untuk tugas, bukan kuis.',
        ]);
        unset($data['instruction_file'], $data['remove_instruction_file']);

        if (($data['type'] ?? null) === Assignment::TYPE_KUIS) {
            $data['submission_mode'] = Assignment::SUBMISSION_FILE;
            $data['mode'] = Assignment::MODE_INDIVIDU; // kuis selalu individu

            if (($data['delivery_mode'] ?? Assignment::DELIVERY_INTERNAL) === Assignment::DELIVERY_EXTERNAL) {
                $data['duration_minutes'] = null;
            } else {
                $data['external_url'] = null;
            }
        } else {
            $data['delivery_mode'] = Assignment::DELIVERY_INTERNAL;
            $data['external_url'] = null;
        }

        if ($assignment
            && $assignment->isQuiz()
            && $assignment->delivery_mode !== ($data['delivery_mode'] ?? Assignment::DELIVERY_INTERNAL)
            && ($assignment->questions()->exists() || $assignment->submissions()->exists())) {
            throw ValidationException::withMessages([
                'delivery_mode' => 'Metode kuis tidak dapat diubah setelah soal atau hasil peserta tersimpan.',
            ]);
        }

        if (($data['mode'] ?? Assignment::MODE_INDIVIDU) !== Assignment::MODE_KELOMPOK) {
            $data['group_max'] = null;
        }

        return $data;
    }

    private function instructionFileData(UploadedFile $file, string $path): array
    {
        $name = basename(str_replace('\\', '/', $file->getClientOriginalName()));
        $name = preg_replace('/[\x00-\x1F\x7F]/u', '', $name) ?: 'lampiran-instruksi';

        return [
            'instruction_file_path' => $path,
            'instruction_file_name' => Str::limit($name, 255, ''),
            'instruction_file_mime' => Str::limit((string) $file->getMimeType(), 255, ''),
            'instruction_file_size' => $file->getSize(),
        ];
    }
}
