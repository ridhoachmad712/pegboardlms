<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ChecksCourseAccess;
use App\Models\Course;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class SyllabusController extends Controller
{
    use ChecksCourseAccess;

    public function show(Request $request, Course $course): View
    {
        $this->ensureCourseAccess($request, $course);
        $course->load('syllabus', 'meetings', 'lecturer');

        return view('syllabus.show', compact('course'));
    }

    public function edit(Request $request, Course $course): View
    {
        $this->ensureCourseOwner($request, $course);
        $syllabus = $course->syllabus;

        return view('syllabus.edit', compact('course', 'syllabus'));
    }

    public function update(Request $request, Course $course): RedirectResponse
    {
        $this->ensureCourseOwner($request, $course);

        $data = [
            // Field bernomor (array baris) → teks per baris; sisanya teks biasa.
            'cpl' => \App\Models\Syllabus::listToText($request->input('cpl')),
            'cpmk' => \App\Models\Syllabus::listToText($request->input('cpmk')),
            'sub_cpmk' => \App\Models\Syllabus::listToText($request->input('sub_cpmk')),
            'references' => \App\Models\Syllabus::listToText($request->input('references')),
            'description' => \App\Models\Syllabus::listToText($request->input('description')),
            'assessment' => \App\Models\Syllabus::listToText($request->input('assessment')),
            'rules' => \App\Models\Syllabus::listToText($request->input('rules')),
        ];

        $course->syllabus()->updateOrCreate(['course_id' => $course->id], $data);

        return redirect()->route('syllabus.show', $course)->with('status', 'RPS berhasil disimpan.');
    }

    public function pdf(Request $request, Course $course): Response
    {
        $this->ensureCourseAccess($request, $course);
        $course->load('syllabus', 'meetings', 'lecturer');

        $pdf = Pdf::loadView('syllabus.pdf', compact('course'))->setPaper('a4');

        return $pdf->download('RPS-'.\Illuminate\Support\Str::slug($course->name).'.pdf');
    }
}
