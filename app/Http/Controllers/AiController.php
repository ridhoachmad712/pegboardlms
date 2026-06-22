<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ChecksCourseAccess;
use App\Models\Assignment;
use App\Models\Material;
use App\Models\Meeting;
use App\Services\AiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;
use Throwable;

class AiController extends Controller
{
    use ChecksCourseAccess;

    public function __construct(private AiService $claude) {}

    /** Ringkasan AI untuk materi PDF. */
    public function summarizeMaterial(Request $request, Material $material): RedirectResponse
    {
        $material->load('meeting.course');
        $this->ensureCourseOwner($request, $material->meeting->course);

        if (! $this->claude->isConfigured()) {
            return back()->with('error', 'Fitur AI belum aktif. Atur provider & API key di Pengaturan → Integrasi AI.');
        }

        $text = $this->extractPdfText($material);
        if ($text === null) {
            return back()->with('error', 'Ringkasan AI hanya untuk materi berupa berkas PDF.');
        }

        try {
            $material->update(['summary' => $this->claude->summarize($text)]);
        } catch (Throwable $e) {
            return back()->with('error', 'Gagal membuat ringkasan: '.$e->getMessage());
        }

        return back()->with('status', 'Ringkasan AI berhasil dibuat.');
    }

    /** Susun materi kuliah otomatis (AI) untuk satu pertemuan, lalu simpan sebagai materi teks. */
    public function generateMaterial(Request $request, Meeting $meeting): RedirectResponse
    {
        $meeting->load('course.syllabus', 'materials');
        $this->ensureCourseOwner($request, $meeting->course);

        if (! $this->claude->isConfigured()) {
            return back()->with('error', 'Fitur AI belum aktif. Atur provider & API key di Pengaturan → Integrasi AI.');
        }

        // Kumpulkan teks dari materi PDF yang sudah ada di pertemuan ini sebagai konteks
        $sources = '';
        foreach ($meeting->materials as $m) {
            if ($text = $this->extractPdfText($m)) {
                $sources .= "\n\n=== {$m->title} ===\n".$text;
            }
        }

        $syllabus = $meeting->course->syllabus;

        try {
            $content = $this->claude->generateMaterial([
                'course' => $meeting->course->name,
                'topic' => $meeting->topic,
                'cpl' => $syllabus?->cpl,
                'cpmk' => $syllabus?->cpmk,
                'sub_cpmk' => $syllabus?->sub_cpmk,
                'description' => $syllabus?->description,
                'references' => $syllabus?->references,
                'sources' => trim($sources) ?: null,
            ]);
        } catch (Throwable $e) {
            return back()->with('error', 'Gagal membuat materi: '.$e->getMessage());
        }

        if (trim($content) === '') {
            return back()->with('error', 'AI tidak menghasilkan materi. Coba lagi.');
        }

        $meeting->materials()->create([
            'title' => 'Materi (AI) — '.$meeting->topic,
            'type' => Material::TYPE_TEXT,
            'content' => $content,
        ]);

        return back()->with('status', 'Materi berhasil dibuat AI. Periksa & sunting bila perlu.');
    }

    /** Generate soal kuis dari materi (PDF) atau teks yang ditempel. */
    public function generateQuestions(Request $request, Assignment $assignment): RedirectResponse
    {
        $assignment->load('course');
        $this->ensureCourseOwner($request, $assignment->course);
        abort_unless($assignment->isQuiz(), 404);

        if (! $this->claude->isConfigured()) {
            return back()->with('error', 'Fitur AI belum aktif. Atur provider & API key di Pengaturan → Integrasi AI.');
        }

        $data = $request->validate([
            'count' => ['required', 'integer', 'min:1', 'max:10'],
            'material_id' => ['nullable', 'integer', 'exists:materials,id'],
            'source_text' => ['nullable', 'string'],
        ]);

        // Sumber teks: materi PDF dari kelas yang sama, atau teks tempel
        $text = trim($data['source_text'] ?? '');
        if ($data['material_id'] ?? null) {
            $material = Material::with('meeting.course')->find($data['material_id']);
            abort_unless($material && $material->meeting->course->id === $assignment->course_id, 403);
            $text = $this->extractPdfText($material) ?? '';
        }

        if ($text === '') {
            return back()->with('error', 'Sediakan materi PDF atau tempel teks sumber.');
        }

        try {
            $questions = $this->claude->generateQuestions($text, $data['count']);
        } catch (Throwable $e) {
            return back()->with('error', 'Gagal generate soal: '.$e->getMessage());
        }

        if (empty($questions)) {
            return back()->with('error', 'AI tidak menghasilkan soal yang valid. Coba lagi.');
        }

        foreach ($questions as $q) {
            $assignment->questions()->create([
                'type' => 'pg',
                'question' => $q['question'],
                'options' => $q['options'],
                'correct_answer' => $q['correct_answer'],
                'points' => $q['points'],
            ]);
        }

        return back()->with('status', count($questions).' soal berhasil dibuat oleh AI.');
    }

    /** Ekstrak teks dari materi PDF; null jika bukan PDF. */
    private function extractPdfText(Material $material): ?string
    {
        if (! $material->isFile() || ! $material->path) {
            return null;
        }
        if (! str_ends_with(strtolower($material->path), '.pdf')) {
            return null;
        }

        $fullPath = Storage::disk('public')->path($material->path);
        if (! is_file($fullPath)) {
            return null;
        }

        $parser = new Parser();
        $pdf = $parser->parseFile($fullPath);

        return $pdf->getText();
    }
}
