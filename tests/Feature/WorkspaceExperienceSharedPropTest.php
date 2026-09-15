<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AuthenticationAssurance;
use App\Services\PortalNavigationAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class WorkspaceExperienceSharedPropTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_representative_users_receive_authoritative_workspace_experience(): void
    {
        $this->seed();

        foreach ([
            'employee@talibon.demo' => 'employee',
            'engineering@talibon.demo' => 'department_head',
            'mayor@talibon.demo' => 'executive_oversight',
            'admin@talibon.demo' => 'system_administration',
        ] as $email => $expectedExperience) {
            $user = User::query()->where('email', $email)->firstOrFail();
            $this->assure($user);
            $expectedNavigation = app(PortalNavigationAccess::class)->for($user->fresh());

            $this->actingAs($user)->get('/dashboard')
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->where('workspaceExperience', $expectedExperience)
                    ->where('permissions.navigation', $expectedNavigation)
                    ->where('permissions.reports', $expectedNavigation['reports']));
        }
    }

    public function test_workspace_experience_does_not_replace_navigation_capability_payload(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@talibon.demo')->firstOrFail();
        $this->assure($admin);
        $expectedNavigation = app(PortalNavigationAccess::class)->for($admin->fresh());

        $this->assertTrue($expectedNavigation['systemAdministration']);
        $this->assertTrue($expectedNavigation['mayorOffice']);

        $this->actingAs($admin)->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('workspaceExperience', 'system_administration')
                ->where('permissions.navigation', $expectedNavigation));
    }

    private function assure(User $user): void
    {
        $assurance = app(AuthenticationAssurance::class);
        if (! $assurance->requiresMfa($user)) {
            return;
        }

        $user->forceFill([
            'mfa_secret' => 'JBSWY3DPEHPK3PXP',
            'mfa_confirmed_at' => now(),
            'mfa_version' => max(1, (int) $user->mfa_version),
        ])->save();
        $user->refresh();

        $this->withSession([
            AuthenticationAssurance::SESSION_USER_KEY => $user->id,
            AuthenticationAssurance::SESSION_VERSION_KEY => $user->mfa_version,
            AuthenticationAssurance::SESSION_VERIFIED_AT_KEY => now()->getTimestamp(),
        ]);
    }
}
