<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_guest_can_see_login_and_register(): void
    {
        $this->get('/login')->assertOk();
        $this->get('/register')->assertOk();
    }

    public function test_admin_pages_render(): void
    {
        $admin = User::where('email', 'admin@perpustakaan.test')->first();

        foreach ([
            '/dashboard', '/buku', '/kategori', '/penulis', '/penerbit', '/rak',
            '/mahasiswa', '/users', '/pengaturan', '/peminjaman', '/pengembalian',
            '/denda', '/laporan', '/slider', '/pengurus', '/e-resources',
        ] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_member_card_renders(): void
    {
        $librarian = User::where('email', 'librarian@perpustakaan.test')->first();
        $budi = User::where('email', 'budi@student.test')->first();

        $this->actingAs($librarian)->get('/mahasiswa/'.$budi->id.'/kartu')->assertOk()->assertSee('UNIVERSITAS MUHAMMADIYAH LAMPUNG');
        // Pemilik akun juga bisa lihat kartunya
        $this->actingAs($budi)->get('/mahasiswa/'.$budi->id.'/kartu')->assertOk();
    }

    public function test_student_pages_render(): void
    {
        $student = User::where('email', 'budi@student.test')->first();

        foreach (['/dashboard', '/katalog', '/pinjaman-saya', '/denda-saya'] as $url) {
            $this->actingAs($student)->get($url)->assertOk();
        }
    }

    public function test_member_without_photo_is_forced_to_upload(): void
    {
        $member = User::factory()->create();
        $member->assignRole('Anggota');
        $member->mahasiswaProfile()->create([
            'tipe' => 'mahasiswa',
            'no_hp' => '081200000099',
            'foto' => null,
        ]);

        $this->actingAs($member)->get('/dashboard')->assertRedirect('/lengkapi-foto');
    }

    public function test_bulk_approve_members(): void
    {
        $librarian = User::where('email', 'librarian@perpustakaan.test')->first();
        $siti = User::where('email', 'siti@student.test')->first(); // pending member

        \Livewire\Volt\Volt::actingAs($librarian)->test('staff.students')
            ->set('selected', [$siti->id])
            ->call('bulkApprove');

        $this->assertEquals(\App\Enums\UserStatus::Active, $siti->fresh()->status);
    }

    public function test_student_cannot_access_admin_area(): void
    {
        $student = User::where('email', 'budi@student.test')->first();
        $this->actingAs($student)->get('/users')->assertForbidden();
        $this->actingAs($student)->get('/pengaturan')->assertForbidden();
    }

    public function test_pending_account_cannot_pass_active_middleware(): void
    {
        $pending = User::where('email', 'siti@student.test')->first();
        $this->actingAs($pending)->get('/dashboard')->assertRedirect('/login');
    }
}
