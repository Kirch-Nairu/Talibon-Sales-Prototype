<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase1IntegratedAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase_one_critical_surfaces_are_reachable_by_their_authorized_demo_roles(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@talibon.demo')->firstOrFail();
        $mayor = User::query()->where('email', 'mayor@talibon.demo')->firstOrFail();
        $hr = User::query()->where('email', 'hr@talibon.demo')->firstOrFail();

        $this->actingAs($admin)->get('/dashboard')->assertOk();
        $this->actingAs($admin)->get('/calendar')->assertOk();
        $this->actingAs($admin)->get('/property')->assertOk();
        $this->actingAs($admin)->get('/property/lifecycle')->assertOk();
        $this->actingAs($admin)->get('/legislative-workspace')->assertOk();
        $this->actingAs($admin)->get('/reports')->assertOk();
        $this->actingAs($admin)->get('/audit')->assertOk();

        $this->actingAs($mayor)->get('/mayor-office')->assertOk();
        $this->actingAs($mayor)->get('/reports')->assertOk();

        $this->actingAs($hr)->get('/hris')->assertOk();
        $this->actingAs($hr)->get('/hris/admin')->assertOk();
        $this->actingAs($hr)->get('/hris/admin/lifecycle')->assertOk();
        $this->actingAs($hr)->get('/hris/admin/offboarding')->assertOk();
        $this->actingAs($hr)->get('/hris/dtr')->assertOk();
        $this->actingAs($hr)->get('/hris/payroll')->assertOk();
    }

    public function test_normal_engineering_account_cannot_cross_privileged_phase_one_boundaries(): void
    {
        $this->seed();
        $engineering = User::query()->where('email', 'engineering@talibon.demo')->firstOrFail();

        foreach (['/mayor-office', '/legislative-workspace', '/property', '/property/lifecycle', '/audit', '/hris/admin', '/hris/admin/lifecycle', '/hris/admin/offboarding'] as $path) {
            $this->actingAs($engineering)->get($path)->assertForbidden();
        }

        $this->actingAs($engineering)->get('/reports')->assertOk();
    }

    public function test_phase_one_organization_seed_still_exposes_expected_routing_shape(): void
    {
        $this->seed();

        $routable = Department::query()->where('is_active', true)->where('is_routable', true);
        $this->assertSame(33, (clone $routable)->count());
        $this->assertSame(30, (clone $routable)->where('branch', 'executive')->count());
        $this->assertSame(3, (clone $routable)->where('branch', 'legislative')->count());
    }
}
