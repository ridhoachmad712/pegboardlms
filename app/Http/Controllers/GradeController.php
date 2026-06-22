<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ChecksCourseAccess;
use App\Models\Course;
use App\Models\GradeScore;
use App\Services\GradeCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GradeController extends Controller
{
    use ChecksCourseAccess;

    public function index(Request $request, Course $course, GradeCalculator $calc): View
    {
        $this->ensureCourseAccess($request, $course);
        $user = $request->user();

        if ($user->isMahasiswa()) {
            $data = $calc->forStudent($course, $user);

            return view('grades.mahasiswa', [
                'course' => $course,
                'components' => $data['components'],
                'row' => $data['row'],
            ]);
        }

        $data = $calc->forCourse($course);

        // Peringatan alur (#2): tugas dinilai tapi tidak ditautkan ke komponen apa pun
        $unlinkedGraded = $course->assignments()
            ->whereNull('grade_component_id')
            ->whereHas('submissions', fn ($q) => $q->whereNotNull('score'))
            ->count();

        return view('grades.dosen', [
            'course' => $course,
            'components' => $data['components'],
            'rows' => $data['rows'],
            'summary' => $data['summary'],
            'autoComponentIds' => $data['autoComponentIds'],
            'unlinkedGraded' => $unlinkedGraded,
        ]);
    }

    /** Simpan nilai manual untuk komponen tanpa tugas online (mis. UTS/UAS kertas). */
    public function saveManual(Request $request, Course $course): RedirectResponse
    {
        $this->ensureCourseOwner($request, $course);

        $componentIds = $course->gradeComponents()->pluck('id')->all();
        $studentIds = $course->students()->pluck('users.id')->all();
        // Komponen yang sudah ditautkan tugas → otomatis, abaikan input manualnya
        $autoIds = $course->assignments()->whereNotNull('grade_component_id')
            ->pluck('grade_component_id')->unique()->map(fn ($v) => (int) $v)->all();

        foreach ((array) $request->input('scores', []) as $cid => $perStudent) {
            $cid = (int) $cid;
            if (! in_array($cid, $componentIds) || in_array($cid, $autoIds) || ! is_array($perStudent)) {
                continue;
            }

            foreach ($perStudent as $uid => $val) {
                $uid = (int) $uid;
                if (! in_array($uid, $studentIds)) {
                    continue;
                }

                $val = trim((string) $val);
                if ($val === '') {
                    GradeScore::where('grade_component_id', $cid)->where('user_id', $uid)->delete();

                    continue;
                }

                GradeScore::updateOrCreate(
                    ['grade_component_id' => $cid, 'user_id' => $uid],
                    ['score' => max(0, min(100, (float) $val))],
                );
            }
        }

        return back()->with('status', 'Nilai manual tersimpan.');
    }
}
