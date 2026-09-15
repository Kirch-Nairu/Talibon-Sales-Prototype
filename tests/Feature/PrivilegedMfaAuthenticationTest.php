<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AuthenticationAssurance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class PrivilegedMfaAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'Municipal-Test-2026!';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_privileged_password_login_requires_mfa_enrollment(): void
    {
        $user = $this->user('system_admin');

        $this->post('/login', $this->credentials($user))
            ->assertRedirect(route('mfa.enroll'));

        $this->get('/dashboard')
            ->assertRedirect(route('mfa.enroll'));

        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $user->id,
            'action' => 'auth.assurance.denied',
            'outcome' => 'denied',
        ]);
    }

    public function test_privileged_user_with_mfa_must_pass_totp_challenge(): void
    {
        $user = $this->user('department_head');
        $secret = $this->configureMfa($user);

        $this->post('/login', $this->credentials($user))
            ->assertRedirect(route('mfa.challenge'));

        $this->post('/security/mfa/challenge', [
            'code' => $this->totp()->getCurrentOtp($secret),
        ])->assertRedirect(route('dashboard'));

        $this->get('/security/mfa')->assertOk();
        $this->assertSame(
            (int) $user->fresh()->mfa_version,
            (int) session()->get(AuthenticationAssurance::SESSION_VERSION_KEY),
        );
        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $user->id,
            'action' => 'auth.assurance.satisfied',
            'outcome' => 'allowed',
        ]);
    }

    public function test_invalid_totp_does_not_satisfy_privileged_assurance(): void
    {
        $user = $this->user('hr_officer');
        $secret = $this->configureMfa($user);
        $this->post('/login', $this->credentials($user));

        $this->post('/security/mfa/challenge', [
            'code' => $this->invalidTotp($secret),
        ])->assertSessionHasErrors('code');

        $this->get('/dashboard')->assertRedirect(route('mfa.challenge'));
        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $user->id,
            'action' => 'auth.mfa.challenge.failed',
            'outcome' => 'denied',
        ]);
    }

    public function test_recovery_code_is_consumed_only_once(): void
    {
        $user = $this->user('mayor_approver');
        $recoveryCode = 'RECOV1-RECOV2';
        $this->configureMfa($user, [$recoveryCode]);
        $this->post('/login', $this->credentials($user));

        $this->post('/security/mfa/challenge', [
            'recovery_code' => $recoveryCode,
        ])->assertRedirect(route('dashboard'));

        $this->assertSame([], $user->fresh()->mfa_recovery_codes);
        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $user->id,
            'action' => 'auth.mfa.recovery_code.consumed',
        ]);
        $this->post('/logout')->assertRedirect(route('login'));
        $this->post('/login', $this->credentials($user))->assertRedirect(route('mfa.challenge'));

        $this->post('/security/mfa/challenge', [
            'recovery_code' => $recoveryCode,
        ])->assertSessionHasErrors('recovery_code');
    }

    public function test_privileged_enrollment_confirmation_generates_recovery_material_and_assurance(): void
    {
        $user = $this->user('system_admin');
        $this->post('/login', $this->credentials($user))->assertRedirect(route('mfa.enroll'));
        $this->get('/security/mfa/enroll')->assertOk();
        $pending = $user->fresh();
        $secret = $pending->mfa_secret;
        $this->assertGreaterThan(0, (int) $pending->mfa_version);

        $this->post('/security/mfa/enroll', [
            'code' => $this->totp()->getCurrentOtp($secret),
        ])->assertRedirect(route('mfa.recovery.show'));

        $configured = $user->fresh();
        $this->assertGreaterThan((int) $pending->mfa_version, (int) $configured->mfa_version);
        $this->assertNotNull($configured->mfa_confirmed_at);
        $this->assertCount(10, $configured->mfa_recovery_codes);
        $this->get('/security/mfa')->assertOk();
        $this->assertSame(
            (int) $configured->mfa_version,
            (int) session()->get(AuthenticationAssurance::SESSION_VERSION_KEY),
        );
        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $user->id,
            'action' => 'auth.mfa.enrollment.confirmed',
        ]);
    }

    public function test_nonprivileged_login_remains_password_only(): void
    {
        $user = $this->user('employee');

        $this->post('/login', $this->credentials($user))
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->get('/security/mfa')->assertRedirect(route('dashboard'));
    }

    public function test_inactive_account_is_rejected_with_generic_login_error(): void
    {
        $user = $this->user('system_admin', false);

        $this->post('/login', $this->credentials($user))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => null,
            'action' => 'auth.login.failed',
            'outcome' => 'denied',
        ]);
    }

    /**
     * @param  array<int, string>  $recoveryCodes
     */
    private function configureMfa(User $user, array $recoveryCodes = []): string
    {
        $secret = $this->totp()->generateSecretKey();
        $user->forceFill([
            'mfa_secret' => $secret,
            'mfa_confirmed_at' => now(),
            'mfa_recovery_codes' => array_map(fn (string $code): string => Hash::make(Str::upper($code)), $recoveryCodes),
            'mfa_recovery_codes_generated_at' => now(),
            'mfa_version' => max(1, (int) $user->mfa_version),
        ])->save();

        return $secret;
    }

    private function user(string $role, bool $active = true): User
    {
        return User::query()->create([
            'name' => 'MFA Test User',
            'email' => Str::uuid().'@example.test',
            'password' => self::PASSWORD,
            'role' => $role,
            'is_active' => $active,
        ]);
    }

    /**
     * @return array{email: string, password: string}
     */
    private function credentials(User $user): array
    {
        return ['email' => $user->email, 'password' => self::PASSWORD];
    }

    private function totp(): Google2FA
    {
        return app(Google2FA::class);
    }

    private function invalidTotp(string $secret): string
    {
        return $this->totp()->getCurrentOtp($secret) === '000000' ? '000001' : '000000';
    }
}
