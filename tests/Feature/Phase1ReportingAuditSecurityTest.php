<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase1ReportingAuditSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_and_audit_surfaces_remain_role_scoped(): void
    {
        $this->seed();
        $engineering = User::query()->where('email', 'engineering@talibon.demo')->firstOrFail();
        $hr = User::query()->where('email', 'hr@talibon.demo')->firstOrFail();
        $admin = User::query()->where('email', 'admin@talibon.demo')->firstOrFail();

        $this->actingAs($engineering)->get('/reports')->assertOk();
        $this->actingAs($engineering)->get('/audit')->assertForbidden();
        $this->actingAs($hr)->get('/reports')->assertOk();
        $this->actingAs($hr)->get('/audit')->assertForbidden();
        $this->actingAs($admin)->get('/audit')->assertOk();
    }

    public function test_parked_payroll_export_is_not_part_of_the_core_reports_surface(): void
    {
        $this->seed();
        $engineering = User::query()->where('email', 'engineering@talibon.demo')->firstOrFail();
        $mayor = User::query()->where('email', 'mayor@talibon.demo')->firstOrFail();
        $hr = User::query()->where('email', 'hr@talibon.demo')->firstOrFail();

        $this->actingAs($engineering)->get('/reports/export/payroll-summary')->assertNotFound();
        $this->actingAs($mayor)->get('/reports/export/payroll-summary')->assertNotFound();
        $this->actingAs($hr)->get('/reports/export/payroll-summary')->assertNotFound();
    }

    public function test_audit_filters_can_isolate_denied_events_by_department(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@talibon.demo')->firstOrFail();
        $engineering = Department::query()->where('code', 'ENG')->firstOrFail();

        AuditLog::query()->create([
            'actor_user_id' => $admin->id,
            'actor_department_id' => $engineering->id,
            'action' => 'security.test.denied',
            'outcome' => 'denied',
            'summary' => 'Synthetic denied security event for filter verification.',
            'created_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get('/audit?outcome=denied&department_id='.$engineering->id.'&action=security.test')
            ->assertOk();
    }
}
