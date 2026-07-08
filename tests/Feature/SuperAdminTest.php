<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Models\ActivityLog;
use App\Models\BlockedIp;
use App\Models\IpClearance;
use App\Models\LoginAttempt;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class SuperAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function superAdmin(): User
    {
        return User::where('email', 'superadmin@perpustakaan.test')->first();
    }

    public function test_only_super_admin_can_access_log_aktivitas(): void
    {
        $this->actingAs($this->superAdmin())->get('/log-aktivitas')->assertOk();

        // Admin biasa TIDAK boleh masuk halaman Log Aktivitas.
        $admin = User::where('email', 'admin@perpustakaan.test')->first();
        $this->actingAs($admin)->get('/log-aktivitas')->assertForbidden();

        $librarian = User::where('email', 'librarian@perpustakaan.test')->first();
        $this->actingAs($librarian)->get('/log-aktivitas')->assertForbidden();

        $budi = User::where('email', 'budi@student.test')->first();
        $this->actingAs($budi)->get('/log-aktivitas')->assertForbidden();
    }

    public function test_admin_can_manage_pengurus_but_staff_cannot(): void
    {
        $admin = User::where('email', 'admin@perpustakaan.test')->first();
        $this->actingAs($admin)->get('/pengurus')->assertOk();

        $librarian = User::where('email', 'librarian@perpustakaan.test')->first();
        $this->actingAs($librarian)->get('/pengurus')->assertForbidden();

        $staff = User::where('email', 'staff@perpustakaan.test')->first();
        $this->actingAs($staff)->get('/pengurus')->assertForbidden();
    }

    public function test_successful_login_is_recorded_with_email(): void
    {
        $user = User::factory()->create();

        $c = Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'password');
        $c->set('captcha', (string) ($c->get('a') + $c->get('b')))->call('login')->assertHasNoErrors();

        $this->assertDatabaseHas('login_attempts', ['email' => $user->email, 'successful' => true]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'login_success', 'user_id' => $user->id, 'email' => $user->email]);
    }

    public function test_location_can_be_shared_and_is_recorded(): void
    {
        $this->postJson('/verifikasi-lokasi', [
            'latitude' => -5.3971,
            'longitude' => 105.2668,
            'accuracy' => 25,
            'email' => 'coba@x.com',
        ])->assertOk()->assertJson(['ok' => true]);

        $this->assertDatabaseHas('location_pings', ['email' => 'coba@x.com']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'lokasi_dibagikan']);
    }

    public function test_blocked_ip_can_still_share_location(): void
    {
        BlockedIp::create(['ip_address' => '127.0.0.1', 'reason' => 'uji']);

        // Halaman biasa diblokir…
        $this->get('/login')->assertForbidden();

        // …tetapi endpoint berbagi lokasi tetap boleh (untuk verifikasi).
        $this->postJson('/verifikasi-lokasi', ['latitude' => 1.1, 'longitude' => 2.2])->assertOk();
    }

    public function test_failed_login_is_recorded(): void
    {
        $user = User::factory()->create();

        $c = Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'salah-password');
        $c->set('captcha', (string) ($c->get('a') + $c->get('b')))->call('login')->assertHasErrors();

        $this->assertDatabaseHas('login_attempts', ['email' => $user->email, 'successful' => false]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'login_failed']);
    }

    public function test_suspicious_ip_is_listed_after_many_attempts(): void
    {
        foreach (range(1, 6) as $i) {
            LoginAttempt::create(['email' => 'coba@x.com', 'ip_address' => '203.0.113.9', 'successful' => false]);
        }

        Volt::actingAs($this->superAdmin())->test('admin.log-aktivitas')
            ->assertSee('203.0.113.9');
    }

    public function test_super_admin_can_block_and_unblock_ip(): void
    {
        $component = Volt::actingAs($this->superAdmin())->test('admin.log-aktivitas')
            ->call('blockIp', '8.8.8.8', 'uji');

        $this->assertDatabaseHas('blocked_ips', ['ip_address' => '8.8.8.8']);

        $id = BlockedIp::where('ip_address', '8.8.8.8')->first()->id;
        $component->call('unblockIp', $id);
        $this->assertDatabaseMissing('blocked_ips', ['ip_address' => '8.8.8.8']);
    }

    public function test_blocked_ip_gets_access_denied_page(): void
    {
        BlockedIp::create(['ip_address' => '127.0.0.1', 'reason' => 'uji']);

        $this->get('/login')->assertForbidden()->assertSee('Akses Dibatasi');
    }

    public function test_super_admin_can_suspend_and_activate_account(): void
    {
        $budi = User::where('email', 'budi@student.test')->first();

        $component = Volt::actingAs($this->superAdmin())->test('admin.log-aktivitas')
            ->call('suspendUser', $budi->id);
        $this->assertEquals(UserStatus::Suspended, $budi->fresh()->status);

        // Akun diblokir tidak bisa akses area terproteksi.
        $this->actingAs($budi->fresh())->get('/dashboard')->assertRedirect('/login');

        $component->call('activateUser', $budi->id);
        $this->assertEquals(UserStatus::Active, $budi->fresh()->status);
    }

    public function test_super_admin_account_cannot_be_suspended(): void
    {
        $admin = $this->superAdmin();

        Volt::actingAs($admin)->test('admin.log-aktivitas')->call('suspendUser', $admin->id);

        $this->assertEquals(UserStatus::Active, $admin->fresh()->status);
    }

    public function test_super_admin_clears_ip_via_email_otp(): void
    {
        Mail::fake();
        $admin = $this->superAdmin(); // tanpa 2FA → OTP email

        $c = Volt::actingAs($admin)->test('admin.log-aktivitas')->call('startClear', '9.9.9.9');
        $this->assertEquals('email', $c->get('clearMethod'));

        $otp = Cache::get('ip_clear_otp:'.$admin->id.':9.9.9.9');
        $this->assertNotNull($otp);

        $c->set('clearCode', $otp)->call('confirmClear')->assertHasNoErrors();

        $this->assertDatabaseHas('ip_clearances', ['ip_address' => '9.9.9.9', 'method' => 'email', 'user_id' => $admin->id]);
    }

    public function test_super_admin_clears_ip_via_google_authenticator(): void
    {
        $admin = $this->superAdmin();
        $secret = (new Google2FA())->generateSecretKey();
        $admin->forceFill(['two_factor_secret' => $secret, 'two_factor_enabled_at' => now()])->save();

        $c = Volt::actingAs($admin)->test('admin.log-aktivitas')->call('startClear', '8.8.4.4');
        $this->assertEquals('totp', $c->get('clearMethod'));

        $c->set('clearCode', (new Google2FA())->getCurrentOtp($secret))->call('confirmClear')->assertHasNoErrors();

        $this->assertDatabaseHas('ip_clearances', ['ip_address' => '8.8.4.4', 'method' => 'totp']);
    }

    public function test_clear_ip_rejects_wrong_code(): void
    {
        Mail::fake();
        $admin = $this->superAdmin();

        Volt::actingAs($admin)->test('admin.log-aktivitas')
            ->call('startClear', '7.7.7.7')
            ->set('clearCode', '000000')
            ->call('confirmClear')
            ->assertHasErrors('clearCode');

        $this->assertDatabaseMissing('ip_clearances', ['ip_address' => '7.7.7.7']);
    }

    public function test_cleared_ip_is_no_longer_flagged_on_login(): void
    {
        foreach (range(1, 6) as $i) {
            LoginAttempt::create(['email' => 'x@x.com', 'ip_address' => '127.0.0.1', 'successful' => false]);
        }
        IpClearance::create(['ip_address' => '127.0.0.1', 'expires_at' => now()->addDay()]);

        $this->assertFalse(Volt::test('pages.auth.login')->get('ipSuspicious'));
    }

    public function test_archive_command_exports_old_logs_to_excel_and_deletes_them(): void
    {
        Storage::fake('local');

        ActivityLog::insert([
            ['action' => 'log_lama', 'user_name' => 'x', 'created_at' => now()->subDays(5), 'updated_at' => now()->subDays(5)],
            ['action' => 'log_baru', 'user_name' => 'y', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->artisan('logs:archive')->assertSuccessful();

        $this->assertDatabaseMissing('activity_logs', ['action' => 'log_lama']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'log_baru']);
        $this->assertNotEmpty(Storage::disk('local')->files('log-archives'));
    }

    public function test_super_admin_can_purge_old_logs(): void
    {
        ActivityLog::insert([
            ['action' => 'log_kuno', 'user_name' => 'z', 'created_at' => now()->subDays(6), 'updated_at' => now()->subDays(6)],
        ]);

        Volt::actingAs($this->superAdmin())->test('admin.log-aktivitas')->call('purgeOld', 4);

        $this->assertDatabaseMissing('activity_logs', ['action' => 'log_kuno']);
    }
}
