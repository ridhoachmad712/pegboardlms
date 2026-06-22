<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ChecksCourseAccess;
use App\Models\Course;
use App\Models\GradeComponent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class GradeComponentController extends Controller
{
    use ChecksCourseAccess;

    public function store(Request $request, Course $course): RedirectResponse
    {
        $this->ensureCourseOwner($request, $course);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'in:tugas,kuis,uts,uas,lainnya'],
            'weight' => ['required', 'integer', 'min:1', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $this->assertWeightWithin100($course, $data['weight']);

        $course->gradeComponents()->create($data);

        return back()->with('status', 'Komponen nilai ditambahkan.');
    }

    public function update(Request $request, GradeComponent $component): RedirectResponse
    {
        $this->ensureCourseOwner($request, $component->course);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'in:tugas,kuis,uts,uas,lainnya'],
            'weight' => ['required', 'integer', 'min:1', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $this->assertWeightWithin100($component->course, $data['weight'], $component->id);

        $component->update($data);

        return back()->with('status', 'Komponen nilai diperbarui.');
    }

    public function destroy(Request $request, GradeComponent $component): RedirectResponse
    {
        $this->ensureCourseOwner($request, $component->course);
        $component->delete();

        return back()->with('status', 'Komponen nilai dihapus.');
    }

    private function assertWeightWithin100(Course $course, int $weight, ?int $exceptId = null): void
    {
        $existing = $course->gradeComponents()
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->sum('weight');

        if ($existing + $weight > 100) {
            throw ValidationException::withMessages([
                'weight' => "Total bobot melebihi 100% (saat ini terpakai {$existing}%).",
            ]);
        }
    }
}
