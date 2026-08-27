<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminExperienceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['demo.enabled' => false]);
    }

    public function test_dashboard_routes_each_role_to_its_own_workspace(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get(route('dashboard'))->assertRedirect(route('admin.dashboard'));
        $this->get(route('dashboard.dosen'))->assertOk();
        $this->actingAs(User::factory()->activeLecturer()->create())->get(route('dashboard'))->assertRedirect(route('dashboard.dosen'));
        $this->actingAs(User::factory()->create())->get(route('dashboard'))->assertRedirect(route('dashboard.mahasiswa'));
    }

    public function test_admin_summary_is_protected_by_authentication_and_admin_permission(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
        foreach ([User::factory()->create(), User::factory()->activeLecturer()->create()] as $user) {
            $this->actingAs($user)->get(route('admin.dashboard'))->assertForbidden();
        }
        $this->actingAs(User::factory()->create(['role' => 'dosen']))
            ->get(route('admin.dashboard'))->assertRedirect(route('activation.show'));
    }

    public function test_summary_counts_and_oldest_pending_accounts_exclude_administrators(): void
    {
        $admin = User::factory()->admin()->create();
        $active = User::factory()->activeLecturer()->create();
        $pending = User::factory()->create(['role' => 'dosen', 'created_at' => now()->subDays(2)]);
        User::factory()->create(['role' => 'dosen', 'created_at' => now()->subDay()]);
        User::factory()->count(2)->create();
        Course::create([
            'user_id' => $active->id, 'name' => 'Kelas Uji', 'code' => 'ADM101',
            'semester' => 'Ganjil', 'year' => 2026, 'status' => Course::STATUS_ACTIVE,
            'join_code' => Course::generateJoinCode(),
        ]);
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()
            ->assertViewHas('pendingCount', 2)->assertViewHas('activeCount', 1)
            ->assertViewHas('studentCount', 2)->assertViewHas('courseCount', 1)
            ->assertViewHas('pendingLecturers', fn ($items) => $items->first()->is($pending))
            ->assertSee('Ruang mengajar')->assertSee('Navigasi admin mobile');
        $this->get(route('admin.lecturers.index', ['status' => 'active']))->assertOk()
            ->assertViewHas('activeCount', 1)
            ->assertViewHas('lecturers', fn ($items) => $items->total() === 1 && $items->first()->is($active));
    }

    public function test_admin_pages_share_the_admin_navigation_and_stylesheet(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->create();
        $lecturer = User::factory()->create(['role' => 'dosen']);
        $routes = [
            ['admin.dashboard', []], ['admin.lecturers.index', []], ['admin.lecturers.show', $lecturer],
            ['admin.students.index', []], ['admin.students.create', []], ['admin.students.edit', $student],
            ['admin.settings.edit', []], ['admin.semesters.index', []], ['admin.gradeScale.edit', []],
            ['admin.ai.edit', []], ['admin.activity.index', []], ['admin.backups.index', []],
        ];
        $this->actingAs($admin);
        foreach ($routes as [$name, $parameters]) {
            $this->get(route($name, $parameters))->assertOk()->assertSee('admin-ui')
                ->assertSee('css/admin.css')->assertSee('Navigasi admin mobile')->assertSee('Menu admin');
        }
        $this->get(route('dashboard.dosen'))->assertOk()->assertDontSee('css/admin.css');
        $this->actingAs($student)->get(route('dashboard.mahasiswa'))->assertOk()->assertDontSee('css/admin.css');
    }

    public function test_lecturer_filter_preserves_query_and_has_contextual_empty_state(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        User::factory()->create(['role' => 'dosen', 'institution' => 'Kampus Uji']);
        User::factory()->activeLecturer()->create(['institution' => 'Institusi Lain']);
        $this->get(route('admin.lecturers.index', ['q' => 'Kampus Uji', 'status' => 'pending']))
            ->assertOk()->assertViewHas('lecturers', fn ($items) => $items->total() === 1)->assertSee('Hapus pencarian');
        $this->get(route('admin.lecturers.index', ['q' => 'tidak-ada']))
            ->assertOk()->assertSee('Dosen tidak ditemukan');
    }

    public function test_empty_admin_dashboard_is_compact_and_has_useful_navigation(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.dashboard'))->assertOk()
            ->assertSee('Tidak ada aktivasi tertunda')->assertSee('Akses cepat')
            ->assertViewHas('pendingCount', 0)->assertViewHas('activeCount', 0);
    }

    public function test_lecturer_detail_keeps_payment_confirmation_and_private_code_handling(): void
    {
        $admin = User::factory()->admin()->create();
        $pending = User::factory()->create(['role' => 'dosen', 'name' => '<script>alert("unsafe")</script>']);
        $this->actingAs($admin)->get(route('admin.lecturers.show', $pending))->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertSee('Pembayaran satu kali sudah saya verifikasi.')
            ->assertSee('name="payment_confirmed"', false)->assertDontSee($pending->name, false);
        $active = User::factory()->activeLecturer()->create();
        $this->get(route('admin.lecturers.show', $active))->assertOk()
            ->assertSee('Tidak perlu kode tambahan')->assertDontSee('name="payment_confirmed"', false);
    }
}
