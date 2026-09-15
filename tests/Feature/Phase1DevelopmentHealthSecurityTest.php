<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\CalendarEvent;
use App\Models\EmployeeDevelopmentRecord;
use App\Models\EmployeeHealthAccessGrant;
use App\Models\EmployeeHealthRecord;
use App\Models\PerformanceRecord;
use App\Models\PlatformNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class Phase1DevelopmentHealthSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_can_record_performance_and_expiring_development_evidence(): void
    {
        $this->seed();

        $hr = User::query()->where('email', 'hr@talibon.demo')->firstOrFail();
        $employeeUser = User::query()->where('email', 'employee@talibon.demo')->firstOrFail();
        $employee = $employeeUser->employee;

        $this->actingAs($hr)->post('/hris/admin/development/employees/'.$employee->id.'/performance', [
            'period_start' => now()->startOfYear()->toDateString(),
            'period_end' => now()->endOfYear()->toDateString(),
            'rating' => 4.75,
            'rating_scale' => '5-point',
            'status' => 'reviewed',
            'summary' => 'Phase 1 governed performance record test.',
            'reviewed' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('performance_records', [
            'employee_id' => $employee->id,
            'status' => 'reviewed',
        ]);

        $expiry = now()->addDays(45)->toDateString();
        $this->actingAs($hr)->post('/hris/admin/development/employees/'.$employee->id.'/records', [
            'record_type' => 'certification',
            'title' => 'Phase 1 Expiring Certification',
            'provider' => 'Test Provider',
            'reference_no' => 'CERT-TEST-001',
            'attained_at' => now()->subYear()->toDateString(),
            'expires_at' => $expiry,
            'status' => 'active',
        ])->assertRedirect();

        $record = EmployeeDevelopmentRecord::query()->where('title', 'Phase 1 Expiring Certification')->firstOrFail();
        $this->assertDatabaseHas('calendar_events', [
            'event_key' => 'employee-development-expiry-'.$record->id,
            'user_id' => $employeeUser->id,
            'source_domain' => 'hr_development',
            'status' => 'scheduled',
        ]);
        $this->assertTrue(PlatformNotification::query()
            ->where('user_id', $employeeUser->id)
            ->where('event_key', 'employee-development-expiry-'.$record->id)
            ->exists());

        $this->actingAs($employeeUser)->get('/employees/'.$employee->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Employees/Show')
                ->where('permissions.canViewHrRecord', true)
                ->has('performanceRecords')
                ->has('developmentRecords'));
    }

    public function test_system_admin_does_not_receive_health_content_without_explicit_grant(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@talibon.demo')->firstOrFail();
        $target = User::query()->where('email', 'employee@talibon.demo')->firstOrFail()->employee;

        $this->actingAs($admin)->get('/hris/health/'.$target->id)->assertStatus(403);

        $this->assertTrue(AuditLog::query()
            ->where('actor_user_id', $admin->id)
            ->where('action', 'hr.health.access')
            ->where('outcome', 'denied')
            ->exists());
    }

    public function test_access_manager_can_explicitly_grant_hr_then_hr_can_manage_health_vault(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@talibon.demo')->firstOrFail();
        $hr = User::query()->where('email', 'hr@talibon.demo')->firstOrFail();
        $target = User::query()->where('email', 'employee@talibon.demo')->firstOrFail()->employee;

        $this->actingAs($admin)->post('/hris/health-access', [
            'user_id' => $hr->id,
            'employee_id' => $target->id,
            'can_manage' => true,
            'purpose' => 'Authorized Phase 1 employment-health administration test.',
            'expires_at' => now()->addDay()->toIso8601String(),
        ])->assertRedirect();

        $grant = EmployeeHealthAccessGrant::query()
            ->where('user_id', $hr->id)
            ->where('employee_id', $target->id)
            ->whereNull('revoked_at')
            ->firstOrFail();
        $this->assertTrue($grant->can_view);
        $this->assertTrue($grant->can_manage);

        $this->actingAs($hr)->get('/hris/health/'.$target->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Hris/HealthVault')
                ->where('canManage', true));

        $summary = 'Restricted employment-health test content.';
        $this->actingAs($hr)->post('/hris/health/'.$target->id, [
            'record_type' => 'health_clearance',
            'title' => 'Phase 1 Health Clearance Test',
            'issued_at' => now()->toDateString(),
            'valid_until' => now()->addMonth()->toDateString(),
            'status' => 'active',
            'summary' => $summary,
            'restriction_notes' => 'Synthetic restriction note.',
        ])->assertRedirect();

        $record = EmployeeHealthRecord::query()->where('title', 'Phase 1 Health Clearance Test')->firstOrFail();
        $this->assertSame($summary, $record->summary);
        $rawSummary = DB::table('employee_health_records')->where('id', $record->id)->value('summary');
        $this->assertNotSame($summary, $rawSummary);
    }

    public function test_department_head_cannot_view_another_employee_health_vault(): void
    {
        $this->seed();

        $head = User::query()->where('email', 'engineering@talibon.demo')->firstOrFail();
        $target = User::query()->where('email', 'employee@talibon.demo')->firstOrFail()->employee;

        $this->actingAs($head)->get('/hris/health/'.$target->id)->assertStatus(403);
    }

    public function test_expiry_sync_publishes_contract_review_without_changing_employment_state(): void
    {
        $this->seed();

        $hr = User::query()->where('email', 'hr@talibon.demo')->firstOrFail();
        $employeeUser = User::query()->where('email', 'employee@talibon.demo')->firstOrFail();
        $employee = $employeeUser->employee;
        $employee->update([
            'employment_status' => 'active',
            'contract_end_date' => now()->addDays(30)->toDateString(),
        ]);

        $this->actingAs($hr)->post('/hris/admin/development/sync-expiries')->assertRedirect();

        $employee->refresh();
        $this->assertSame('active', $employee->employment_status);
        $this->assertTrue(CalendarEvent::query()
            ->where('event_key', 'employee-contract-expiry-'.$employee->id)
            ->where('source_domain', 'hr_contract')
            ->exists());
        $this->assertTrue(PlatformNotification::query()
            ->where('user_id', $employeeUser->id)
            ->where('event_key', 'employee-contract-expiry-'.$employee->id)
            ->exists());
    }
}
