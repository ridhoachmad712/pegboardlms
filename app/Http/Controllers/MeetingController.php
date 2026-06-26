<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Meeting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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
            'description' => ['nullable', 'string'],
        ]);

        if ($data['type'] === Meeting::TYPE_MANDIRI) {
            $data['location'] = null; // pertemuan mandiri tak punya lokasi fisik
        }

        $course->meetings()->create($data);

        return back()->with('status', 'Pertemuan berhasil ditambahkan.');
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
