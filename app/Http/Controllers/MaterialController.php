<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\Meeting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MaterialController extends Controller
{
    /** Tambah materi: berupa file upload atau link/video. */
    public function store(Request $request, Meeting $meeting): RedirectResponse
    {
        $this->authorizeOwner($request, $meeting);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:file,link,video'],
            'file' => ['required_if:type,file', 'nullable', 'file', 'max:20480', // 20 MB
                'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx'],
            'url' => ['required_unless:type,file', 'nullable', 'url', 'max:2048'],
        ], [
            'file.mimes' => 'Tipe berkas harus PDF, Word, PowerPoint, atau Excel.',
            'file.max' => 'Ukuran berkas maksimal 20 MB.',
        ]);

        $attributes = [
            'title' => $validated['title'],
            'type' => $validated['type'],
        ];

        if ($validated['type'] === Material::TYPE_FILE) {
            $file = $request->file('file');
            $path = $file->store('materials/'.$meeting->course_id, 'public');

            $attributes['path'] = $path;
            $attributes['mime'] = $file->getClientMimeType();
            $attributes['size'] = $file->getSize();
        } else {
            $attributes['url'] = $validated['url'];
        }

        $meeting->materials()->create($attributes);

        return back()->with('status', 'Materi berhasil ditambahkan.');
    }

    public function update(Request $request, Material $material): RedirectResponse
    {
        $this->authorizeOwner($request, $material->meeting);

        $rules = ['title' => ['required', 'string', 'max:255']];
        if ($material->isText()) {
            $rules['content'] = ['nullable', 'string'];
        } elseif (! $material->isFile()) {
            $rules['url'] = ['required', 'url', 'max:2048'];
        }

        $material->update($request->validate($rules));

        return back()->with('status', 'Materi berhasil diperbarui.');
    }

    /** Sajikan berkas secara inline (Content-Disposition: inline) untuk dipreview di aplikasi. */
    public function preview(Request $request, Material $material): StreamedResponse|RedirectResponse
    {
        $this->authorizeAccess($request, $material);

        if (! $material->isFile() || ! $material->path) {
            return redirect()->away($material->url);
        }

        $disk = Storage::disk('public');
        abort_unless($disk->exists($material->path), 404, 'Berkas tidak ditemukan.');

        // response() memakai disposisi "inline" sehingga browser menampilkan, bukan mengunduh.
        return $disk->response($material->path, null, ['Content-Type' => $material->mime ?: 'application/pdf']);
    }

    public function download(Request $request, Material $material): StreamedResponse|RedirectResponse
    {
        $this->authorizeAccess($request, $material);

        if (! $material->isFile() || ! $material->path) {
            return redirect()->away($material->url);
        }

        $disk = Storage::disk('public');
        abort_unless($disk->exists($material->path), 404, 'Berkas tidak ditemukan.');

        $ext = pathinfo($material->path, PATHINFO_EXTENSION);
        $filename = Str::slug($material->title).($ext ? '.'.$ext : '');

        return $disk->download($material->path, $filename);
    }

    public function destroy(Request $request, Material $material): RedirectResponse
    {
        $this->authorizeOwner($request, $material->meeting);

        if ($material->isFile() && $material->path) {
            Storage::disk('public')->delete($material->path);
        }

        $material->delete();

        return back()->with('status', 'Materi berhasil dihapus.');
    }

    // --- Helpers ---

    private function authorizeOwner(Request $request, Meeting $meeting): void
    {
        abort_unless($meeting->course->user_id === $request->user()->id, 403);

        if (! $request->isMethodSafe() && $meeting->course->isCompleted()) {
            abort(403, 'Kelas ini sudah selesai (read-only). Buka kembali untuk mengubah.');
        }
    }

    /** Dosen pemilik atau mahasiswa terdaftar boleh mengakses. */
    private function authorizeAccess(Request $request, Material $material): void
    {
        $user = $request->user();
        $course = $material->meeting->course;

        if ($user->isDosen() && $course->user_id === $user->id) {
            return;
        }

        if ($user->isMahasiswa() && $course->students()->whereKey($user->id)->exists()) {
            return;
        }

        abort(403);
    }
}
