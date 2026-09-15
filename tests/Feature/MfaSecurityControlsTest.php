<?php

namespace Tests\Feature;

use App\Domain\Identity\ConfirmedMfaEnrollment;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuthenticationAssurance;
use App\Services\MfaRecoveryCodeBroker;
use App\Services\MfaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class MfaSecurityControlsTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'Municipal-Test-2026!';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_deactivated_authenticated_session_is_forced_out_and_audited(): void
    {
        $user = $this->user('employee');
        $this->actingAs($user);
        $user->update(['is_active' => false]);

        $this->get('/dashboard')->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $user->id,
            'action' => 'auth.account.deactivated_forced_logout',
            'outcome' => 'denied',
        ]);
    }

    public function test_mfa_middleware_blocks_direct_privileged_route_bypass(): void
    {
        $user = $this->configuredPrivilegedUser();

        Auth::guard('web')->login($user);
        $this->get('/transactions')->assertRedirect(route('mfa.challenge'));

        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $user->id,
            'action' => 'auth.assurance.denied',
            'outcome' => 'denied',
        ]);
    }

    public function test_password_login_and_mfa_challenge_are_rate_limited(): void
    {
        $ordinary = $this->user('employee');

        foreach (range(1, 5) as $_) {
            $this->post('/login', ['email' => $ordinary->email, 'password' => 'wrong-password']);
        }

        $this->post('/login', ['email' => $ordinary->email, 'password' => 'wrong-password'])
            ->assertSessionHasErrors('email');
        $this->assertDatabaseHas('audit_logs', ['action' => 'auth.login.rate_limited']);

        Cache::flush();
        $privileged = $this->configuredPrivilegedUser();
        Auth::guard('web')->login($privileged);
        $current = $this->totp()->getCurrentOtp($privileged->mfa_secret);
        $invalid = $current === '000000' ? '000001' : '000000';

        foreach (range(1, 5) as $_) {
            $this->post('/security/mfa/challenge', ['code' => $invalid]);
        }

        $this->post('/security/mfa/challenge', ['code' => $invalid])
            ->assertSessionHasErrors('code');
        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $privileged->id,
            'action' => 'auth.mfa.challenge.rate_limited',
        ]);
    }

    public function test_enrollment_confirmation_returns_exact_proven_epoch(): void
    {
        $user = $this->user('system_admin');
        $mfa = app(MfaService::class);
        $secret = $mfa->ensureEnrollmentSecret($user);
        $pending = $user->fresh();

        $proof = $mfa->confirmEnrollment($pending, $this->totp()->getCurrentOtp($secret));

        $this->assertInstanceOf(ConfirmedMfaEnrollment::class, $proof);
        $confirmed = $user->fresh();
        $this->assertSame((int) $user->id, $proof->userId);
        $this->assertSame((int) $confirmed->mfa_version, $proof->mfaVersion);
        $this->assertGreaterThan((int) $pending->mfa_version, $proof->mfaVersion);
        $this->assertCount(10, $proof->recoveryCodes);
    }

    public function test_totp_challenge_returns_exact_proven_epoch(): void
    {
        $user = $this->configuredPrivilegedUser();
        $proof = app(MfaService::class)->verifyChallenge(
            $user,
            $this->totp()->getCurrentOtp($user->mfa_secret),
            null,
        );

        $this->assertInstanceOf(ConfirmedMfaEnrollment::class, $proof);
        $this->assertSame((int) $user->id, $proof->userId);
        $this->assertSame((int) $user->mfa_version, $proof->mfaVersion);
        $this->assertSame([], $proof->recoveryCodes);
    }

    public function test_mfa_secret_and_recovery_material_are_not_exposed_or_stored_in_plaintext(): void
    {
        $user = $this->configuredPrivilegedUser();
        $secret = $user->mfa_secret;
        $serialized = $user->fresh()->toArray();

        $this->assertArrayNotHasKey('mfa_secret', $serialized);
        $this->assertArrayNotHasKey('mfa_recovery_codes', $serialized);
        $this->assertArrayNotHasKey('mfa_version', $serialized);
        $this->assertNotSame($secret, DB::table('users')->where('id', $user->id)->value('mfa_secret'));

        $this->actingAs($user)
            ->withSession($this->assuredSession($user))
            ->get('/security/mfa')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/MfaSettings')
                ->missing('secret')
                ->missing('mfa_secret')
                ->missing('mfa_recovery_codes')
                ->missing('mfa_version')
                ->missing('auth.user.mfa_secret')
                ->missing('auth.user.mfa_recovery_codes')
                ->missing('auth.user.mfa_version'));
    }

    public function test_reset_and_disable_advance_epoch_and_invalidate_assurance(): void
    {
        $user = $this->configuredPrivilegedUser();
        $beforeReset = $user->fresh();

        $this->actingAs($user)
            ->withSession($this->assuredSession($beforeReset))
            ->post('/security/mfa/reset')
            ->assertRedirect(route('mfa.enroll'));

        $afterReset = $user->fresh();
        $this->assertGreaterThan((int) $beforeReset->mfa_version, (int) $afterReset->mfa_version);
        $this->assertNull($afterReset->mfa_confirmed_at);
        $this->assertDatabaseHas('audit_logs', ['actor_user_id' => $user->id, 'action' => 'auth.mfa.reset']);
        $this->get('/dashboard')->assertRedirect(route('mfa.enroll'));

        $this->configure($user);
        $beforeDisable = $user->fresh();
        $this->withSession($this->assuredSession($beforeDisable))
            ->delete('/security/mfa')
            ->assertRedirect(route('mfa.enroll'));

        $afterDisable = $user->fresh();
        $this->assertGreaterThan((int) $beforeDisable->mfa_version, (int) $afterDisable->mfa_version);
        $this->assertNull($afterDisable->mfa_secret);
        $this->assertDatabaseHas('audit_logs', ['actor_user_id' => $user->id, 'action' => 'auth.mfa.disabled']);
        $this->get('/dashboard')->assertRedirect(route('mfa.enroll'));
    }

    public function test_success_failure_recovery_and_assurance_events_leave_audit_evidence_without_secrets(): void
    {
        $user = $this->configuredPrivilegedUser();
        $secret = $user->mfa_secret;
        Auth::guard('web')->login($user);

        $invalid = $this->totp()->getCurrentOtp($secret) === '000000' ? '000001' : '000000';
        $this->post('/security/mfa/challenge', ['code' => $invalid]);
        $this->post('/security/mfa/challenge', ['code' => $this->totp()->getCurrentOtp($secret)]);

        $actions = AuditLog::query()->where('actor_user_id', $user->id)->pluck('action')->all();
        $this->assertContains('auth.mfa.challenge.failed', $actions);
        $this->assertContains('auth.mfa.challenge.succeeded', $actions);
        $this->assertContains('auth.assurance.satisfied', $actions);
        $this->assertStringNotContainsString($secret, AuditLog::query()->pluck('summary')->implode('\n'));
    }

    public function test_old_assured_session_is_invalid_after_other_session_reset_and_reenrollment(): void
    {
        $user = $this->configuredPrivilegedUser();
        $sessionA = $this->assuredSession($user->fresh());
        $oldVersion = (int) $sessionA[AuthenticationAssurance::SESSION_VERSION_KEY];

        $this->actingAs($user)
            ->withSession($sessionA)
            ->get('/security/mfa')
            ->assertOk();

        $newSecret = app(MfaService::class)->resetEnrollment($user);
        $afterReset = $user->fresh();
        $this->assertGreaterThan($oldVersion, (int) $afterReset->mfa_version);

        Auth::guard('web')->login($afterReset);
        $this->withSession($sessionA)
            ->get('/dashboard')
            ->assertRedirect(route('mfa.enroll'));

        $newProof = app(MfaService::class)->confirmEnrollment(
            $afterReset,
            $this->totp()->getCurrentOtp($newSecret),
        );
        $this->assertInstanceOf(ConfirmedMfaEnrollment::class, $newProof);
        $reenrolled = $user->fresh();
        $this->assertGreaterThan((int) $afterReset->mfa_version, (int) $reenrolled->mfa_version);
        $this->assertNotNull($reenrolled->mfa_confirmed_at);

        Auth::guard('web')->login($reenrolled);
        $this->withSession($sessionA)
            ->get('/dashboard')
            ->assertRedirect(route('mfa.challenge'));
    }

    public function test_http_enrollment_response_preserves_sensitive_props_and_cache_headers_without_history_encryption(): void
    {
        $user = $this->user('system_admin');
        $this->post('/login', ['email' => $user->email, 'password' => self::PASSWORD]);

        $response = $this->get('/security/mfa/enroll');

        $response->assertOk()
            ->assertInertia(function (Assert $page): void {
                $page->component('Auth/MfaEnrollment')
                    ->has('secret')
                    ->has('provisioningUri')
                    ->missing('mfa_recovery_codes');
                $this->assertArrayNotHasKey('encryptHistory', $page->toArray());
            });
        $this->assertSensitiveCacheHeaders($response);
    }

    public function test_https_enrollment_response_preserves_sensitive_props_and_cache_headers_with_history_encryption(): void
    {
        $user = $this->user('system_admin');
        Auth::guard('web')->login($user);

        $response = $this->get('https://localhost/security/mfa/enroll');

        $response->assertOk()
            ->assertInertia(function (Assert $page): void {
                $page->component('Auth/MfaEnrollment')
                    ->has('secret')
                    ->has('provisioningUri')
                    ->missing('mfa_recovery_codes');
                $this->assertTrue($page->toArray()['encryptHistory'] ?? false);
            });
        $this->assertSensitiveCacheHeaders($response);
    }

    public function test_http_recovery_codes_use_one_time_flash_and_cache_headers_without_history_encryption(): void
    {
        $user = $this->configuredPrivilegedUser();
        $codes = ['ONETIME-ALPHA1', 'ONETIME-BRAVO2'];
        $sealed = app(MfaRecoveryCodeBroker::class)->seal($codes);
        Auth::guard('web')->login($user);

        $response = $this->withSession([
            ...$this->assuredSession($user),
            'mfa_recovery_codes_sealed' => $sealed,
        ])->get('/security/mfa/recovery-codes');

        $response->assertOk()
            ->assertInertia(function (Assert $page) use ($codes): void {
                $page->component('Auth/MfaRecoveryCodes')
                    ->hasFlash('mfaRecoveryCodes', $codes)
                    ->missing('codes')
                    ->missing('mfaRecoveryCodes')
                    ->has('continueUrl');
                $this->assertArrayNotHasKey('encryptHistory', $page->toArray());
            });
        $this->assertSensitiveCacheHeaders($response);

        $this->get('/security/mfa/recovery-codes')
            ->assertRedirect(route('mfa.settings'));
    }

    public function test_https_recovery_codes_use_one_time_flash_and_cache_headers_with_history_encryption(): void
    {
        $user = $this->configuredPrivilegedUser();
        $codes = ['ONETIME-CHARLIE3', 'ONETIME-DELTA4'];
        $sealed = app(MfaRecoveryCodeBroker::class)->seal($codes);
        Auth::guard('web')->login($user);

        $response = $this->withSession([
            ...$this->assuredSession($user),
            'mfa_recovery_codes_sealed' => $sealed,
        ])->get('https://localhost/security/mfa/recovery-codes');

        $response->assertOk()
            ->assertInertia(function (Assert $page) use ($codes): void {
                $page->component('Auth/MfaRecoveryCodes')
                    ->hasFlash('mfaRecoveryCodes', $codes)
                    ->missing('codes')
                    ->missing('mfaRecoveryCodes')
                    ->has('continueUrl');
                $this->assertTrue($page->toArray()['encryptHistory'] ?? false);
            });
        $this->assertSensitiveCacheHeaders($response);

        $this->get('https://localhost/security/mfa/recovery-codes')
            ->assertRedirect(route('mfa.settings'));
    }

    public function test_https_sensitive_response_resets_history_encryption_for_an_ordinary_response(): void
    {
        $user = $this->user('system_admin');
        Auth::guard('web')->login($user);

        $this->get('https://localhost/security/mfa/enroll')
            ->assertInertia(function (Assert $page): void {
                $this->assertTrue($page->toArray()['encryptHistory'] ?? false);
            });

        $this->assertOrdinaryResponseDoesNotEncryptHistory(true);
    }

    public function test_http_sensitive_response_resets_history_encryption_for_an_ordinary_response(): void
    {
        $user = $this->user('system_admin');
        Auth::guard('web')->login($user);

        $this->get('/security/mfa/enroll')
            ->assertInertia(function (Assert $page): void {
                $this->assertArrayNotHasKey('encryptHistory', $page->toArray());
            });

        $this->assertOrdinaryResponseDoesNotEncryptHistory(false);
    }

    private function configuredPrivilegedUser(): User
    {
        $user = $this->user('system_admin');
        $this->configure($user);

        return $user->fresh();
    }

    private function configure(User $user): void
    {
        $user->forceFill([
            'mfa_secret' => $this->totp()->generateSecretKey(),
            'mfa_confirmed_at' => now(),
            'mfa_recovery_codes' => [],
            'mfa_recovery_codes_generated_at' => now(),
            'mfa_version' => max(1, (int) $user->mfa_version),
        ])->save();
    }

    private function user(string $role): User
    {
        return User::query()->create([
            'name' => 'Security Test User',
            'email' => Str::uuid().'@example.test',
            'password' => self::PASSWORD,
            'role' => $role,
            'is_active' => true,
        ]);
    }

    /**
     * @return array<string, int>
     */
    private function assuredSession(User $user): array
    {
        $current = $user->fresh();

        return [
            AuthenticationAssurance::SESSION_USER_KEY => $current->id,
            AuthenticationAssurance::SESSION_VERSION_KEY => (int) $current->mfa_version,
            AuthenticationAssurance::SESSION_VERIFIED_AT_KEY => now()->getTimestamp(),
        ];
    }

    private function assertSensitiveCacheHeaders($response): void
    {
        $cacheDirectives = array_map('trim', explode(',', (string) $response->headers->get('Cache-Control')));
        sort($cacheDirectives);

        $this->assertSame([
            'max-age=0',
            'must-revalidate',
            'no-cache',
            'no-store',
            'private',
        ], $cacheDirectives);
        $this->assertSame('no-cache', $response->headers->get('Pragma'));
        $this->assertSame('0', $response->headers->get('Expires'));
    }

    private function assertOrdinaryResponseDoesNotEncryptHistory(bool $secure): void
    {
        $this->get($secure ? 'https://localhost/' : '/')
            ->assertOk()
            ->assertInertia(function (Assert $page): void {
                $page->component('Public/Home');
                $this->assertArrayNotHasKey('encryptHistory', $page->toArray());
            });
    }

    private function totp(): Google2FA
    {
        return app(Google2FA::class);
    }
}
