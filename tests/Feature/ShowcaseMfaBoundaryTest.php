<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PortalNavigationAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ShowcaseMfaBoundaryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('showcase.enabled', true);
        $this->seed();
    }

    public function test_privileged_showcase_persona_bypasses_visible_mfa_challenge(): void
    {
        $admin = User::query()->where('email', 'admin@talibon.demo')->firstOrFail();

        $this->post('/showcase/session', ['persona' => 'system_admin'])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($admin);
        $this->get('/dashboard')->assertOk();
    }

    public function test_normal_privileged_session_does_not_automatically_bypass_mfa(): void
    {
        $admin = User::query()->where('email', 'admin@talibon.demo')->firstOrFail();

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertRedirect(route('mfa.enroll'));
    }

    public function test_forged_showcase_marker_for_another_persona_does_not_bypass_mfa(): void
    {
        $admin = User::query()->where('email', 'admin@talibon.demo')->firstOrFail();

        $this->actingAs($admin)
            ->withSession([
                'showcase.session' => true,
                'showcase.persona' => 'employee',
            ])
            ->get('/dashboard')
            ->assertRedirect(route('mfa.enroll'));
    }

    public function test_showcase_marker_does_not_bypass_mfa_when_mode_is_disabled(): void
    {
        config()->set('showcase.enabled', false);
        $admin = User::query()->where('email', 'admin@talibon.demo')->firstOrFail();

        $this->actingAs($admin)
            ->withSession([
                'showcase.session' => true,
                'showcase.persona' => 'system_admin',
            ])
            ->get('/dashboard')
            ->assertRedirect(route('mfa.enroll'));
    }

    public function test_showcase_navigation_remains_derived_from_authenticated_user(): void
    {
        $admin = User::query()->where('email', 'admin@talibon.demo')->firstOrFail();
        $expected = app(PortalNavigationAccess::class)->for($admin);

        $this->post('/showcase/session', ['persona' => 'system_admin']);

        $this->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('permissions.navigation', $expected)
                ->where('showcaseSession.active', true)
                ->where('showcaseSession.persona.label', 'System Administration'));
    }

    public function test_employee_showcase_session_does_not_gain_administration_navigation(): void
    {
        $employee = User::query()->where('email', 'employee@talibon.demo')->firstOrFail();
        $expected = app(PortalNavigationAccess::class)->for($employee);
        $this->assertFalse($expected['systemAdministration']);

        $this->post('/showcase/session', ['persona' => 'employee']);

        $this->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('permissions.navigation', $expected)
                ->where('permissions.navigation.systemAdministration', false));
    }
}
