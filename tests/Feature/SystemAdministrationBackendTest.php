<?php

namespace Tests\Feature;

use App\Http\Middleware\RequireMfaAssurance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SystemAdministrationBackendTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_admin_receives_factual_bounded_administration_workspace(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@talibon.demo')->firstOrFail();

        $response = $this->withoutMiddleware(RequireMfaAssurance::class)
            ->actingAs($admin)
            ->get('/admin');

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Index')
            ->where('overview.totalEmployees', 350)
            ->where('overview.portalUsers', 350)
            ->where('overview.activeUsers', 350)
            ->where('overview.inactiveUsers', 0)
            ->where('overview.employeesWithoutPortalAccounts', 0)
            ->has('overview.activeDepartments')
            ->has('overview.privilegedUsers')
            ->has('overview.mfaEnrolled')
            ->where('registry.per_page', 25)
            ->has('registry.data', 25)
            ->has('officeIdentities')
            ->has('operations.summary')
            ->has('operations.departmentWorkload')
            ->has('security'));

        $content = $response->getContent();
        foreach ([
            'personal_email',
            'home_address',
            'emergency_contact',
            'gsis_number',
            'philhealth_number',
            'pagibig_number',
            'tin_number',
            'mfa_secret',
            'mfa_recovery_codes',
        ] as $sensitiveField) {
            $this->assertStringNotContainsString($sensitiveField, $content);
        }
    }

    public function test_account_registry_search_and_filters_are_server_side(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@talibon.demo')->firstOrFail();

        $this->withoutMiddleware(RequireMfaAssurance::class)
            ->actingAs($admin)
            ->get('/admin?search=engineering%40talibon.demo')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('registry.total', 1)
                ->where('registry.data.0.loginEmail', 'engineering@talibon.demo'));

        $this->withoutMiddleware(RequireMfaAssurance::class)
            ->actingAs($admin)
            ->get('/admin?role=department_head&status=active')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('registry.total', 2)
                ->where('registryFilters.role', 'department_head')
                ->where('registryFilters.status', 'active'));
    }

    public function test_only_system_admin_can_enter_administration_workspace(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'employee@talibon.demo')->firstOrFail();

        foreach ([
            'department_head',
            'department_staff',
            'employee',
            'hr_officer',
            'mayor_staff',
            'mayor_approver',
            'legislative_staff',
        ] as $role) {
            $user->forceFill(['role' => $role])->save();

            $this->withoutMiddleware(RequireMfaAssurance::class)
                ->actingAs($user)
                ->get('/admin')
                ->assertForbidden();
        }
    }
}
