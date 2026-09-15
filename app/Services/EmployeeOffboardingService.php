<?php

namespace App\Services;

use App\Models\AssetAssignment;
use App\Models\Department;
use App\Models\Employee;
use App\Models\OffboardingCase;
use App\Models\OffboardingTask;
use App\Models\User;
use App\Models\WorkflowTransaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmployeeOffboardingService
{
    private const TERMINAL_WORK_STATUSES = ['approved', 'disapproved', 'closed'];

    public function __construct(
        private readonly AuditLogger $audit,
        private readonly PlatformNotificationService $notifications,
    ) {
    }

    public function start(User $actor, Employee $employee, array $data): OffboardingCase
    {
        $this->assertHrActor($actor);

        return DB::transaction(function () use ($actor, $employee, $data): OffboardingCase {
            $locked = Employee::query()->with(['department', 'user'])->lockForUpdate()->findOrFail($employee->id);
            if ($locked->employment_status !== 'active') {
                throw ValidationException::withMessages(['employee' => 'Only active employees can enter separation/offboarding.']);
            }

            if (OffboardingCase::query()->where('employee_id', $locked->id)->whereIn('status', ['in_progress', 'ready_for_finalization'])->exists()) {
                throw ValidationException::withMessages(['employee' => 'This employee already has an active offboarding case.']);
            }

            $effectiveDate = Carbon::parse($data['effective_date'])->startOfDay();
            $case = OffboardingCase::query()->create([
                'employee_id' => $locked->id,
                'separation_type' => $data['separation_type'],
                'effective_date' => $effectiveDate->toDateString(),
                'status' => 'in_progress',
                'reason' => $data['reason'] ?? null,
                'initiated_by_user_id' => $actor->id,
                'initiated_at' => now(),
            ]);

            $hr = Department::query()->where('code', 'HRMO')->first();
            $gso = Department::query()->where('code', 'GSO')->first();
            $accounting = Department::query()->where('code', 'ACCOUNTING')->first();
            $employeeOffice = $locked->department;
            $dueAt = $effectiveDate->copy()->endOfDay();
            $openWork = $this->openWorkCount($locked);
            $property = $this->openPropertyCount($locked);

            foreach ([
                ['department_clearance', 'Department clearance and handover', $employeeOffice?->id, true, 'pending'],
                ['open_work_reassignment', 'Reassign or formally dispose all open assigned work', $employeeOffice?->id, $openWork > 0, $openWork > 0 ? 'pending' : 'not_required'],
                ['property_return', 'Return or formally transfer all accountable LGU property', $gso?->id, $property > 0, $property > 0 ? 'pending' : 'not_required'],
                ['financial_clearance', 'Complete financial/accountability clearance', $accounting?->id, true, 'pending'],
                ['payroll_finalization', 'Complete payroll/final-pay administrative review', $hr?->id, true, 'pending'],
                ['leave_finalization', 'Finalize leave and HR balances', $hr?->id, true, 'pending'],
                ['document_handover', 'Confirm records and document custody handover', $employeeOffice?->id, true, 'pending'],
                ['biometric_disable', 'Confirm biometric disablement task', $hr?->id, true, 'pending'],
                ['access_revocation', 'Revoke portal access during finalization', $hr?->id, true, 'system_pending'],
            ] as [$key, $title, $ownerDepartmentId, $required, $status]) {
                OffboardingTask::query()->create([
                    'offboarding_case_id' => $case->id,
                    'task_key' => $key,
                    'title' => $title,
                    'owner_department_id' => $ownerDepartmentId,
                    'is_required' => $required,
                    'status' => $status,
                    'due_at' => $required ? $dueAt : null,
                ]);
            }

            $this->notifications->notifyUser($actor, [
                'event_key' => 'offboarding-started-'.$case->id,
                'source_domain' => 'hr_offboarding',
                'source_type' => OffboardingCase::class,
                'source_id' => $case->id,
                'priority' => 'action_required',
                'title' => 'Offboarding case started',
                'message' => $locked->full_name.' has separation clearances to resolve.',
                'action_url' => '/hris/admin/offboarding/'.$case->id,
            ]);

            foreach (collect([$employeeOffice, $gso, $accounting, $hr])->filter()->unique('id') as $department) {
                $this->notifications->notifyDepartment($department, [
                    'event_key' => 'offboarding-office-'.$case->id.'-'.$department->id,
                    'source_domain' => 'hr_offboarding',
                    'source_type' => OffboardingCase::class,
                    'source_id' => $case->id,
                    'priority' => 'action_required',
                    'title' => 'Employee clearance action required',
                    'message' => 'Review offboarding clearance for '.$locked->full_name.'.',
                    'action_url' => '/hris/admin/offboarding/'.$case->id,
                ]);
            }

            $this->audit->record($actor, 'hr.offboarding.started', 'Started offboarding for '.$locked->employee_number.' · '.$locked->full_name.'.', 'allowed', OffboardingCase::class, $case->id);

            return $case->fresh(['employee.department', 'tasks.ownerDepartment']);
        });
    }

    public function completeTask(User $actor, OffboardingTask $task, ?string $notes = null): OffboardingTask
    {
        return DB::transaction(function () use ($actor, $task, $notes): OffboardingTask {
            $locked = OffboardingTask::query()->lockForUpdate()->findOrFail($task->id);
            $this->assertTaskActor($actor, $locked->owner_department_id);

            if (in_array($locked->status, ['completed', 'not_required', 'waived'], true)) {
                return $locked;
            }
            if ($locked->task_key === 'access_revocation') {
                throw ValidationException::withMessages(['task' => 'Access revocation is completed automatically during final offboarding.']);
            }

            $case = OffboardingCase::query()->with('employee')->lockForUpdate()->findOrFail($locked->offboarding_case_id);
            if ($case->status === 'completed') {
                throw ValidationException::withMessages(['task' => 'The offboarding case is already completed.']);
            }
            if ($locked->task_key === 'open_work_reassignment' && $this->openWorkCount($case->employee) > 0) {
                throw ValidationException::withMessages(['task' => 'Open assigned transactions remain. Reassign or formally complete them first.']);
            }
            if ($locked->task_key === 'property_return' && $this->openPropertyCount($case->employee) > 0) {
                throw ValidationException::withMessages(['task' => 'Outstanding accountable property remains. Return or formally transfer it first.']);
            }

            $locked->update([
                'status' => 'completed',
                'completed_by_user_id' => $actor->id,
                'completed_at' => now(),
                'notes' => $notes,
            ]);

            $this->audit->record($actor, 'hr.offboarding.task_completed', 'Completed offboarding task '.$locked->task_key.' for case '.$case->id.'.', 'allowed', OffboardingTask::class, $locked->id);

            return $locked->fresh(['case.employee', 'ownerDepartment', 'completer']);
        });
    }

    public function finalize(User $actor, OffboardingCase $case): OffboardingCase
    {
        $this->assertHrActor($actor);

        return DB::transaction(function () use ($actor, $case): OffboardingCase {
            $locked = OffboardingCase::query()->lockForUpdate()->findOrFail($case->id);
            if ($locked->status === 'completed') {
                return $locked;
            }

            $employee = Employee::query()->with('user')->lockForUpdate()->findOrFail($locked->employee_id);
            if ($this->openWorkCount($employee) > 0) {
                throw ValidationException::withMessages(['offboarding' => 'Open assigned work remains and blocks finalization.']);
            }
            if ($this->openPropertyCount($employee) > 0) {
                throw ValidationException::withMessages(['offboarding' => 'Outstanding accountable property remains and blocks finalization.']);
            }

            $blocking = OffboardingTask::query()
                ->where('offboarding_case_id', $locked->id)
                ->where('is_required', true)
                ->where('task_key', '!=', 'access_revocation')
                ->whereNotIn('status', ['completed', 'waived', 'not_required'])
                ->pluck('title');
            if ($blocking->isNotEmpty()) {
                throw ValidationException::withMessages(['offboarding' => 'Resolve required clearances before finalization: '.$blocking->join(', ')]);
            }

            $now = now();
            $employee->update([
                'employment_status' => 'separated',
                'separation_date' => $locked->effective_date,
                'supervisor_employee_id' => null,
            ]);
            if ($employee->user) {
                $employee->user->update(['is_active' => false]);
            }

            OffboardingTask::query()->where('offboarding_case_id', $locked->id)->where('task_key', 'access_revocation')->update([
                'status' => 'completed',
                'completed_by_user_id' => $actor->id,
                'completed_at' => $now,
                'notes' => 'Portal access revoked automatically during final offboarding.',
                'updated_at' => $now,
            ]);

            $locked->update([
                'status' => 'completed',
                'completed_by_user_id' => $actor->id,
                'completed_at' => $now,
                'account_deactivated_at' => $employee->user ? $now : null,
                'archived_at' => $now,
            ]);

            $this->audit->record($actor, 'hr.offboarding.completed', 'Completed offboarding for '.$employee->employee_number.' · '.$employee->full_name.'; access deactivated and employment archived.', 'allowed', OffboardingCase::class, $locked->id);

            return $locked->fresh(['employee.user', 'employee.department', 'tasks.ownerDepartment']);
        });
    }

    private function openWorkCount(Employee $employee): int
    {
        return WorkflowTransaction::query()->where('assigned_employee_id', $employee->id)->whereNotIn('status', self::TERMINAL_WORK_STATUSES)->count();
    }

    private function openPropertyCount(Employee $employee): int
    {
        return AssetAssignment::query()->where('employee_id', $employee->id)->whereNull('returned_at')->count();
    }

    private function assertHrActor(User $actor): void
    {
        $actor->loadMissing('employee.department');
        if (! ($actor->isRole('system_admin') || ($actor->isRole('hr_officer') && $actor->employee?->department?->code === 'HRMO'))) {
            throw ValidationException::withMessages(['authorization' => 'Authorized HR administration is required.']);
        }
    }

    private function assertTaskActor(User $actor, ?int $ownerDepartmentId): void
    {
        $actor->loadMissing('employee.department');
        $isHr = $actor->isRole('system_admin') || ($actor->isRole('hr_officer') && $actor->employee?->department?->code === 'HRMO');
        if ($isHr) {
            return;
        }
        $sameOffice = $ownerDepartmentId !== null && (int) ($actor->employee?->department_id ?? 0) === (int) $ownerDepartmentId;
        $officeAuthority = $actor->isRole('department_head', 'department_staff', 'legislative_staff', 'mayor_staff');
        if (! $sameOffice || ! $officeAuthority) {
            throw ValidationException::withMessages(['authorization' => 'The owning office or authorized HR administration must complete this clearance.']);
        }
    }
}
