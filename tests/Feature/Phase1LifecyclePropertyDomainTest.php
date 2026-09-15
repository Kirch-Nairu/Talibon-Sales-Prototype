<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Department;
use App\Models\Employee;
use App\Models\OnboardingTask;
use App\Models\User;
use App\Models\WorkflowTransaction;
use App\Services\AssetAccountabilityService;
use App\Services\EmployeeLifecycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class Phase1LifecyclePropertyDomainTest extends TestCase
{
    use RefreshDatabase;

    public function test_onboarding_cannot_activate_employee_until_required_blockers_are_completed(): void
    {
        $this->seed();

        $hr = User::query()->where('email', 'hr@talibon.demo')->firstOrFail();
        $department = Department::query()->where('code', 'ENG')->firstOrFail();
        $service = app(EmployeeLifecycleService::class);

        $case = $service->startOnboarding($hr, [
            'full_name' => 'Phase One New Employee',
            'work_email' => 'phase1.new.employee@talibon.demo',
            'department_id' => $department->id,
            'position_title' => 'Administrative Officer',
            'employment_type' => 'regular',
            'planned_start_date' => now()->addDays(5)->toDateString(),
            'appointment_reference' => 'TEST-APPT-001',
        ]);

        $this->assertSame('onboarding', $case->employee->employment_status);
        $this->assertSame(7, $case->tasks()->where('is_required', true)->count());

        try {
            $service->completeOnboarding($hr, $case);
            $this->fail('Onboarding completed even though required blockers were still open.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('onboarding', $exception->errors());
        }

        OnboardingTask::query()->where('onboarding_case_id', $case->id)->orderBy('id')->get()
            ->each(fn (OnboardingTask $task) => $service->completeOnboardingTask($hr, $task, 'Verified in Phase 1 feature test.'));

        $completed = $service->completeOnboarding($hr, $case);
        $completed->employee->refresh()->load('user');

        $this->assertSame('completed', $completed->status);
        $this->assertSame('active', $completed->employee->employment_status);
        $this->assertNotNull($completed->employee->user_id);
        $this->assertTrue($completed->employee->user->is_active);
    }

    public function test_employee_transfer_creates_work_and_property_review_tasks_when_accountabilities_exist(): void
    {
        $this->seed();

        $hr = User::query()->where('email', 'hr@talibon.demo')->firstOrFail();
        $admin = User::query()->where('email', 'admin@talibon.demo')->firstOrFail();
        $employee = User::query()->where('email', 'employee@talibon.demo')->firstOrFail()->employee;
        $targetDepartment = Department::query()->where('code', 'ENG')->firstOrFail();

        $assetService = app(AssetAccountabilityService::class);
        $asset = $assetService->register($admin, [
            'property_number' => 'TEST-PROP-0001',
            'category' => 'ICT Equipment',
            'description' => 'Synthetic Phase 1 laptop',
            'condition' => 'good',
        ]);
        $assetService->assign($admin, $asset, $employee, ['reference_no' => 'TEST-PAR-001']);

        $transaction = WorkflowTransaction::query()
            ->whereNotIn('status', ['approved', 'disapproved', 'closed'])
            ->firstOrFail();
        $transaction->update(['assigned_employee_id' => $employee->id]);

        $movement = app(EmployeeLifecycleService::class)->applyMovement($hr, $employee, [
            'movement_type' => 'transfer',
            'effective_date' => now()->toDateString(),
            'to_department_id' => $targetDepartment->id,
            'to_position_title' => 'Technical Administrative Officer',
            'reason' => 'Synthetic Phase 1 movement test.',
        ]);

        $employee->refresh();
        $this->assertSame($targetDepartment->id, $employee->department_id);
        $this->assertDatabaseHas('employee_movement_tasks', [
            'employee_movement_id' => $movement->id,
            'task_key' => 'open_work_reassignment',
            'is_required' => true,
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('employee_movement_tasks', [
            'employee_movement_id' => $movement->id,
            'task_key' => 'property_accountability_review',
            'is_required' => true,
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('platform_notifications', [
            'user_id' => $employee->user_id,
            'event_key' => 'employee-movement-'.$movement->id,
        ]);
    }

    public function test_property_issue_and_return_preserve_append_only_asset_events(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@talibon.demo')->firstOrFail();
        $employee = User::query()->where('email', 'employee@talibon.demo')->firstOrFail()->employee;
        $service = app(AssetAccountabilityService::class);

        $asset = $service->register($admin, [
            'property_number' => 'TEST-PROP-0002',
            'category' => 'Office Equipment',
            'description' => 'Synthetic Phase 1 monitor',
        ]);
        $assignment = $service->assign($admin, $asset, $employee, [
            'reference_no' => 'TEST-ICS-001',
            'condition_at_issue' => 'good',
        ]);

        $asset->refresh();
        $this->assertSame($employee->id, $asset->accountable_employee_id);
        $this->assertNull($assignment->returned_at);

        $returned = $service->returnAsset($admin, $asset, [
            'condition_at_return' => 'good',
            'remarks' => 'Returned in Phase 1 feature test.',
        ]);

        $this->assertNull($returned->accountable_employee_id);
        $this->assertSame('available', $returned->status);
        $this->assertDatabaseHas('asset_events', ['asset_id' => $asset->id, 'event_type' => 'registered']);
        $this->assertDatabaseHas('asset_events', ['asset_id' => $asset->id, 'event_type' => 'issued']);
        $this->assertDatabaseHas('asset_events', ['asset_id' => $asset->id, 'event_type' => 'returned']);
        $this->assertSame(3, Asset::query()->findOrFail($asset->id)->events()->count());
    }
}
