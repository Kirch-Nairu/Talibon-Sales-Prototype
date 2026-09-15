<?php

namespace Tests\Feature;

use App\Models\AssetAssignment;
use App\Models\OffboardingTask;
use App\Models\User;
use App\Models\WorkflowTransaction;
use App\Services\EmployeeOffboardingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class Phase1OffboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_offboarding_generates_cross_office_clearance_blockers(): void
    {
        $this->seed();
        $hr = User::query()->where('email', 'hr@talibon.demo')->firstOrFail();
        $employee = User::query()->where('email', 'engineering@talibon.demo')->firstOrFail()->employee;
        $case = app(EmployeeOffboardingService::class)->start($hr, $employee, ['separation_type' => 'resignation', 'effective_date' => now()->addDays(7)->toDateString(), 'reason' => 'Phase 1 offboarding test.']);
        $this->assertSame('in_progress', $case->status);
        $this->assertTrue($case->tasks->contains('task_key', 'department_clearance'));
        $this->assertTrue($case->tasks->contains('task_key', 'financial_clearance'));
        $this->assertTrue($case->tasks->contains('task_key', 'biometric_disable'));
        $this->assertTrue($case->tasks->contains('task_key', 'access_revocation'));
    }

    public function test_open_work_is_a_live_blocker_not_a_checkbox_bypass(): void
    {
        $this->seed();
        $hr = User::query()->where('email', 'hr@talibon.demo')->firstOrFail();
        $employee = User::query()->where('email', 'engineering@talibon.demo')->firstOrFail()->employee;
        $service = app(EmployeeOffboardingService::class);
        $case = $service->start($hr, $employee, ['separation_type' => 'resignation', 'effective_date' => now()->addDays(7)->toDateString()]);
        $task = OffboardingTask::query()->where('offboarding_case_id', $case->id)->where('task_key', 'open_work_reassignment')->firstOrFail();
        if (WorkflowTransaction::query()->where('assigned_employee_id', $employee->id)->whereNotIn('status', ['approved', 'disapproved', 'closed'])->exists()) {
            $this->expectException(ValidationException::class);
            $service->completeTask($hr, $task);
            return;
        }
        $this->assertContains($task->status, ['not_required', 'pending']);
    }

    public function test_finalization_requires_clearances_and_deactivates_identity(): void
    {
        $this->seed();
        $hr = User::query()->where('email', 'hr@talibon.demo')->firstOrFail();
        $employeeUser = User::query()->where('email', 'employee@talibon.demo')->firstOrFail();
        $employee = $employeeUser->employee;
        $service = app(EmployeeOffboardingService::class);
        WorkflowTransaction::query()->where('assigned_employee_id', $employee->id)->update(['assigned_employee_id' => null, 'assigned_to_user_id' => null]);
        AssetAssignment::query()->where('employee_id', $employee->id)->whereNull('returned_at')->update(['returned_at' => now()]);
        $case = $service->start($hr, $employee, ['separation_type' => 'retirement', 'effective_date' => now()->toDateString()]);
        foreach ($case->tasks()->where('is_required', true)->where('task_key', '!=', 'access_revocation')->whereNotIn('status', ['not_required'])->get() as $task) {
            $service->completeTask($hr, $task, 'Verified in automated Phase 1 test.');
        }
        $service->finalize($hr, $case);
        $employee->refresh(); $employeeUser->refresh(); $case->refresh();
        $this->assertSame('separated', $employee->employment_status);
        $this->assertNotNull($employee->separation_date);
        $this->assertFalse($employeeUser->is_active);
        $this->assertSame('completed', $case->status);
        $this->assertNotNull($case->archived_at);
        $this->assertDatabaseHas('offboarding_tasks', ['offboarding_case_id' => $case->id, 'task_key' => 'access_revocation', 'status' => 'completed']);
    }

    public function test_non_hr_actor_cannot_start_offboarding(): void
    {
        $this->seed();
        $engineering = User::query()->where('email', 'engineering@talibon.demo')->firstOrFail();
        $employee = User::query()->where('email', 'employee@talibon.demo')->firstOrFail()->employee;
        $this->expectException(ValidationException::class);
        app(EmployeeOffboardingService::class)->start($engineering, $employee, ['separation_type' => 'other', 'effective_date' => now()->toDateString()]);
    }
}
