<?php

namespace Tests\Feature\Auth;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response
            ->assertOk()
            ->assertSeeVolt('pages.auth.register');
    }

    public function test_new_mahasiswa_can_register_with_pending_status(): void
    {
        Storage::fake('public');

        $component = Volt::test('pages.auth.register')
            ->set('name', 'Test Mahasiswa')
            ->set('nim', '2024999001')
            ->set('email', 'mahasiswa@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->set('fakultas', 'FT')
            ->set('kode_prodi', 'IF')
            ->set('angkatan', '2024')
            ->set('no_hp', '081234567890')
            ->set('ktm', UploadedFile::fake()->image('ktm.jpg'))
            ->set('foto', UploadedFile::fake()->image('foto.jpg', 300, 400));

        $component->set('captcha', (string) ($component->get('a') + $component->get('b')))->call('register');

        // Tidak auto-login: menunggu approval, diarahkan ke halaman login.
        $component->assertRedirect(route('login'));
        $this->assertGuest();

        $user = User::where('email', 'mahasiswa@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals(UserStatus::Pending, $user->status);
        $this->assertNotNull($user->mahasiswaProfile);
        $this->assertEquals('2024999001', $user->mahasiswaProfile->nim);
        $this->assertNotNull($user->mahasiswaProfile->ktm_path);
        Storage::disk('public')->assertExists($user->mahasiswaProfile->ktm_path);
        $this->assertNotNull($user->mahasiswaProfile->foto);
        Storage::disk('public')->assertExists($user->mahasiswaProfile->foto);

        // Kode prodi tersimpan & nama fakultas/prodi terderivasi dari config.
        $this->assertEquals('IF', $user->mahasiswaProfile->kode_prodi);
        $this->assertEquals('Informatika', $user->mahasiswaProfile->program_studi);
        $this->assertEquals('Fakultas Teknik', $user->mahasiswaProfile->fakultas);
        $this->assertEquals('S1', $user->mahasiswaProfile->jenjang);
    }
}
