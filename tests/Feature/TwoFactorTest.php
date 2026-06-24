<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use PragmaRX\Google2FAQRCode\Google2FA;
use Tests\TestCase;

class TwoFactorTest extends TestCase
{
    use RefreshDatabase;

    private function otpFor(string $secret): string
    {
        return (new Google2FA())->getCurrentOtp($secret);
    }

    public function test_member_can_enable_two_factor(): void
    {
        $user = User::factory()->create();

        $component = Volt::actingAs($user)->test('profile.two-factor')->call('enable');

        $secret = $component->get('pendingSecret');
        $this->assertNotEmpty($secret);

        $component->set('code', $this->otpFor($secret))->call('confirmEnable')->assertHasNoErrors();

        $this->assertTrue($user->fresh()->twoFactorEnabled());
    }

    public function test_enable_rejects_invalid_code(): void
    {
        $user = User::factory()->create();

        Volt::actingAs($user)->test('profile.two-factor')
            ->call('enable')
            ->set('code', '000000')
            ->call('confirmEnable')
            ->assertHasErrors('code');

        $this->assertFalse($user->fresh()->twoFactorEnabled());
    }

    public function test_member_can_disable_two_factor_with_password(): void
    {
        $secret = (new Google2FA())->generateSecretKey();
        $user = User::factory()->create();
        $user->forceFill(['two_factor_secret' => $secret, 'two_factor_enabled_at' => now()])->save();

        Volt::actingAs($user)->test('profile.two-factor')
            ->call('startDisable')
            ->set('currentPassword', 'password')
            ->call('disable')
            ->assertHasNoErrors();

        $this->assertFalse($user->fresh()->twoFactorEnabled());
    }

    public function test_disable_requires_correct_password(): void
    {
        $secret = (new Google2FA())->generateSecretKey();
        $user = User::factory()->create();
        $user->forceFill(['two_factor_secret' => $secret, 'two_factor_enabled_at' => now()])->save();

        Volt::actingAs($user)->test('profile.two-factor')
            ->call('startDisable')
            ->set('currentPassword', 'wrong-password')
            ->call('disable')
            ->assertHasErrors('currentPassword');

        $this->assertTrue($user->fresh()->twoFactorEnabled());
    }

    public function test_login_with_two_factor_redirects_to_challenge(): void
    {
        $secret = (new Google2FA())->generateSecretKey();
        $user = User::factory()->create();
        $user->forceFill(['two_factor_secret' => $secret, 'two_factor_enabled_at' => now()])->save();

        $component = Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'password');

        $component->set('captcha', (string) ($component->get('a') + $component->get('b')))->call('login');

        $component->assertHasNoErrors()->assertRedirect(route('two-factor.login'));

        $this->assertGuest();
        $this->assertEquals($user->id, session('login.2fa.id'));
    }

    public function test_two_factor_challenge_logs_user_in_with_valid_code(): void
    {
        $secret = (new Google2FA())->generateSecretKey();
        $user = User::factory()->create();
        $user->forceFill(['two_factor_secret' => $secret, 'two_factor_enabled_at' => now()])->save();

        session(['login.2fa.id' => $user->id, 'login.2fa.remember' => false]);

        Volt::test('pages.auth.two-factor')
            ->set('code', $this->otpFor($secret))
            ->call('verify')
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
        $this->assertNull(session('login.2fa.id'));
    }

    public function test_two_factor_challenge_rejects_invalid_code(): void
    {
        $secret = (new Google2FA())->generateSecretKey();
        $user = User::factory()->create();
        $user->forceFill(['two_factor_secret' => $secret, 'two_factor_enabled_at' => now()])->save();

        session(['login.2fa.id' => $user->id, 'login.2fa.remember' => false]);

        Volt::test('pages.auth.two-factor')
            ->set('code', '000000')
            ->call('verify')
            ->assertHasErrors('code');

        $this->assertGuest();
    }

    public function test_admin_can_reset_member_two_factor(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $librarian = User::where('email', 'librarian@perpustakaan.test')->first();
        $budi = User::where('email', 'budi@student.test')->first();
        $budi->forceFill([
            'two_factor_secret' => (new Google2FA())->generateSecretKey(),
            'two_factor_enabled_at' => now(),
        ])->save();

        Volt::actingAs($librarian)->test('staff.students')
            ->call('resetTwoFactor', $budi->id);

        $this->assertFalse($budi->fresh()->twoFactorEnabled());
    }
}
