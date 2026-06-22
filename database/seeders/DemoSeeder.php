<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\GradeScore;
use App\Models\Material;
use App\Models\QuizQuestion;
use App\Models\Semester;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Data contoh untuk MODE DEMO.
 * Akun demo (dosen@demo.test & mahasiswa@demo.test) sengaja diberi data kaya
 * agar calon pembeli langsung melihat aplikasi "berisi" saat klik akses 1-klik.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // Nama statis (tanpa Faker) agar seeder jalan di deploy --no-dev.
        $names = [
            'Budi Santoso', 'Siti Aminah', 'Andi Pratama', 'Dewi Lestari', 'Rizki Ramadhan',
            'Putri Maharani', 'Agus Setiawan', 'Nur Fadilah', 'Eko Prasetyo', 'Indah Permata',
            'Fajar Nugroho', 'Ayu Wulandari', 'Hendra Gunawan', 'Maya Sari', 'Dani Kurniawan',
            'Rina Marlina', 'Bayu Aji', 'Lina Oktaviani', 'Yusuf Hidayat', 'Sri Wahyuni',
            'Arif Rahman', 'Citra Dewanti', 'Galih Saputra', 'Wulan Anggraini',
        ];
        $year = (int) date('Y');
        $semester = 'Ganjil';

        // Pengaturan dasar demo
        Setting::put('academic_year', (string) $year);
        Setting::put('semester', $semester);
        Semester::firstOrCreate(['year' => $year, 'semester' => $semester]);

        // --- Dosen demo (merangkap admin) ---
        $dosen = User::create([
            'name' => 'Dr. Andi Wijaya, S.E., M.M.',
            'email' => config('demo.dosen_email'),
            'password' => Hash::make('demo'),
            'role' => User::ROLE_DOSEN,
            'nim_nip' => '198501012010011001',
            'phone' => '081234567890',
            'email_verified_at' => now(),
        ]);

        // --- Mahasiswa demo (akun 1-klik) sebagai mahasiswa pertama ---
        $demoMhs = User::create([
            'name' => 'Mahasiswa Demo',
            'email' => config('demo.mahasiswa_email'),
            'password' => Hash::make('demo'),
            'role' => User::ROLE_MAHASISWA,
            'nim_nip' => '2100000000',
            'phone' => '081200000001',
            'email_verified_at' => now(),
        ]);

        // --- 24 mahasiswa lain ---
        $others = collect();
        for ($i = 1; $i <= 24; $i++) {
            $others->push(User::create([
                'name' => $names[$i - 1],
                'email' => sprintf('demo-mhs%03d@demo.test', $i),
                'password' => Hash::make('demo'),
                'role' => User::ROLE_MAHASISWA,
                'nim_nip' => '2100'.str_pad((string) $i, 6, '0', STR_PAD_LEFT),
                'phone' => '0812'.str_pad((string) $i, 7, '0', STR_PAD_LEFT),
                'email_verified_at' => now(),
            ]));
        }

        $courseData = [
            ['name' => 'Manajemen Keuangan', 'code' => 'MNJ2103', 'slice' => [0, 14]],
            ['name' => 'Manajemen Pemasaran', 'code' => 'MNJ2105', 'slice' => [10, 14]],
        ];

        foreach ($courseData as $cd) {
            $course = Course::create([
                'user_id' => $dosen->id,
                'name' => $cd['name'],
                'code' => $cd['code'],
                'join_code' => Course::generateJoinCode(),
                'semester' => $semester,
                'year' => $year,
                'description' => 'Mata kuliah '.$cd['name'].' untuk Prodi Manajemen FEB UNM.',
                'status' => Course::STATUS_ACTIVE,
            ]);

            // Mahasiswa demo selalu ikut + sebagian mahasiswa lain
            $enrolled = collect([$demoMhs])
                ->merge($others->slice($cd['slice'][0], $cd['slice'][1]))
                ->values();

            foreach ($enrolled as $student) {
                $course->students()->attach($student->id, ['enrolled_at' => now()]);
            }

            // 3 pertemuan + materi
            for ($m = 1; $m <= 3; $m++) {
                $meeting = $course->meetings()->create([
                    'number' => $m,
                    'topic' => $cd['name'].' — Pertemuan '.$m,
                    'date' => now()->subWeeks(3 - $m),
                    'description' => 'Ringkasan dan tujuan pembelajaran untuk pertemuan ke-'.$m.'.',
                ]);

                $meeting->materials()->create([
                    'title' => "Slide Pertemuan {$m}",
                    'type' => Material::TYPE_LINK,
                    'url' => 'https://drive.google.com/contoh-slide-'.$m,
                ]);

                if ($m === 1) {
                    $meeting->materials()->create([
                        'title' => 'Video Pengantar',
                        'type' => Material::TYPE_VIDEO,
                        'url' => 'https://youtube.com/watch?v=contoh',
                    ]);
                }
            }

            // Komponen nilai
            $compTugas = $course->gradeComponents()->create(['name' => 'Tugas', 'type' => 'tugas', 'weight' => 30]);
            $compUts = $course->gradeComponents()->create(['name' => 'UTS', 'type' => 'uts', 'weight' => 30]);
            $compUas = $course->gradeComponents()->create(['name' => 'UAS', 'type' => 'uas', 'weight' => 40]);

            // Tugas (mahasiswa demo termasuk yang sudah dinilai)
            $tugas = $course->assignments()->create([
                'meeting_id' => $course->meetings()->where('number', 1)->value('id'),
                'grade_component_id' => $compTugas->id,
                'title' => 'Tugas 1 — Analisis Kasus',
                'description' => 'Kerjakan analisis kasus pada bab 1.',
                'type' => Assignment::TYPE_TUGAS,
                'deadline' => now()->addDays(7),
                'max_score' => 100,
            ]);

            foreach ($enrolled->take(6)->values() as $k => $student) {
                $tugas->submissions()->create([
                    'user_id' => $student->id,
                    'file_path' => null,
                    'status' => 'ontime',
                    'submitted_at' => now()->subDay(),
                    'score' => $k < 4 ? rand(75, 95) : null,
                    'feedback' => $k < 4 ? 'Kerja bagus, perhatikan kutipan referensi.' : null,
                ]);
            }

            // Nilai UTS & UAS untuk semua mahasiswa terdaftar
            foreach ($enrolled as $student) {
                GradeScore::create(['grade_component_id' => $compUts->id, 'user_id' => $student->id, 'score' => rand(65, 92)]);
                GradeScore::create(['grade_component_id' => $compUas->id, 'user_id' => $student->id, 'score' => rand(65, 95)]);
            }

            // Kuis: 2 PG + 1 esai
            $kuis = $course->assignments()->create([
                'meeting_id' => $course->meetings()->where('number', 2)->value('id'),
                'title' => 'Kuis 1',
                'description' => 'Kuis singkat materi awal.',
                'type' => Assignment::TYPE_KUIS,
                'deadline' => now()->addDays(3),
                'duration_minutes' => 15,
                'max_score' => 100,
            ]);
            $kuis->questions()->create([
                'type' => QuizQuestion::TYPE_PG, 'question' => 'Apa fungsi utama manajemen keuangan?',
                'options' => ['A' => 'Mengelola dana', 'B' => 'Memasarkan produk', 'C' => 'Merekrut pegawai'], 'correct_answer' => 'A', 'points' => 1,
            ]);
            $kuis->questions()->create([
                'type' => QuizQuestion::TYPE_PG, 'question' => 'Ibukota Sulawesi Selatan?',
                'options' => ['A' => 'Makassar', 'B' => 'Manado', 'C' => 'Palu'], 'correct_answer' => 'A', 'points' => 1,
            ]);
            $kuis->questions()->create([
                'type' => QuizQuestion::TYPE_ESSAY, 'question' => 'Jelaskan pengertian manajemen menurut Anda.',
                'points' => 2,
            ]);

            // Pengumuman
            $course->announcements()->create([
                'user_id' => $dosen->id,
                'title' => 'Selamat datang di '.$course->name,
                'content' => 'Silakan pelajari materi pertemuan 1 dan kerjakan Tugas 1 sebelum deadline.',
            ]);

            // Forum
            $thread = $course->forumThreads()->create([
                'user_id' => $demoMhs->id,
                'title' => 'Pertanyaan tentang Tugas 1',
                'content' => 'Apakah analisis kasus boleh dikerjakan berkelompok?',
                'pinned' => false,
            ]);
            $thread->replies()->create([
                'user_id' => $dosen->id,
                'content' => 'Tugas 1 dikerjakan individu ya.',
            ]);

            // Silabus / RPS
            $course->syllabus()->create([
                'description' => 'Mata kuliah ini membahas konsep dasar '.$cd['name'].'.',
                'cpl' => "Mampu menerapkan konsep keilmuan secara bertanggung jawab.\nMampu mengambil keputusan berdasarkan analisis data.",
                'cpmk' => "Memahami konsep dasar.\nMampu menerapkan dalam kasus nyata.\nMenganalisis permasalahan terkait.",
                'sub_cpmk' => "Menjelaskan terminologi dasar.\nMenyelesaikan studi kasus sederhana.",
                'references' => "Buku Ajar ".$cd['name']." (2024).\nJurnal Manajemen FEB UNM.",
                'assessment' => "Tugas 30%, UTS 30%, UAS 40%. Kehadiran minimal 75%.",
                'rules' => "Wajib hadir minimal 75%. Keterlambatan tugas dikurangi nilai.",
            ]);

            // Absensi: 2 pertemuan pertama
            foreach ($course->meetings()->orderBy('number')->take(2)->get() as $meeting) {
                foreach ($enrolled->values() as $idx => $student) {
                    $status = 'hadir';
                    if ($idx === $enrolled->count() - 1) {
                        $status = 'alpa';
                    } elseif ($idx % 7 === 0 && $idx !== 0) {
                        $status = ['izin', 'sakit'][$meeting->number % 2];
                    }
                    $meeting->attendances()->create([
                        'user_id' => $student->id,
                        'status' => $status,
                        'method' => $status === 'hadir' ? 'qr' : 'manual',
                    ]);
                }
            }
        }

        $this->command->info('DemoSeeder selesai: 1 dosen demo, 25 mahasiswa, 2 kelas berisi.');
    }
}
