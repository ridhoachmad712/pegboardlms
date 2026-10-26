<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Meeting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class MeetingController extends Controller
{
    /** Batas maksimal pertemuan per kelas (1 semester). */
    public const MAX_MEETINGS = 16;

    public function store(Request $request, Course $course): RedirectResponse
    {
        $this->authorizeOwner($request, $course);

        if ($course->meetings()->count() >= self::MAX_MEETINGS) {
            return back()->with('error', 'Maksimal '.self::MAX_MEETINGS.' pertemuan per kelas. Hapus salah satu pertemuan jika ingin menambah.');
        }

        $data = $request->validate([
            'number' => ['required', 'integer', 'min:1', 'max:'.self::MAX_MEETINGS],
            'topic' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:tatap_muka,mandiri'],
            'location' => ['nullable', 'string', 'max:255'],
            'date' => ['nullable', 'date'],
            'attend_opens_at' => ['nullable', 'date'],
            'attend_closes_at' => ['nullable', 'date', 'after_or_equal:attend_opens_at'],
            'description' => ['nullable', 'string'],
        ]);

        if ($data['type'] === Meeting::TYPE_MANDIRI) {
            $data['location'] = null; // pertemuan mandiri tak punya lokasi fisik
        }

        $course->meetings()->create($data);

        return back()->with('status', 'Pertemuan berhasil ditambahkan.');
    }

    /**
     * Buat banyak pertemuan sekaligus, satu per minggu mulai tanggal pertama.
     * Nomor yang sudah ada dilewati sehingga aman dipakai untuk melengkapi jadwal.
     */
    public function generate(Request $request, Course $course): RedirectResponse
    {
        $this->authorizeOwner($request, $course);

        $data = $request->validate([
            'count' => ['required', 'integer', 'min:1', 'max:'.self::MAX_MEETINGS],
            'first_date' => ['required', 'date'],
            'interval_days' => ['required', 'integer', 'min:1', 'max:31'],
            'type' => ['required', 'in:tatap_muka,mandiri'],
            'location' => ['nullable', 'string', 'max:255'],
            'topics' => ['nullable', 'string'],
            'exams' => ['nullable', 'boolean'],
        ]);

        $topics = collect(preg_split('/\R/', (string) ($data['topics'] ?? '')))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values();
        $existing = $course->meetings()->pluck('number')->all();
        $start = Carbon::parse($data['first_date'])->startOfDay();
        $created = 0;

        for ($number = 1; $number <= $data['count']; $number++) {
            if (in_array($number, $existing, true)) {
                continue;
            }

            $topic = $topics->get($number - 1);
            if (! $topic && ($data['exams'] ?? false)) {
                $topic = match ($number) {
                    8 => 'Ujian Tengah Semester (UTS)',
                    16 => 'Ujian Akhir Semester (UAS)',
                    default => null,
                };
            }

            $course->meetings()->create([
                'number' => $number,
                'topic' => Str::limit($topic ?: 'Pertemuan '.$number, 255, ''),
                'type' => $data['type'],
                'location' => $data['type'] === Meeting::TYPE_MANDIRI ? null : ($data['location'] ?? null),
                'date' => $start->copy()->addDays(($number - 1) * $data['interval_days']),
            ]);
            $created++;
        }

        if ($created === 0) {
            return back()->with('error', 'Tidak ada pertemuan baru: pertemuan 1–'.$data['count'].' sudah ada semua.');
        }

        return back()->with('status', $created.' pertemuan berhasil dibuat. Materi hanya terlihat mahasiswa mulai tanggal pertemuannya.');
    }

    public function update(Request $request, Meeting $meeting): RedirectResponse
    {
        $this->authorizeOwner($request, $meeting->course);

        $data = $request->validate([
            'number' => ['required', 'integer', 'min:1', 'max:'.self::MAX_MEETINGS],
            'topic' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:tatap_muka,mandiri'],
            'location' => ['nullable', 'string', 'max:255'],
            'date' => ['nullable', 'date'],
            'attend_opens_at' => ['nullable', 'date'],
            'attend_closes_at' => ['nullable', 'date', 'after_or_equal:attend_opens_at'],
            'description' => ['nullable', 'string'],
        ]);

        if ($data['type'] === Meeting::TYPE_MANDIRI) {
            $data['location'] = null;
        }

        $meeting->update($data);

        return back()->with('status', 'Pertemuan berhasil diperbarui.');
    }

    public function destroy(Request $request, Meeting $meeting): RedirectResponse
    {
        $this->authorizeOwner($request, $meeting->course);

        $meeting->delete();

        return back()->with('status', 'Pertemuan berhasil dihapus.');
    }

    private function authorizeOwner(Request $request, Course $course): void
    {
        abort_unless($course->user_id === $request->user()->id, 403);

        if (! $request->isMethodSafe() && $course->isCompleted()) {
            abort(403, 'Kelas ini sudah selesai (read-only). Buka kembali untuk mengubah.');
        }
    }
}
