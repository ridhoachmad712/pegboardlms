<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Material;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $faker = \Faker\Factory::create('id_ID');

        // --- Dosen ---
        $dosen = User::create([
            'name' => 'Dr. Andi Wijaya, S.E., M.M.',
            'email' => 'dosen@test.com',
            'password' => Hash::make('password'),
            'role' => User::ROLE_DOSEN,
            'nim_nip' => '198501012010011001',
            'phone' => '081234567890',
        ]);

        // --- 30 Mahasiswa ---
        $students = collect();
        for ($i = 1; $i <= 30; $i++) {
            $nim = '210901' . str_pad((string) $i, 3, '0', STR_PAD_LEFT);
            $students->push(User::create([
                'name' => $faker->name(),
                'email' => sprintf('mhs%03d@test.com', $i),
                'password' => Hash::make('password'),
                'role' => User::ROLE_MAHASISWA,
                'nim_nip' => $nim,
                'phone' => $faker->phoneNumber(),
            ]));
        }

        // --- 2 Kelas, masing-masing 15 mahasiswa ---
        $courseData = [
            ['name' => 'Manajemen Keuangan', 'code' => 'MNJ2103'],
            ['name' => 'Manajemen Pemasaran', 'code' => 'MNJ2105'],
        ];

        foreach ($courseData as $idx => $cd) {
            $course = Course::create([
                'user_id' => $dosen->id,
                'name' => $cd['name'],
                'code' => $cd['code'],
                'join_code' => Course::generateJoinCode(),
                'semester' => 'Ganjil',
                'year' => 2025,
                'description' => 'Mata kuliah ' . $cd['name'] . ' untuk Prodi Manajemen FEB UNM.',
                'status' => Course::STATUS_ACTIVE,
            ]);

            // enroll 15 mahasiswa (kelas 1: 0-14, kelas 2: 15-29)
            $slice = $students->slice($idx * 15, 15);
            foreach ($slice as $student) {
                $course->students()->attach($student->id, ['enrolled_at' => now()]);
            }

            // 3 pertemuan + materi
            for ($m = 1; $m <= 3; $m++) {
                $meeting = $course->meetings()->create([
                    'number' => $m,
                    'topic' => $cd['name'],
                    'date' => now()->subWeeks(3 - $m),
                    'description' => $faker->sentence(10),
                ]);

                $meeting->materials()->create([
                    'title' => "Slide Pertemuan {$m}",
                    'type' => Material::TYPE_LINK,
                    'url' => 'https://drive.google.com/contoh-slide-' . $m,
                ]);

                if ($m === 1) {
                    $meeting->materials()->create([
                        'title' => 'Video Pengantar',
                        'type' => Material::TYPE_VIDEO,
                        'url' => 'https://youtube.com/watch?v=contoh',
                    ]);
                }
            }

            // --- Komponen nilai (Tugas 30, UTS 30, UAS 40) ---
            $compTugas = $course->gradeComponents()->create(['name' => 'Tugas', 'type' => 'tugas', 'weight' => 30]);
            $course->gradeComponents()->create(['name' => 'UTS', 'type' => 'uts', 'weight' => 30]);
            $course->gradeComponents()->create(['name' => 'UAS', 'type' => 'uas', 'weight' => 40]);

            // --- Tugas (1 sudah dinilai sebagian) ---
            $tugas = $course->assignments()->create([
                'meeting_id' => $course->meetings()->where('number', 1)->value('id'),
                'grade_component_id' => $compTugas->id,
                'title' => 'Tugas 1 — Analisis Kasus',
                'description' => 'Kerjakan analisis kasus pada bab 1.',
                'type' => Assignment::TYPE_TUGAS,
                'deadline' => now()->addDays(7),
                'max_score' => 100,
            ]);

            // 5 mahasiswa pertama submit; 3 di antaranya sudah dinilai
            foreach ($slice->take(5)->values() as $k => $student) {
                $tugas->submissions()->create([
                    'user_id' => $student->id,
                    'file_path' => null,
                    'status' => 'ontime',
                    'submitted_at' => now()->subDay(),
                    'score' => $k < 3 ? rand(70, 95) : null,
                    'feedback' => $k < 3 ? 'Kerja bagus, perhatikan referensi.' : null,
                ]);
            }

            // --- Kuis dengan 2 soal PG + 1 esai ---
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
                'type' => QuizQuestion::TYPE_PG, 'question' => '2 + 2 = ?',
                'options' => ['A' => '3', 'B' => '4', 'C' => '5'], 'correct_answer' => 'B', 'points' => 1,
            ]);
            $kuis->questions()->create([
                'type' => QuizQuestion::TYPE_PG, 'question' => 'Ibukota Sulawesi Selatan?',
                'options' => ['A' => 'Makassar', 'B' => 'Manado', 'C' => 'Palu'], 'correct_answer' => 'A', 'points' => 1,
            ]);
            $kuis->questions()->create([
                'type' => QuizQuestion::TYPE_ESSAY, 'question' => 'Jelaskan pengertian manajemen menurut Anda.',
                'points' => 2,
            ]);

            // --- Pengumuman ---
            $course->announcements()->create([
                'user_id' => $dosen->id,
                'title' => 'Selamat datang di '.$course->name,
                'content' => 'Silakan pelajari materi pertemuan 1 dan kerjakan Tugas 1 sebelum deadline.',
            ]);

            // --- Forum thread + 1 balasan ---
            $thread = $course->forumThreads()->create([
                'user_id' => $slice->first()->id,
                'title' => 'Pertanyaan tentang Tugas 1',
                'content' => 'Apakah analisis kasus boleh dikerjakan berkelompok?',
                'pinned' => false,
            ]);
            $thread->replies()->create([
                'user_id' => $dosen->id,
                'content' => 'Tugas 1 dikerjakan individu ya.',
            ]);

            // --- RPS / Silabus ---
            $course->syllabus()->create([
                'description' => 'Mata kuliah ini membahas konsep dasar '.$cd['name'].'.',
                'cpl' => "Mampu menerapkan konsep keilmuan secara bertanggung jawab.\nMampu mengambil keputusan berdasarkan analisis data.",
                'cpmk' => "Memahami konsep dasar.\nMampu menerapkan dalam kasus nyata.\nMenganalisis permasalahan terkait.",
                'sub_cpmk' => "Menjelaskan terminologi dasar.\nMenyelesaikan studi kasus sederhana.",
                'references' => "Buku Ajar ".$cd['name']." (2024).\nJurnal Manajemen FEB UNM.",
                'assessment' => "Tugas 30%, UTS 30%, UAS 40%. Kehadiran minimal 75%.",
                'rules' => "Wajib hadir minimal 75%. Keterlambatan tugas dikurangi nilai.",
            ]);

            // --- Absensi: 2 pertemuan pertama sudah ada sesi ---
            foreach ($course->meetings()->orderBy('number')->take(2)->get() as $meeting) {
                foreach ($slice->values() as $idx => $student) {
                    // mayoritas hadir; mahasiswa terakhir sering alpa (kehadiran < 75%)
                    $status = 'hadir';
                    if ($idx === $slice->count() - 1) {
                        $status = 'alpa';
                    } elseif ($idx % 7 === 0) {
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

        $this->command->info('Seeder selesai: 1 dosen, 30 mahasiswa, 2 kelas.');
        $this->command->info('Login dosen: dosen@test.com / password');
        $this->command->info('Login mahasiswa: mhs001@test.com / password');
    }
}
