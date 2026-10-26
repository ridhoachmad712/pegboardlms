<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Material;
use App\Models\Setting;
use App\Models\User;
use App\Services\Ai\AiProviderFactory;
use Illuminate\Encryption\Encrypter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PrivateFilesAndSecretsTest extends TestCase
{
    use RefreshDatabase;

    private function context(): array
    {
        $lecturer = User::factory()->activeLecturer()->create();
        $student = User::factory()->create();
        $outsider = User::factory()->create();
        $course = Course::create([
            'user_id' => $lecturer->id,
            'name' => 'Manajemen Keuangan',
            'code' => 'MKU101',
            'semester' => 'Ganjil',
            'year' => 2026,
            'status' => Course::STATUS_ACTIVE,
            'join_code' => Course::generateJoinCode(),
        ]);
        $course->students()->attach($student->id, ['enrolled_at' => now()]);
        $meeting = $course->meetings()->create(['number' => 1, 'topic' => 'Pendahuluan', 'date' => today()]);

        return compact('lecturer', 'student', 'outsider', 'course', 'meeting');
    }

    public function test_demo_mode_blocks_changing_password_from_profile(): void
    {
        config(['demo.enabled' => true]);
        $student = User::factory()->create(['password' => Hash::make('password')]);

        $this->actingAs($student)
            ->from(route('profile.edit'))
            ->put(route('profile.password'), [
                'current_password' => 'password',
                'password' => 'kata-sandi-baru-123',
                'password_confirmation' => 'kata-sandi-baru-123',
            ])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('error');

        $this->assertTrue(Hash::check('password', $student->fresh()->password));
    }

    public function test_material_upload_is_stored_privately_and_served_only_to_course_members(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $context = $this->context();

        $this->actingAs($context['lecturer'])
            ->post(route('materials.store', $context['meeting']), [
                'title' => 'Modul 1',
                'type' => Material::TYPE_FILE,
                'file' => UploadedFile::fake()->create('modul.pdf', 64, 'application/pdf'),
            ])->assertRedirect();

        $material = Material::sole();
        Storage::disk('local')->assertExists($material->path);
        Storage::disk('public')->assertMissing($material->path);
        $this->assertSame(route('materials.preview', $material), $material->link);

        $this->actingAs($context['student'])->get(route('materials.download', $material))->assertOk();
        $this->actingAs($context['outsider'])->get(route('materials.download', $material))->assertForbidden();
    }

    public function test_office_preview_uses_expiring_signed_url_instead_of_public_storage(): void
    {
        // Disk asli (bukan fake) agar temporaryUrl memakai route bertanda tangan Laravel.
        $root = storage_path('framework/testing/disks/private-'.uniqid());
        config(['filesystems.disks.local.root' => $root]);
        Storage::forgetDisk('local');
        $this->beforeApplicationDestroyed(fn () => File::deleteDirectory($root));
        $this->freezeTime();
        $context = $this->context();
        Storage::disk('local')->put('materials/1/slide.pptx', 'isi-slide');
        $context['meeting']->materials()->create([
            'title' => 'Slide 1',
            'type' => Material::TYPE_FILE,
            'path' => 'materials/1/slide.pptx',
        ]);

        $html = $this->actingAs($context['student'])->get(route('courses.show', $context['course']))
            ->assertOk()->getContent();
        $this->assertStringContainsString('view.officeapps.live.com', $html);
        $this->assertStringNotContainsString(urlencode(asset('storage/materials/1/slide.pptx')).'&', $html);

        $signed = Storage::disk('local')->temporaryUrl('materials/1/slide.pptx', now()->addMinutes(30));
        $this->assertStringContainsString(urlencode($signed), $html);

        auth()->logout();
        $this->assertStringContainsString('signature=', $signed);
        $this->get($signed)->assertOk();
        $this->get('/storage/materials/1/slide.pptx')->assertForbidden();
    }

    public function test_privatize_command_moves_legacy_files_and_is_idempotent(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $public = Storage::disk('public');
        $public->put('materials/1/lama.pdf', 'materi');
        $public->put('submissions/7/jawaban.docx', 'jawaban');
        $public->put('avatars/foto.png', 'avatar');
        // Sisa salinan dari percobaan sebelumnya: berkas tujuan tidak boleh ditimpa.
        Storage::disk('local')->put('submissions/7/jawaban.docx', 'sudah-dipindah');

        $this->artisan('lms:privatize-files')->assertSuccessful();

        Storage::disk('local')->assertExists('materials/1/lama.pdf');
        $this->assertSame('sudah-dipindah', Storage::disk('local')->get('submissions/7/jawaban.docx'));
        $public->assertMissing('materials/1/lama.pdf');
        $public->assertMissing('submissions/7/jawaban.docx');
        $public->assertExists('avatars/foto.png');

        $this->artisan('lms:privatize-files')->assertSuccessful();
        Storage::disk('local')->assertExists('materials/1/lama.pdf');

        $this->artisan('lms:privatize-files', ['--reverse' => true])->assertSuccessful();
        $public->assertExists('materials/1/lama.pdf');
        Storage::disk('local')->assertMissing('materials/1/lama.pdf');
    }

    public function test_ai_key_is_encrypted_at_rest_and_plaintext_legacy_value_still_reads(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.ai.update'), [
                'ai_enabled' => '1',
                'ai_provider' => 'openai',
                'model_anthropic' => array_key_first(AiProviderFactory::models()['anthropic']),
                'model_openai' => array_key_first(AiProviderFactory::models()['openai']),
                'model_gemini' => array_key_first(AiProviderFactory::models()['gemini']),
                'key_openai' => 'sk-test-rahasia',
            ])->assertRedirect();

        $stored = DB::table('settings')->where('key', 'ai_key_openai')->value('value');
        $this->assertNotSame('sk-test-rahasia', $stored);
        $this->assertSame('sk-test-rahasia', Crypt::decryptString($stored));
        $this->assertSame('sk-test-rahasia', Setting::secret('ai_key_openai'));

        Setting::put('ai_key_gemini', 'kunci-polos-lama');
        $this->assertSame('kunci-polos-lama', Setting::secret('ai_key_gemini'));

        // Ciphertext dari APP_KEY lain tidak boleh dikirim mentah sebagai API key.
        $foreign = (new Encrypter(random_bytes(32), 'aes-256-cbc'))->encryptString('x');
        Setting::put('ai_key_anthropic', $foreign);
        $this->assertNull(Setting::secret('ai_key_anthropic'));
    }

    public function test_migration_encrypts_existing_plaintext_ai_keys(): void
    {
        Setting::put('ai_key_openai', 'sk-polos');
        Setting::putSecret('ai_key_gemini', 'sudah-terenkripsi');
        Setting::put('ai_provider', 'openai');
        $migration = require database_path('migrations/2026_10_26_000001_encrypt_ai_keys_in_settings.php');

        $migration->up();
        $migration->up();

        $raw = DB::table('settings')->pluck('value', 'key');
        $this->assertSame('sk-polos', Crypt::decryptString($raw['ai_key_openai']));
        $this->assertSame('sudah-terenkripsi', Crypt::decryptString($raw['ai_key_gemini']));
        $this->assertSame('openai', $raw['ai_provider']);

        $migration->down();
        $this->assertSame('sk-polos', DB::table('settings')->where('key', 'ai_key_openai')->value('value'));
    }
}
