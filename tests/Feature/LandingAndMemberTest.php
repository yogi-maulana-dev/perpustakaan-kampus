<?php

namespace Tests\Feature;

use App\Enums\MemberType;
use App\Enums\UserStatus;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;
use Tests\TestCase;

class LandingAndMemberTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_renders_with_collections(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->get('/')
            ->assertOk()
            ->assertSee('Universitas Muhammadiyah Lampung')
            ->assertSee('Koleksi Terbaru');
    }

    public function test_dosen_can_register(): void
    {
        $this->seed(DatabaseSeeder::class);
        Storage::fake('public');

        Volt::test('pages.auth.register')
            ->set('tipe', 'dosen')
            ->set('name', 'Dr. Dosen')
            ->set('email', 'dosen@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->set('nidn', '0012345678')
            ->set('nbm', '1122334455')
            ->set('fakultas', 'FT')
            ->set('kode_prodi', 'TE')
            ->set('no_hp', '081200000001')
            ->set('ktm', UploadedFile::fake()->image('kartu.jpg'))
            ->call('register')
            ->assertRedirect(route('login'));

        $user = User::where('email', 'dosen@example.com')->first();
        $this->assertEquals(UserStatus::Pending, $user->status);
        $this->assertEquals(MemberType::Dosen, $user->mahasiswaProfile->tipe);
        $this->assertEquals('0012345678', $user->mahasiswaProfile->nidn);
    }

    public function test_umum_can_register(): void
    {
        $this->seed(DatabaseSeeder::class);
        Storage::fake('public');

        Volt::test('pages.auth.register')
            ->set('tipe', 'umum')
            ->set('name', 'Warga Umum')
            ->set('email', 'umum@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->set('nomor_identitas', '1871xxxxxxxx')
            ->set('pekerjaan', 'Wiraswasta')
            ->set('no_hp', '081200000002')
            ->set('ktm', UploadedFile::fake()->image('ktp.jpg'))
            ->call('register')
            ->assertRedirect(route('login'));

        $user = User::where('email', 'umum@example.com')->first();
        $this->assertEquals(MemberType::Umum, $user->mahasiswaProfile->tipe);
        $this->assertEquals('Wiraswasta', $user->mahasiswaProfile->pekerjaan);
    }

    public function test_dosen_registration_requires_nidn(): void
    {
        Storage::fake('public');

        Volt::test('pages.auth.register')
            ->set('tipe', 'dosen')
            ->set('name', 'Tanpa NIDN')
            ->set('email', 'nonidn@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->set('no_hp', '081200000003')
            ->set('ktm', UploadedFile::fake()->image('kartu.jpg'))
            ->call('register')
            ->assertHasErrors('nidn');
    }
}
