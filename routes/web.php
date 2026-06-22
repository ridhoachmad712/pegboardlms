<?php

use App\Http\Controllers\Admin\ActivityController as AdminActivityController;
use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\StudentController as AdminStudentController;
use App\Http\Controllers\AiController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ProfileController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\ForumController;
use App\Http\Controllers\GradeComponentController;
use App\Http\Controllers\GradeController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\RubricController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SubmissionController;
use App\Http\Controllers\SyllabusController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

// --- Guest ---
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:6,1');
});

// --- Authenticated ---
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/dosen', [DashboardController::class, 'dosen'])
        ->middleware('role:dosen')->name('dashboard.dosen');
    Route::get('/dashboard/mahasiswa', [DashboardController::class, 'mahasiswa'])
        ->middleware('role:mahasiswa')->name('dashboard.mahasiswa');

    // Panduan penggunaan (per role, dibaca dari auth() di view)
    Route::view('/panduan', 'panduan')->name('panduan');

    // Kalender jadwal & pencarian global
    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar');
    Route::get('/search', [SearchController::class, 'index'])->name('search');

    // Profil & kata sandi
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // Gabung kelas via kode (mahasiswa)
    Route::get('/join', [EnrollmentController::class, 'showJoin'])->name('enrollments.join.show');
    Route::post('/join', [EnrollmentController::class, 'join'])->name('enrollments.join');

    // Kelas — index & show untuk kedua role (otorisasi di controller)
    Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
    Route::get('/courses/{course}', [CourseController::class, 'show'])->name('courses.show');

    // Materi — preview (inline) & download untuk kedua role
    Route::get('/materials/{material}/preview', [MaterialController::class, 'preview'])->name('materials.preview');
    Route::get('/materials/{material}/download', [MaterialController::class, 'download'])->name('materials.download');

    // ===== Notifikasi =====
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/count', [NotificationController::class, 'count'])->name('notifications.count');
    Route::get('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.readAll');

    // ===== Tugas & Kuis (kedua role; otorisasi di controller) =====
    Route::get('/courses/{course}/assignments', [AssignmentController::class, 'index'])->name('assignments.index');
    Route::get('/assignments/{assignment}', [AssignmentController::class, 'show'])->name('assignments.show');
    Route::post('/assignments/{assignment}/submit', [SubmissionController::class, 'store'])
        ->middleware('throttle:20,1')->name('submissions.store');
    Route::get('/submissions/{submission}/download', [SubmissionController::class, 'download'])->name('submissions.download');

    // Kuis — kerjakan & review (mahasiswa + dosen review)
    Route::get('/assignments/{assignment}/take', [QuizController::class, 'take'])->name('quizzes.take');
    Route::post('/assignments/{assignment}/take', [QuizController::class, 'submit'])
        ->middleware('throttle:20,1')->name('quizzes.submit');
    Route::get('/submissions/{submission}/review', [QuizController::class, 'review'])->name('quizzes.review');

    // ===== Penilaian (kedua role) =====
    Route::get('/courses/{course}/grades', [GradeController::class, 'index'])->name('grades.index');

    // ===== Pengumuman (kedua role lihat) =====
    Route::get('/courses/{course}/announcements', [AnnouncementController::class, 'index'])->name('announcements.index');

    // ===== Forum (kedua role) =====
    Route::get('/courses/{course}/forum', [ForumController::class, 'index'])->name('forum.index');
    Route::post('/courses/{course}/forum', [ForumController::class, 'storeThread'])->name('forum.threads.store');
    Route::get('/forum/{thread}', [ForumController::class, 'show'])->name('forum.show');
    Route::post('/forum/{thread}/replies', [ForumController::class, 'storeReply'])->name('forum.replies.store');
    Route::delete('/forum/{thread}', [ForumController::class, 'destroyThread'])->name('forum.threads.destroy');
    Route::delete('/forum-replies/{reply}', [ForumController::class, 'destroyReply'])->name('forum.replies.destroy');

    // ===== Kehadiran (kedua role) =====
    Route::get('/courses/{course}/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::get('/attend/{token}', [AttendanceController::class, 'attend'])->name('attendance.attend');

    // ===== Silabus / RPS (kedua role lihat & unduh) =====
    Route::get('/courses/{course}/syllabus', [SyllabusController::class, 'show'])->name('syllabus.show');
    Route::get('/courses/{course}/syllabus/pdf', [SyllabusController::class, 'pdf'])->name('syllabus.pdf');

    // --- Khusus dosen ---
    Route::middleware('role:dosen')->group(function () {
        // CRUD kelas
        Route::get('/courses-create', [CourseController::class, 'create'])->name('courses.create');
        Route::post('/courses', [CourseController::class, 'store'])->name('courses.store');
        Route::get('/courses/{course}/edit', [CourseController::class, 'edit'])->name('courses.edit');
        Route::put('/courses/{course}', [CourseController::class, 'update'])->name('courses.update');
        Route::delete('/courses/{course}', [CourseController::class, 'destroy'])->name('courses.destroy');
        Route::get('/courses-trash', [CourseController::class, 'trash'])->name('courses.trash');
        Route::patch('/courses/{id}/restore', [CourseController::class, 'restore'])->name('courses.restore');
        Route::delete('/courses/{id}/force', [CourseController::class, 'forceDestroy'])->name('courses.forceDestroy');
        Route::patch('/courses/{course}/complete', [CourseController::class, 'toggleComplete'])->name('courses.complete');
        Route::patch('/courses/{course}/join-code', [CourseController::class, 'regenerateJoinCode'])->name('courses.regenerateCode');
        Route::get('/enrollments/template', [EnrollmentController::class, 'template'])->name('enrollments.template');
        Route::post('/courses/{course}/students/{user}/reset-password', [EnrollmentController::class, 'resetPassword'])->name('enrollments.resetPassword');

        // Enrollment mahasiswa
        Route::get('/courses/{course}/students', [CourseController::class, 'students'])->name('courses.students');
        Route::post('/courses/{course}/students', [EnrollmentController::class, 'store'])->name('enrollments.store');
        Route::post('/courses/{course}/students/import', [EnrollmentController::class, 'import'])->name('enrollments.import');
        Route::delete('/courses/{course}/students/{user}', [EnrollmentController::class, 'destroy'])->name('enrollments.destroy');

        // Pertemuan
        Route::post('/courses/{course}/meetings', [MeetingController::class, 'store'])->name('meetings.store');
        Route::put('/meetings/{meeting}', [MeetingController::class, 'update'])->name('meetings.update');
        Route::delete('/meetings/{meeting}', [MeetingController::class, 'destroy'])->name('meetings.destroy');

        // Materi
        Route::post('/meetings/{meeting}/materials', [MaterialController::class, 'store'])->name('materials.store');
        Route::put('/materials/{material}', [MaterialController::class, 'update'])->name('materials.update');
        Route::delete('/materials/{material}', [MaterialController::class, 'destroy'])->name('materials.destroy');

        // Tugas & Kuis — CRUD
        Route::get('/courses/{course}/assignments/create', [AssignmentController::class, 'create'])->name('assignments.create');
        Route::post('/courses/{course}/assignments', [AssignmentController::class, 'store'])->name('assignments.store');
        Route::get('/assignments/{assignment}/edit', [AssignmentController::class, 'edit'])->name('assignments.edit');
        Route::put('/assignments/{assignment}', [AssignmentController::class, 'update'])->name('assignments.update');
        Route::delete('/assignments/{assignment}', [AssignmentController::class, 'destroy'])->name('assignments.destroy');
        Route::post('/submissions/{submission}/grade', [SubmissionController::class, 'grade'])->name('submissions.grade');

        // Rubrik penilaian (kriteria per tugas)
        Route::post('/assignments/{assignment}/rubric', [RubricController::class, 'store'])->name('rubric.store');
        Route::put('/rubric-criteria/{criterion}', [RubricController::class, 'update'])->name('rubric.update');
        Route::delete('/rubric-criteria/{criterion}', [RubricController::class, 'destroy'])->name('rubric.destroy');
        Route::get('/assignments/{assignment}/download-all', [SubmissionController::class, 'downloadAll'])->name('submissions.downloadAll');
        Route::post('/submissions/{submission}/reopen', [SubmissionController::class, 'reopen'])->name('submissions.reopen');

        // Kuis — kelola soal & nilai esai
        Route::get('/assignments/{assignment}/questions', [QuizController::class, 'questions'])->name('quizzes.questions');
        Route::get('/assignments/{assignment}/questions/export', [QuizController::class, 'exportQuestions'])->name('quizzes.questions.export');
        Route::post('/assignments/{assignment}/questions/import', [QuizController::class, 'importQuestions'])->name('quizzes.questions.import');
        Route::post('/assignments/{assignment}/questions', [QuizController::class, 'storeQuestion'])->name('quizzes.questions.store');
        Route::put('/quiz-questions/{question}', [QuizController::class, 'updateQuestion'])->name('quizzes.questions.update');
        Route::delete('/quiz-questions/{question}', [QuizController::class, 'destroyQuestion'])->name('quizzes.questions.destroy');
        Route::post('/submissions/{submission}/grade-essays', [QuizController::class, 'gradeEssays'])->name('quizzes.gradeEssays');

        // Komponen nilai + input nilai manual
        Route::post('/courses/{course}/grades/manual', [GradeController::class, 'saveManual'])->name('grades.saveManual');
        Route::post('/courses/{course}/grade-components', [GradeComponentController::class, 'store'])->name('grade-components.store');
        Route::put('/grade-components/{component}', [GradeComponentController::class, 'update'])->name('grade-components.update');
        Route::delete('/grade-components/{component}', [GradeComponentController::class, 'destroy'])->name('grade-components.destroy');

        // Pengumuman
        Route::post('/courses/{course}/announcements', [AnnouncementController::class, 'store'])->name('announcements.store');
        Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'destroy'])->name('announcements.destroy');

        // Forum — pin
        Route::patch('/forum/{thread}/pin', [ForumController::class, 'pin'])->name('forum.pin');

        // ===== Admin (dosen merangkap admin) =====
        Route::prefix('admin')->name('admin.')->group(function () {
            Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
            Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
            Route::get('/grade-scale', [SettingController::class, 'editGradeScale'])->name('gradeScale.edit');
            Route::put('/grade-scale', [SettingController::class, 'updateGradeScale'])->name('gradeScale.update');
            Route::get('/ai', [SettingController::class, 'editAi'])->name('ai.edit');
            Route::put('/ai', [SettingController::class, 'updateAi'])->name('ai.update');

            Route::get('/students', [AdminStudentController::class, 'index'])->name('students.index');
            Route::get('/students/create', [AdminStudentController::class, 'create'])->name('students.create');
            Route::post('/students', [AdminStudentController::class, 'store'])->name('students.store');
            Route::post('/students/import', [AdminStudentController::class, 'import'])->name('students.import');
            Route::post('/students/bulk/reset-password', [AdminStudentController::class, 'bulkResetPassword'])->name('students.bulkReset');
            Route::post('/students/bulk/destroy', [AdminStudentController::class, 'bulkDestroy'])->name('students.bulkDestroy');
            Route::get('/students/{student}/edit', [AdminStudentController::class, 'edit'])->name('students.edit');
            Route::put('/students/{student}', [AdminStudentController::class, 'update'])->name('students.update');
            Route::post('/students/{student}/reset-password', [AdminStudentController::class, 'resetPassword'])->name('students.resetPassword');
            Route::delete('/students/{student}', [AdminStudentController::class, 'destroy'])->name('students.destroy');

            Route::get('/backups', [BackupController::class, 'index'])->name('backups.index');
            Route::post('/backups', [BackupController::class, 'run'])->name('backups.run');
            Route::get('/backups/{name}/download', [BackupController::class, 'download'])->name('backups.download');

            Route::get('/activity', [AdminActivityController::class, 'index'])->name('activity.index');
        });

        // Absensi — sesi & edit manual
        Route::get('/meetings/{meeting}/attendance', [AttendanceController::class, 'session'])->name('attendance.session');
        Route::post('/meetings/{meeting}/attendance/start', [AttendanceController::class, 'start'])->name('attendance.start');
        Route::post('/meetings/{meeting}/attendance', [AttendanceController::class, 'updateStatus'])->name('attendance.update');

        // Silabus / RPS — edit
        Route::get('/courses/{course}/syllabus/edit', [SyllabusController::class, 'edit'])->name('syllabus.edit');
        Route::put('/courses/{course}/syllabus', [SyllabusController::class, 'update'])->name('syllabus.update');

        // AI (Claude)
        Route::post('/materials/{material}/summarize', [AiController::class, 'summarizeMaterial'])->name('ai.material.summarize');
        Route::post('/meetings/{meeting}/materials/generate', [AiController::class, 'generateMaterial'])->name('ai.material.generate');
        Route::post('/assignments/{assignment}/generate-questions', [AiController::class, 'generateQuestions'])->name('ai.questions.generate');

        // Analitik
        Route::get('/courses/{course}/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
        Route::get('/api/course/{course}/analytics', [AnalyticsController::class, 'data'])->name('analytics.data');

        // Ekspor
        Route::get('/courses/{course}/export/nilai-excel', [ExportController::class, 'nilaiExcel'])->name('export.nilai.excel');
        Route::get('/courses/{course}/export/absensi-excel', [ExportController::class, 'absensiExcel'])->name('export.absensi.excel');
        Route::get('/courses/{course}/export/nilai-pdf', [ExportController::class, 'nilaiPdf'])->name('export.nilai.pdf');
    });
});
