<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ChecksCourseAccess;
use App\Models\Assignment;
use App\Models\RubricCriterion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RubricController extends Controller
{
    use ChecksCourseAccess;

    public function store(Request $request, Assignment $assignment): RedirectResponse
    {
        $this->ensureCourseOwner($request, $assignment->course);
        abort_if($assignment->isQuiz(), 404, 'Rubrik hanya untuk tugas.');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'max_points' => ['required', 'numeric', 'min:0.5', 'max:1000'],
        ]);

        $assignment->rubricCriteria()->create([
            'name' => $data['name'],
            'max_points' => $data['max_points'],
            'position' => (int) $assignment->rubricCriteria()->max('position') + 1,
        ]);

        return back()->with('status', 'Kriteria rubrik ditambahkan.');
    }

    public function update(Request $request, RubricCriterion $criterion): RedirectResponse
    {
        $this->ensureCourseOwner($request, $criterion->assignment->course);

        $criterion->update($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'max_points' => ['required', 'numeric', 'min:0.5', 'max:1000'],
        ]));

        return back()->with('status', 'Kriteria rubrik diperbarui.');
    }

    public function destroy(Request $request, RubricCriterion $criterion): RedirectResponse
    {
        $this->ensureCourseOwner($request, $criterion->assignment->course);
        $criterion->delete();

        return back()->with('status', 'Kriteria rubrik dihapus.');
    }
}
