<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['demo.enabled' => false]);
    }

    public function test_registration_page_no_longer_asks_for_a_class_code(): void
    {
        $this->get(route('register'))->assertOk()
            ->assertDontSee('Kode Kelas')
            ->assertDontSee('name="join_code"', false);
    }

    public function test_student_registers_without_a_class_code_and_lands_on_dashboard(): void
    {
        $this->post(route('register'), [
            'name' => 'Mahasiswa Baru',
            'email' => 'Baru@Contoh.ac.id',
            'nim_nip' => '2109999999',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertRedirect(route('dashboard'));

        $user = User::where('email', 'baru@contoh.ac.id')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->isMahasiswa());
        $this->assertAuthenticatedAs($user);
        // Tidak otomatis tergabung ke kelas mana pun.
        $this->assertSame(0, $user->enrolledCourses()->count());
    }

    public function test_dashboard_renders_for_a_student_with_no_courses(): void
    {
        $this->withoutExceptionHandling();
        $student = User::factory()->create(['role' => 'mahasiswa']);
        $this->actingAs($student)->get(route('dashboard.mahasiswa'))->assertOk();
    }

    public function test_full_registration_flow_reaches_dashboard_without_error(): void
    {
        $this->withoutExceptionHandling();
        $this->followingRedirects()->post(route('register'), [
            'name' => 'Mahasiswa Baru', 'email' => 'flow@contoh.ac.id', 'nim_nip' => '2107777777',
            'password' => 'rahasia123', 'password_confirmation' => 'rahasia123',
        ])->assertOk();
    }

    public function test_registration_still_rejects_duplicate_nim_and_email(): void
    {
        User::factory()->create(['email' => 'ada@contoh.ac.id', 'nim_nip' => '2100000001', 'role' => 'mahasiswa']);

        $this->post(route('register'), [
            'name' => 'Duplikat', 'email' => 'lain@contoh.ac.id', 'nim_nip' => '2100000001',
            'password' => 'rahasia123', 'password_confirmation' => 'rahasia123',
        ])->assertSessionHasErrors('nim_nip');

        $this->post(route('register'), [
            'name' => 'Duplikat', 'email' => 'ada@contoh.ac.id', 'nim_nip' => '2100000002',
            'password' => 'rahasia123', 'password_confirmation' => 'rahasia123',
        ])->assertSessionHasErrors('email');
    }
}
