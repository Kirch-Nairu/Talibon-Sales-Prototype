<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\MunicipalityStructureSeeder;
use Database\Seeders\WorkforceDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class PrototypeAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_presentation_starts_blank_and_contains_no_credential_injection_controls(): void
    {
        $source = file_get_contents(resource_path('js/pages/Auth/Login.tsx'));
        $this->assertIsString($source);
        $this->assertStringContainsString("useForm({ email: '', password: '', remember: false })", $source);
        $this->assertStringContainsString('Continue with Google', $source);
        $this->assertStringContainsString('disabled', $source);

        foreach (['engineering@talibon.demo', 'budget@talibon.demo', 'admin@talibon.demo', 'Demo Password', 'Use Engineering', 'Fill Credentials'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $source);
        }
    }

    public function test_existing_password_authentication_remains_authoritative_for_active_nonprivileged_user(): void
    {
        $password = 'Runtime-Only-Authentication-Test!';
        $user = User::query()->create([
            'name' => 'Authentication Contract User',
            'email' => 'authentication-contract@example.test',
            'password' => Hash::make($password),
            'role' => 'employee',
            'is_active' => true,
        ]);

        $this->post('/login', ['email' => $user->email, 'password' => $password])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_production_demo_seeding_rejects_missing_password(): void
    {
        config()->set('app.env', 'production');
        config()->set('prototype.demo_password', null);

        $this->expectException(RuntimeException::class);
        app(WorkforceDemoSeeder::class)->run();
    }

    public function test_production_demo_seeding_rejects_weak_password(): void
    {
        config()->set('app.env', 'production');
        config()->set('prototype.demo_password', 'too-short');

        $this->expectException(RuntimeException::class);
        app(WorkforceDemoSeeder::class)->run();
    }

    public function test_production_demo_seeding_rejects_a_blocked_historical_fallback_digest(): void
    {
        $candidate = 'known-unsafe-prototype-password';
        config()->set('app.env', 'production');
        config()->set('prototype.demo_password', $candidate);
        config()->set('prototype.blocked_demo_password_sha256', [hash('sha256', $candidate)]);

        $this->expectException(RuntimeException::class);
        app(WorkforceDemoSeeder::class)->run();
    }

    public function test_nonproduction_seed_can_run_without_a_committed_shared_password(): void
    {
        config()->set('app.env', 'testing');
        config()->set('prototype.demo_password', null);
        $this->assertArrayNotHasKey('local_demo_password', config('prototype'));

        $this->seed(MunicipalityStructureSeeder::class);
        $this->seed(WorkforceDemoSeeder::class);

        $this->assertSame(350, User::query()->count());
    }
}
