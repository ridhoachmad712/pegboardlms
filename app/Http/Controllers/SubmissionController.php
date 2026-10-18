<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ChecksCourseAccess;
use App\Models\Assignment;
use App\Models\RubricScore;
use App\Models\Submission;
use App\Models\User;
use App\Services\Notifier;
use App\Support\Grades;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubmissionController extends Controller
{
    use ChecksCourseAccess;

    /** Mahasiswa submit berkas tugas. */
    public function store(Request $request, Assignment $assignment): RedirectResponse
    {
        $assignment->load('course');
        $this->ensureCourseAccess($request, $assignment->course);

        $user = $request->user();
        abort_if($user->isDosen(), 403);
        abort_if($assignment->isQuiz(), 404);

        // Tugas kelompok: pengumpulan ditautkan ke kelompok si mahasiswa (dipakai bersama).
        $group = null;
        if ($assignment->isGroup()) {
            $group = $assignment->groupFor($user);
            if (! $group) {
                return back()->with('error', 'Buat atau gabung kelompok dulu sebelum mengumpulkan tugas.');
            }
        }

        $existing = $assignment->submissionFor($user);
        // Boleh ganti selama belum dinilai. Kalau sudah dinilai, tidak boleh.
        abort_if($existing && $existing->isGraded(), 403, 'Tugas sudah dinilai, tidak bisa diubah.');

        // Aturan validasi mengikuti bentuk jawaban yang diatur dosen.
        $rules = [];
        if ($assignment->allowsFile()) {
            $fileRequired = $assignment->submission_mode === Assignment::SUBMISSION_FILE
                && ! $existing?->hasFiles();
            $rules['files'] = [
                $fileRequired ? 'required' : 'nullable',
                'array', 'max:2',
            ];
            $rules['files.*'] = [
                'file', 'max:20480',
                'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,csv,txt,jpg,jpeg,png,zip',
            ];
        }
        if ($assignment->allowsText()) {
            $rules['answer_text'] = [
                $assignment->submission_mode === Assignment::SUBMISSION_TEXT ? 'required' : 'nullable',
                'string', 'max:20000',
            ];
        }

        $request->validate($rules, [
            'files.required' => 'Unggah minimal satu berkas.',
            'files.max' => 'Maksimal dua berkas dalam satu pengumpulan.',
            'files.*.mimes' => 'Format berkas harus PDF, Word, PowerPoint, Excel, CSV, TXT, gambar JPG/PNG, atau ZIP.',
            'files.*.max' => 'Ukuran setiap berkas maksimal 20 MB.',
            'answer_text.required' => 'Jawaban tidak boleh kosong.',
        ]);

        // Mode "keduanya": minimal salah satu (teks atau berkas) harus diisi.
        if ($assignment->submission_mode === Assignment::SUBMISSION_BOTH
            && ! $request->filled('answer_text') && ! $request->hasFile('files') && ! $existing?->hasFiles()) {
            throw ValidationException::withMessages([
                'answer_text' => 'Isi jawaban teks atau unggah berkas (minimal salah satu).',
            ]);
        }

        $status = $assignment->isPastDeadline() ? 'late' : 'ontime';
        $payload = ['status' => $status, 'submitted_at' => now()];
        $oldPaths = [];

        if ($assignment->allowsText()) {
            $payload['answer_text'] = $request->input('answer_text');
        }

        if ($assignment->allowsFile() && $request->hasFile('files')) {
            $uploaded = collect($request->file('files'))->take(2)->values();
            $newPaths = [];

            try {
                foreach ($uploaded as $file) {
                    $newPaths[] = $file->store('submissions/'.$assignment->id, 'public');
                }
            } catch (\Throwable $exception) {
                Storage::disk('public')->delete($newPaths);
                throw $exception;
            }

            $oldPaths = $existing ? array_values($existing->filePaths()) : [];
            $payload['file_path'] = $newPaths[0] ?? null;
            $payload['file_path_2'] = $newPaths[1] ?? null;
        }

        if ($existing) {
            $existing->update($payload);
            Storage::disk('public')->delete($oldPaths);

            return back()->with('status', 'Pengumpulan berhasil diperbarui.');
        }

        $payload['user_id'] = $user->id;
        if ($group) {
            $payload['assignment_group_id'] = $group->id;
        }
        $assignment->submissions()->create($payload);

        return back()->with('status', $group ? 'Tugas kelompok dikumpulkan — terlihat oleh semua anggota.' : 'Tugas berhasil dikumpulkan.');
    }

    /** Mahasiswa menghapus/membatalkan pengumpulannya sendiri (selama belum dinilai). */
    public function destroy(Request $request, Submission $submission): RedirectResponse
    {
        $submission->load('assignment.course');
        $this->ensureCourseAccess($request, $submission->assignment->course);

        $user = $request->user();
        abort_unless($this->studentOwns($submission, $user), 403);
        abort_if($submission->assignment->isQuiz(), 404);
        abort_if($submission->isGraded(), 403, 'Tugas sudah dinilai, tidak bisa dihapus.');

        Storage::disk('public')->delete(array_values($submission->filePaths()));
        $submission->delete();

        return back()->with('status', 'Pengumpulan dihapus. Anda dapat mengumpulkan ulang.');
    }

    /** Dosen membuka kembali pengumpulan agar mahasiswa bisa kumpul ulang. */
    public function reopen(Request $request, Submission $submission): RedirectResponse
    {
        $submission->load('assignment.course');
        $this->ensureCourseOwner($request, $submission->assignment->course);

        $assignment = $submission->assignment;
        $recipients = $assignment->isGroup() && $submission->assignment_group_id
            ? $submission->group()->first()?->members()->pluck('users.id')->all() ?? []
            : [$submission->user_id];

        Storage::disk('public')->delete(array_values($submission->filePaths()));
        $submission->delete();

        foreach ($recipients as $userId) {
            Notifier::toUser(
                $userId,
                'revision',
                'Tugas perlu dikumpulkan ulang',
                $assignment->title.' dibuka kembali oleh dosen.',
                route('assignments.show', $assignment),
            );
        }

        return back()->with('status', 'Pengumpulan dibuka kembali — mahasiswa dapat mengumpulkan ulang.');
    }

    /** Dosen unduh semua berkas pengumpulan sebagai ZIP. */
    public function downloadAll(Request $request, Assignment $assignment)
    {
        $assignment->load('course');
        $this->ensureCourseOwner($request, $assignment->course);

        $subs = $assignment->submissions()->with('student')
            ->where(fn ($query) => $query->whereNotNull('file_path')->orWhereNotNull('file_path_2'))
            ->get();
        abort_if($subs->isEmpty(), 404, 'Belum ada berkas untuk diunduh.');

        $disk = Storage::disk('public');
        $tmp = storage_path('app/zip-'.uniqid().'.zip');
        $zip = new \ZipArchive;
        $zip->open($tmp, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        foreach ($subs as $sub) {
            foreach ($sub->filePaths() as $slot => $path) {
                if (! $disk->exists($path)) {
                    continue;
                }
                $ext = pathinfo($path, PATHINFO_EXTENSION);
                $base = Str::slug(($sub->student->nim_nip ?: 'mhs').'-'.$sub->student->name);
                $name = $base.'-berkas-'.$slot.($ext ? '.'.$ext : '');
                $zip->addFile($disk->path($path), $name);
            }
        }
        $zip->close();

        return response()->download($tmp, 'tugas-'.Str::slug($assignment->title).'.zip')->deleteFileAfterSend(true);
    }

    /** Dosen input nilai + feedback. */
    public function grade(Request $request, Submission $submission): RedirectResponse
    {
        $submission->load('assignment.course');
        $this->ensureCourseOwner($request, $submission->assignment->course);
        $assignment = $submission->assignment;

        if ($assignment->hasRubric()) {
            // Nilai dihitung dari rubrik: jumlah poin tiap kriteria (di-clamp ke maks-nya).
            $request->validate([
                'rubric' => ['required', 'array'],
                'feedback' => ['nullable', 'string'],
            ]);

            $input = (array) $request->input('rubric', []);
            $total = 0;
            foreach ($assignment->rubricCriteria as $crit) {
                $pts = max(0, min((float) ($input[$crit->id] ?? 0), (float) $crit->max_points));
                $total += $pts;
                RubricScore::updateOrCreate(
                    ['submission_id' => $submission->id, 'rubric_criterion_id' => $crit->id],
                    ['points' => $pts],
                );
            }

            $submission->update([
                'score' => min($total, (float) $assignment->max_score),
                'feedback' => $request->input('feedback'),
            ]);
            $data = ['score' => $submission->score];
        } else {
            $data = $request->validate([
                'score' => ['required', 'numeric', 'min:0', 'max:'.$assignment->max_score],
                'feedback' => ['nullable', 'string'],
            ]);

            $submission->update($data);
        }

        // Tugas kelompok → beri tahu semua anggota; individu → hanya pengumpul.
        $recipients = $assignment->isGroup() && $submission->assignment_group_id
            ? $submission->group()->first()?->members()->pluck('users.id')->all() ?? []
            : [$submission->user_id];

        foreach ($recipients as $uid) {
            Notifier::toUser(
                $uid,
                'grade',
                'Nilai tugas tersedia',
                $submission->assignment->title.' telah dinilai: '.Grades::num($data['score']),
                route('assignments.show', $submission->assignment),
            );
        }

        return back()->with('status', 'Nilai tersimpan.'.($assignment->isGroup() ? ' Berlaku untuk semua anggota kelompok.' : ''));
    }

    /** Sajikan berkas pengumpulan secara inline agar bisa dipreview (dosen pemilik / mahasiswa pemilik). */
    public function preview(Request $request, Submission $submission, int $slot = 1): StreamedResponse
    {
        $submission->load('assignment.course');
        $user = $request->user();
        $isOwnerDosen = $user->isDosen() && $submission->assignment->course->user_id === $user->id;
        $isOwnerStudent = $this->studentOwns($submission, $user);
        abort_unless($isOwnerDosen || $isOwnerStudent, 403);

        $disk = Storage::disk('public');
        $path = $submission->filePath($slot);
        abort_unless($path && $disk->exists($path), 404);

        // Disposisi "inline" → browser menampilkan (PDF/gambar), bukan mengunduh.
        return $disk->response($path);
    }

    public function download(Request $request, Submission $submission, int $slot = 1): StreamedResponse
    {
        $submission->load('assignment.course');
        // Dosen pemilik atau mahasiswa pemilik submission
        $user = $request->user();
        $isOwnerDosen = $user->isDosen() && $submission->assignment->course->user_id === $user->id;
        $isOwnerStudent = $this->studentOwns($submission, $user);
        abort_unless($isOwnerDosen || $isOwnerStudent, 403);

        $disk = Storage::disk('public');
        $path = $submission->filePath($slot);
        abort_unless($path && $disk->exists($path), 404);

        $ext = pathinfo($path, PATHINFO_EXTENSION);
        $name = Str::slug($submission->assignment->title.'-'.$submission->student->name).'-berkas-'.$slot.($ext ? '.'.$ext : '');

        return $disk->download($path, $name);
    }

    /** Mahasiswa berhak atas pengumpulan: pengumpul itu sendiri, atau (tugas kelompok) sesama anggota. */
    private function studentOwns(Submission $submission, User $user): bool
    {
        if ($submission->user_id === $user->id) {
            return true;
        }

        return $submission->assignment_group_id
            && $submission->group()->first()?->hasMember($user->id);
    }
}
