<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ShowcaseSessionGatewayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('showcase.enabled', true);
    }

    public function test_entry_exposes_only_presentation_safe_persona_metadata(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/Login')
                ->where('showcase.enabled', true)
                ->has('showcase.personas', 7)
                ->where('showcase.personas.0.label', 'Municipal Executive')
                ->missing('showcase.personas.0.email'));
    }

    public function test_every_curated_persona_can_create_an_authenticated_showcase_session(): void
    {
        foreach (config('showcase.personas') as $key => $persona) {
            $user = User::query()->create([
                'name' => $persona['label'].' Test',
                'email' => $persona['email'],
                'password' => Hash::make('not-used-by-showcase'),
                'role' => $this->roleFor($key),
                'is_active' => true,
            ]);

            $this->post('/showcase/session', ['persona' => $key])
                ->assertRedirect(route('dashboard'))
                ->assertSessionHas('showcase.persona', $key)
                ->assertSessionHas('showcase.session', true);

            $this->assertAuthenticatedAs($user);
            $this->post('/showcase/switch')->assertRedirect(route('login'));
            $this->assertGuest();
        }
    }

    public function test_invalid_persona_is_rejected_without_authentication(): void
    {
        $this->post('/showcase/session', ['persona' => '999'])
            ->assertSessionHasErrors('persona');

        $this->assertGuest();
    }

    public function test_path_like_persona_is_rejected_without_authentication(): void
    {
        $this->post('/showcase/session', ['persona' => '../../foo'])
            ->assertSessionHasErrors('persona');

        $this->assertGuest();
    }

    public function test_email_shaped_persona_is_rejected_without_authentication(): void
    {
        $this->post('/showcase/session', ['persona' => 'admin@example.com'])
            ->assertSessionHasErrors('persona');

        $this->assertGuest();
    }

    public function test_arbitrary_user_id_selector_is_prohibited(): void
    {
        $user = $this->curatedUser('employee');

        $this->post('/showcase/session', ['persona' => 'employee', 'user_id' => $user->id])
            ->assertSessionHasErrors('user_id');

        $this->assertGuest();
    }

    public function test_arbitrary_email_selector_is_prohibited(): void
    {
        $this->curatedUser('employee');

        $this->post('/showcase/session', [
            'persona' => 'employee',
            'email' => 'somebody@talibon.demo',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_inactive_curated_account_is_rejected(): void
    {
        $this->curatedUser('executive', false);

        $this->post('/showcase/session', ['persona' => 'executive'])
            ->assertSessionHasErrors('persona');

        $this->assertGuest();
    }

    public function test_missing_configured_account_is_rejected(): void
    {
        $this->post('/showcase/session', ['persona' => 'budget_head'])
            ->assertSessionHasErrors('persona');

        $this->assertGuest();
    }

    public function test_showcase_endpoint_is_unavailable_when_mode_is_disabled(): void
    {
        config()->set('showcase.enabled', false);
        $this->curatedUser('employee');

        $this->post('/showcase/session', ['persona' => 'employee'])
            ->assertNotFound();

        $this->assertGuest();
    }

    public function test_switch_workspace_clears_authentication_and_showcase_markers(): void
    {
        $this->curatedUser('employee');
        $this->post('/showcase/session', ['persona' => 'employee']);

        $this->post('/showcase/switch')
            ->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertNull(session()->get('showcase.persona'));
        $this->assertNull(session()->get('showcase.session'));
    }

    public function test_logout_clears_showcase_markers(): void
    {
        $this->curatedUser('employee');
        $this->post('/showcase/session', ['persona' => 'employee']);

        $this->post('/logout')->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertNull(session()->get('showcase.persona'));
        $this->assertNull(session()->get('showcase.session'));
    }

    private function curatedUser(string $key, bool $active = true): User
    {
        $persona = config("showcase.personas.{$key}");

        return User::query()->create([
            'name' => $persona['label'].' Test',
            'email' => $persona['email'],
            'password' => Hash::make('not-used-by-showcase'),
            'role' => $this->roleFor($key),
            'is_active' => $active,
        ]);
    }

    private function roleFor(string $key): string
    {
        return match ($key) {
            'executive' => 'mayor_approver',
            'engineering_head', 'budget_head' => 'department_head',
            'hr' => 'hr_officer',
            'legislative' => 'legislative_staff',
            'system_admin' => 'system_admin',
            default => 'employee',
        };
    }
}
