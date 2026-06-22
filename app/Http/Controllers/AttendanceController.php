<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ChecksCourseAccess;
use App\Models\Attendance;
use App\Models\Course;
use App\Models\Meeting;
use App\Services\AttendanceService;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    use ChecksCourseAccess;

    /** Rekap kehadiran kelas (grid) untuk dosen; daftar pribadi untuk mahasiswa. */
    public function index(Request $request, Course $course, AttendanceService $service): View
    {
        $this->ensureCourseAccess($request, $course);

        $grid = $service->gridForCourse($course);

        return view('attendance.index', [
            'course' => $course,
            'grid' => $grid,
            'isDosen' => $request->user()->isDosen(),
            'me' => $request->user(),
        ]);
    }

    /** Halaman sesi absensi satu pertemuan (dosen): QR + edit manual. */
    public function session(Request $request, Meeting $meeting): View
    {
        $this->ensureCourseOwner($request, $meeting->course);

        $token = $meeting->activeToken();
        $qr = null;
        if ($token) {
            $url = route('attendance.attend', $token->token);
            $qr = $this->qrDataUri($url);
        }

        $students = $meeting->course->students()->orderBy('name')->get();
        $attendances = $meeting->attendances()->get()->keyBy('user_id');

        return view('attendance.session', compact('meeting', 'token', 'qr', 'students', 'attendances'));
    }

    /** Mulai/refresh sesi absensi → buat token UUID kedaluwarsa 15 menit. */
    public function start(Request $request, Meeting $meeting): RedirectResponse
    {
        $this->ensureCourseOwner($request, $meeting->course);

        $meeting->tokens()->create([
            'token' => (string) Str::uuid(),
            'code' => \App\Models\AttendanceToken::generateCode(),
            'expires_at' => now()->addMinutes(15),
        ]);

        return redirect()->route('attendance.session', $meeting)
            ->with('status', 'Sesi absensi dimulai. QR berlaku 15 menit.');
    }

    /** Mahasiswa scan QR → /attend/{token}. */
    public function attend(Request $request, string $token): View
    {
        $user = $request->user();
        $input = trim($token);
        // Terima UUID dari QR maupun kode absen pendek (6 karakter) yang diketik manual.
        $record = \App\Models\AttendanceToken::where('token', $input)->first()
            ?? \App\Models\AttendanceToken::where('code', strtoupper($input))->latest()->first();

        if (! $record) {
            return view('attendance.attend', ['ok' => false, 'message' => 'Token absensi tidak valid.']);
        }

        $meeting = $record->meeting()->with('course')->first();

        if ($record->isExpired()) {
            return view('attendance.attend', ['ok' => false, 'message' => 'Token absensi sudah kedaluwarsa.', 'meeting' => $meeting]);
        }

        if (! $user->isMahasiswa() || ! $meeting->course->students()->whereKey($user->id)->exists()) {
            return view('attendance.attend', ['ok' => false, 'message' => 'Anda tidak terdaftar di kelas ini.', 'meeting' => $meeting]);
        }

        $att = Attendance::firstOrNew(['meeting_id' => $meeting->id, 'user_id' => $user->id]);
        $already = $att->exists;
        if (! $already) {
            $att->fill(['status' => 'hadir', 'method' => 'qr'])->save();
        }

        return view('attendance.attend', [
            'ok' => true,
            'message' => $already ? 'Anda sudah tercatat hadir sebelumnya.' : 'Kehadiran berhasil dicatat!',
            'meeting' => $meeting,
        ]);
    }

    /** Dosen edit status kehadiran manual untuk satu pertemuan. */
    public function updateStatus(Request $request, Meeting $meeting): RedirectResponse
    {
        $this->ensureCourseOwner($request, $meeting->course);

        $statuses = $request->input('statuses', []); // [user_id => status]
        $enrolledIds = $meeting->course->students()->pluck('users.id')->all();

        foreach ($statuses as $userId => $status) {
            if (! in_array((int) $userId, $enrolledIds, true) || ! in_array($status, Attendance::STATUSES, true)) {
                continue;
            }
            Attendance::updateOrCreate(
                ['meeting_id' => $meeting->id, 'user_id' => $userId],
                ['status' => $status, 'method' => 'manual'],
            );
        }

        return back()->with('status', 'Kehadiran diperbarui.');
    }

    /** Hasilkan QR sebagai data-URI SVG. */
    private function qrDataUri(string $data): string
    {
        $result = (new Builder(
            writer: new SvgWriter(),
            data: $data,
            size: 280,
            margin: 10,
        ))->build();

        return $result->getDataUri();
    }
}
