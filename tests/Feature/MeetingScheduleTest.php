<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Material;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MeetingScheduleTest extends TestCase
{
    use RefreshDatabase;

    private function context(): array
    {
        $lecturer = User::factory()->activeLecturer()->create();
        $student = User::factory()->create();
        $course = Course::create([
            'user_id' => $lecturer->id,
            'name' => 'Manajemen Operasional',
            'code' => 'MOP101',
            'semester' => 'Ganjil',
            'year' => 2026,
            'status' => Course::STATUS_ACTIVE,
            'join_code' => Course::generateJoinCode(),
        ]);
        $course->students()->attach($student->id, ['enrolled_at' => now()]);

        return compact('lecturer', 'student', 'course');
    }

    public function test_lecturer_generates_sixteen_weekly_meetings_with_topics_and_exams(): void
    {
        $c = $this->context();

        $this->actingAs($c['lecturer'])
            ->post(route('meetings.generate', $c['course']), [
                'count' => 16,
                'first_date' => '2026-09-01',
                'interval_days' => 7,
                'type' => Meeting::TYPE_TATAP_MUKA,
                'location' => 'Ruang 301',
                'topics' => "Pengantar\n\nPerencanaan Produksi",
                'exams' => 1,
            ])->assertRedirect()->assertSessionHas('status');

        $meetings = $c['course']->meetings()->orderBy('number')->get();
        $this->assertCount(16, $meetings);
        $this->assertSame(range(1, 16), $meetings->pluck('number')->all());
        $this->assertSame('Pengantar', $meetings[0]->topic);
        $this->assertSame('Perencanaan Produksi', $meetings[1]->topic);
        $this->assertSame('Pertemuan 3', $meetings[2]->topic);
        $this->assertSame('Ujian Tengah Semester (UTS)', $meetings[7]->topic);
        $this->assertSame('Ujian Akhir Semester (UAS)', $meetings[15]->topic);
        $this->assertSame('2026-09-01', $meetings[0]->date->toDateString());
        $this->assertSame('2026-09-08', $meetings[1]->date->toDateString());
        $this->assertSame('2026-12-15', $meetings[15]->date->toDateString());
        $this->assertSame('Ruang 301', $meetings[15]->location);
    }

    public function test_generate_skips_existing_numbers_and_reports_when_nothing_new(): void
    {
        $c = $this->context();
        $c['course']->meetings()->create(['number' => 1, 'topic' => 'Kontrak Kuliah', 'type' => Meeting::TYPE_TATAP_MUKA]);
        $payload = ['count' => 3, 'first_date' => '2026-09-01', 'interval_days' => 7, 'type' => Meeting::TYPE_MANDIRI, 'location' => 'Abaikan'];

        $this->actingAs($c['lecturer'])->post(route('meetings.generate', $c['course']), $payload)->assertSessionHas('status');

        $meetings = $c['course']->meetings()->orderBy('number')->get();
        $this->assertSame([1, 2, 3], $meetings->pluck('number')->all());
        $this->assertSame('Kontrak Kuliah', $meetings[0]->topic);
        $this->assertNull($meetings[1]->location);

        $this->post(route('meetings.generate', $c['course']), $payload)->assertSessionHas('error');
        $this->assertSame(3, $c['course']->meetings()->count());
    }

    public function test_only_course_owner_can_generate_meetings(): void
    {
        $c = $this->context();
        $payload = ['count' => 16, 'first_date' => '2026-09-01', 'interval_days' => 7, 'type' => Meeting::TYPE_TATAP_MUKA];

        $this->actingAs(User::factory()->activeLecturer()->create())
            ->post(route('meetings.generate', $c['course']), $payload)->assertForbidden();
        $this->actingAs($c['student'])
            ->post(route('meetings.generate', $c['course']), $payload)->assertForbidden();
        $this->assertSame(0, $c['course']->meetings()->count());
    }

    public function test_students_cannot_see_or_open_materials_before_meeting_date(): void
    {
        Storage::fake('local');
        $c = $this->context();
        $this->travelTo('2026-09-10 08:00');

        $past = $c['course']->meetings()->create(['number' => 1, 'topic' => 'Pengantar', 'date' => '2026-09-03']);
        $today = $c['course']->meetings()->create(['number' => 2, 'topic' => 'Peramalan', 'date' => '2026-09-10']);
        $future = $c['course']->meetings()->create(['number' => 3, 'topic' => 'Persediaan', 'date' => '2026-09-17']);
        $undated = $c['course']->meetings()->create(['number' => 4, 'topic' => 'Bonus', 'date' => null]);

        foreach ([$past, $today, $future, $undated] as $meeting) {
            Storage::disk('local')->put("materials/{$meeting->id}.pdf", 'pdf');
            $meeting->materials()->create([
                'title' => 'Modul '.$meeting->topic,
                'type' => Material::TYPE_FILE,
                'path' => "materials/{$meeting->id}.pdf",
                'mime' => 'application/pdf',
            ]);
        }
        $futureMaterial = $future->materials()->first();

        foreach (['courses.show', 'courses.materials'] as $routeName) {
            $this->actingAs($c['student'])->get(route($routeName, $c['course']))
                ->assertOk()
                ->assertSee('Modul Pengantar')
                ->assertSee('Modul Peramalan')
                ->assertSee('Modul Bonus')
                ->assertDontSee('Modul Persediaan')
                ->assertSee('dibuka', false);
        }

        $this->get(route('materials.preview', $futureMaterial))->assertForbidden();
        $this->get(route('materials.download', $futureMaterial))->assertForbidden();
        $this->get(route('materials.download', $today->materials()->first()))->assertOk();
        $this->get(route('search', ['q' => 'Modul']))->assertOk()->assertDontSee('Modul Persediaan');

        // Dosen tetap melihat dan bisa membuka semua materi.
        $this->actingAs($c['lecturer'])->get(route('courses.show', $c['course']))->assertOk()->assertSee('Modul Persediaan');
        $this->get(route('materials.download', $futureMaterial))->assertOk();

        // Saat tanggalnya tiba, materi terbuka untuk mahasiswa.
        $this->travelTo('2026-09-17 00:01');
        $this->actingAs($c['student'])->get(route('materials.download', $futureMaterial))->assertOk();
    }
}
