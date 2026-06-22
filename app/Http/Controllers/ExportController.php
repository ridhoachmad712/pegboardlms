<?php

namespace App\Http\Controllers;

use App\Exports\AbsensiExport;
use App\Exports\NilaiExport;
use App\Http\Controllers\Concerns\ChecksCourseAccess;
use App\Models\Course;
use App\Services\GradeCalculator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ExportController extends Controller
{
    use ChecksCourseAccess;

    public function nilaiExcel(Request $request, Course $course)
    {
        $this->ensureCourseOwner($request, $course);

        return Excel::download(new NilaiExport($course), 'nilai-'.Str::slug($course->name).'.xlsx');
    }

    public function absensiExcel(Request $request, Course $course)
    {
        $this->ensureCourseOwner($request, $course);

        return Excel::download(new AbsensiExport($course), 'absensi-'.Str::slug($course->name).'.xlsx');
    }

    public function nilaiPdf(Request $request, Course $course)
    {
        $this->ensureCourseOwner($request, $course);

        $data = (new GradeCalculator())->forCourse($course);

        $pdf = Pdf::loadView('exports.nilai-pdf', [
            'course' => $course->load('lecturer'),
            'components' => $data['components'],
            'rows' => $data['rows'],
            'summary' => $data['summary'],
        ])->setPaper('a4', 'landscape');

        return $pdf->download('nilai-'.Str::slug($course->name).'.pdf');
    }
}
