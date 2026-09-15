<?php

namespace App\Services;

use App\Models\AssetAssignment;
use App\Models\CalendarEvent;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeMovement;
use App\Models\EmployeeMovementTask;
use App\Models\OnboardingCase;
use App\Models\OnboardingTask;
use App\Models\User;
use App\Models\WorkflowTransaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EmployeeLifecycleService
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly PlatformNotificationService $notifications,
    ) {
    }

    public function startOnboarding(User $actor, array $data): OnboardingCase
    {
        $this->assertHrActor($actor);

        return DB::transaction(function () use ($actor, $data): OnboardingCase {
            $department = Department::query()->activeRoutable()->whereKey((int) $data['department_id'])->first();
            if (! $department) {
                throw ValidationException::withMessages(['department_id' => 'Select an active routable office.']);
            }

            if (User::query()->where('email', $data['work_email'])->exists()) {
                throw ValidationException::withMessages(['work_email' => 'That work email is already assigned to a portal identity.']);
            }

            if (Employee::query()->where('work_email', $data['work_email'])->exists()) {
                throw ValidationException::withMessages(['work_email' => 'That work email is already assigned to an employee record.']);
            }

            $supervisorId = $data['supervisor_employee_id'] ?? null;
            if ($supervisorId && ! Employee::query()
                ->whereKey((int) $supervisorId)
                ->where('department_id', $department->id)
                ->where('employment_status', 'active')
                ->exists()) {
                throw ValidationException::withMessages(['supervisor_employee_id' => 'The supervisor must be an active employee of the target office.']);
            }

            do {
                $employeeNumber = sprintf('TAL-ONB-%s-%s', now()->format('ymd'), Str::upper(Str::random(5)));
            } while (Employee::query()->where('employee_number', $employeeNumber)->exists());

            $employee = Employee::query()->create([
                'employee_number' => $employeeNumber,
                'full_name' => $data['full_name'],
                'work_email' => $data['work_email'],
                'department_id' => $department->id,
                'supervisor_employee_id' => $supervisorId,
                'position_title' => $data['position_title'],
                'employment_status' => 'onboarding',
                'employment_type' => $data['employment_type'] ?? null,
                'appointment_date' => $data['appointment_date'] ?? null,
                'employment_start_date' => $data['planned_start_date'] ?? null,
            ]);

            $case = OnboardingCase::query()->create([
                'employee_id' => $employee->id,
                'status' => 'in_progress',
                'appointment_reference' => $data['appointment_reference'] ?? null,
                'target_department_id' => $department->id,
                'target_position_title' => $data['position_title'],
                'supervisor_employee_id' => $supervisorId,
                'planned_start_date' => $data['planned_start_date'] ?? null,
                'started_by_user_id' => $actor->id,
                'started_at' => now(),
            ]);

            $hr = Department::query()->where('code', 'HRMO')->first();
            $gso = Department::query()->where('code', 'GSO')->first();
            $dueAt = isset($data['planned_start_date']) && $data['planned_start_date']
                ? Carbon::parse($data['planned_start_date'])->endOfDay()
                : now()->addDays(5)->endOfDay();

            foreach ([
                ['portal_identity', 'Create portal identity', 'identity', $hr?->id],
                ['required_documents', 'Validate required employment documents', 'records', $hr?->id],
                ['leave_setup', 'Initialize leave and HR accounts', 'hr', $hr?->id],
                ['payroll_setup', 'Complete payroll setup checklist', 'payroll', $hr?->id],
                ['biometric_enrollment', 'Complete biometric enrollment task', 'attendance', $hr?->id],
                ['property_accountability', 'Review property issuance requirements', 'property', $gso?->id],
                ['orientation_acknowledgement', 'Complete orientation and policy acknowledgement', 'orientation', $hr?->id],
            ] as [$key, $title, $category, $ownerDepartmentId]) {
                OnboardingTask::query()->create([
                    'onboarding_case_id' => $case->id,
                    'task_key' => $key,
                    'title' => $title,
                    'category' => $category,
                    'owner_department_id' => $ownerDepartmentId,
                    'is_required' => true,
                    'status' => 'pending',
                    'due_at' => $dueAt,
                ]);
            }

            if ($case->planned_start_date) {
                CalendarEvent::query()->updateOrCreate(
                    ['event_key' => 'onboarding-start-'.$case->id],
                    [
                        'event_type' => 'hr_onboarding',
                        'title' => 'Onboarding start · '.$employee->full_name,
                        'description' => 'Planned employment onboarding start date.',
                        'scope' => 'department',
                        'department_id' => $department->id,
                        'source_domain' => 'hr_onboarding',
                        'source_type' => OnboardingCase::class,
                        'source_id' => $case->id,
                        'priority' => 'action_required',
                        'starts_at' => $case->planned_start_date->copy()->startOfDay()->addHours(8),
                        'all_day' => false,
                        'action_url' => '/hris/admin/lifecycle/onboarding/'.$case->id,
                        'status' => 'scheduled',
                        'created_by_user_id' => $actor->id,
                    ],
                );
            }

            $this->notifications->notifyUser($actor, [
                'event_key' => 'onboarding-case-started-'.$case->id,
                'source_domain' => 'hr_onboarding',
                'source_type' => OnboardingCase::class,
                'source_id' => $case->id,
                'priority' => 'action_required',
                'title' => 'Onboarding case started',
                'message' => $employee->full_name.' has required onboarding blockers to complete.',
                'action_url' => '/hris/admin/lifecycle/onboarding/'.$case->id,
            ]);

            $this->notifications->notifyDepartment($department, [
                'event_key' => 'onboarding-target-office-'.$case->id,
                'source_domain' => 'hr_onboarding',
                'source_type' => OnboardingCase::class,
                'source_id' => $case->id,
                'priority' => 'info',
                'title' => 'Employee onboarding planned',
                'message' => $employee->full_name.' is being onboarded as '.$employee->position_title.'.',
                'action_url' => '/employees/'.$employee->id,
            ]);

            if ($gso) {
                $this->notifications->notifyDepartment($gso, [
                    'event_key' => 'onboarding-property-review-'.$case->id,
                    'source_domain' => 'hr_onboarding',
                    'source_type' => OnboardingCase::class,
                    'source_id' => $case->id,
                    'priority' => 'action_required',
                    'title' => 'Onboarding property review',
                    'message' => 'Review property issuance requirements for '.$employee->full_name.'.',
                    'action_url' => '/property',
                ]);
            }

            $this->audit->record(
                $actor,
                'hr.onboarding.started',
                'Started onboarding for '.$employee->employee_number.' · '.$employee->full_name.'.',
                'allowed',
                OnboardingCase::class,
                $case->id,
            );

            return $case->fresh(['employee', 'tasks.ownerDepartment', 'targetDepartment']);
        });
    }

    public function completeOnboardingTask(User $actor, OnboardingTask $task, ?string $notes = null): OnboardingTask
    {
        return DB::transaction(function () use ($actor, $task, $notes): OnboardingTask {
            $locked = OnboardingTask::query()->lockForUpdate()->findOrFail($task->id);
            $this->assertTaskActor($actor, $locked->owner_department_id);

            if (in_array($locked->status, ['completed', 'waived'], true)) {
                return $locked;
            }

            $case = OnboardingCase::query()->with('employee.user')->lockForUpdate()->findOrFail($locked->onboarding_case_id);
            if ($case->status === 'completed') {
                throw ValidationException::withMessages(['task' => 'The onboarding case is already complete.']);
            }

            $employee = $case->employee;
            if ($locked->task_key === 'portal_identity' && ! $employee->user_id) {
                if (! $employee->work_email) {
                    throw ValidationException::withMessages(['task' => 'A work email is required before the portal identity task can be completed.']);
                }

                $existing = User::query()->where('email', $employee->work_email)->first();
                if ($existing) {
                    $existing->loadMissing('employee');
                }

                if ($existing?->employee && (int) $existing->employee->id !== (int) $employee->id) {
                    throw ValidationException::withMessages(['task' => 'The work email is already linked to another employee.']);
                }

                $user = $existing ?? User::query()->create([
                    'name' => $employee->full_name,
                    'email' => $employee->work_email,
                    'password' => Str::random(48),
                    'role' => 'employee',
                    'is_active' => false,
                ]);

                if ($user->is_active) {
                    $user->update(['is_active' => false]);
                }

                $employee->update(['user_id' => $user->id]);
            }

            $locked->update([
                'status' => 'completed',
                'completed_by_user_id' => $actor->id,
                'completed_at' => now(),
                'notes' => $notes,
            ]);

            $this->audit->record(
                $actor,
                'hr.onboarding.task_completed',
                'Completed onboarding task '.$locked->task_key.' for '.$employee->employee_number.'.',
                'allowed',
                OnboardingTask::class,
                $locked->id,
            );

            return $locked->fresh(['ownerDepartment', 'completer']);
        });
    }

    public function completeOnboarding(User $actor, OnboardingCase $case): OnboardingCase
    {
        $this->assertHrActor($actor);

        return DB::transaction(function () use ($actor, $case): OnboardingCase {
            $locked = OnboardingCase::query()->lockForUpdate()->findOrFail($case->id);
            if ($locked->status === 'completed') {
                return $locked;
            }

            $blocking = OnboardingTask::query()
                ->where('onboarding_case_id', $locked->id)
                ->where('is_required', true)
                ->whereNotIn('status', ['completed', 'waived'])
                ->pluck('title');

            if ($blocking->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'onboarding' => 'Resolve required blockers before activation: '.$blocking->join(', '),
                ]);
            }

            $employee = Employee::query()->with('user')->lockForUpdate()->findOrFail($locked->employee_id);
            if (! $employee->user_id || ! $employee->user) {
                throw ValidationException::withMessages(['onboarding' => 'Portal identity must exist before onboarding can be completed.']);
            }

            $locked->update([
                'status' => 'completed',
                'completed_by_user_id' => $actor->id,
                'completed_at' => now(),
            ]);
            $employee->update(['employment_status' => 'active']);
            $employee->user->update(['is_active' => true]);

            CalendarEvent::query()
                ->where('event_key', 'onboarding-start-'.$locked->id)
                ->where('starts_at', '<=', now())
                ->update(['status' => 'completed']);

            $this->notifications->notifyUser($employee->user, [
                'event_key' => 'onboarding-completed-'.$locked->id,
                'source_domain' => 'hr_onboarding',
                'source_type' => OnboardingCase::class,
                'source_id' => $locked->id,
                'priority' => 'info',
                'title' => 'Employment onboarding completed',
                'message' => 'Your municipal employee profile and portal identity are now active.',
                'action_url' => '/employees/'.$employee->id,
            ]);

            $this->audit->record(
                $actor,
                'hr.onboarding.completed',
                'Completed onboarding and activated '.$employee->employee_number.' · '.$employee->full_name.'.',
                'allowed',
                OnboardingCase::class,
                $locked->id,
            );

            return $locked->fresh(['employee.user', 'tasks', 'targetDepartment']);
        });
    }

    public function applyMovement(User $actor, Employee $employee, array $data): EmployeeMovement
    {
        $this->assertHrActor($actor);

        return DB::transaction(function () use ($actor, $employee, $data): EmployeeMovement {
            $lockedEmployee = Employee::query()->with(['department', 'user'])->lockForUpdate()->findOrFail($employee->id);
            if ($lockedEmployee->employment_status !== 'active') {
                throw ValidationException::withMessages(['employee' => 'Employment movement can only be applied to an active employee.']);
            }

            $effectiveDate = Carbon::parse($data['effective_date'])->startOfDay();
            if ($effectiveDate->isFuture()) {
                throw ValidationException::withMessages(['effective_date' => 'This action applies immediately. Use today or an earlier effective date. Scheduled movements require a separate approval workflow.']);
            }

            $targetDepartment = Department::query()->activeRoutable()->whereKey((int) $data['to_department_id'])->first();
            if (! $targetDepartment) {
                throw ValidationException::withMessages(['to_department_id' => 'Select an active routable destination office.']);
            }

            if (! in_array($data['movement_type'], ['transfer', 'promotion', 'reassignment', 'acting_assignment'], true)) {
                throw ValidationException::withMessages(['movement_type' => 'Unsupported employment movement type.']);
            }

            $newPosition = $data['to_position_title'] ?? $lockedEmployee->position_title;
            $newSupervisor = $data['new_supervisor_employee_id'] ?? $lockedEmployee->supervisor_employee_id;
            if ($newSupervisor && ! Employee::query()
                ->whereKey((int) $newSupervisor)
                ->where('department_id', $targetDepartment->id)
                ->where('employment_status', 'active')
                ->exists()) {
                throw ValidationException::withMessages(['new_supervisor_employee_id' => 'The new supervisor must be an active employee of the destination office.']);
            }

            if ((int) $targetDepartment->id === (int) $lockedEmployee->department_id
                && $newPosition === $lockedEmployee->position_title
                && (int) ($newSupervisor ?? 0) === (int) ($lockedEmployee->supervisor_employee_id ?? 0)) {
                throw ValidationException::withMessages(['movement' => 'The movement must change office, position, or supervisor.']);
            }

            $fromDepartmentId = $lockedEmployee->department_id;
            $officeChanged = (int) $targetDepartment->id !== (int) $fromDepartmentId;
            $movement = EmployeeMovement::query()->create([
                'employee_id' => $lockedEmployee->id,
                'movement_type' => $data['movement_type'],
                'effective_date' => $effectiveDate->toDateString(),
                'from_department_id' => $fromDepartmentId,
                'to_department_id' => $targetDepartment->id,
                'from_position_title' => $lockedEmployee->position_title,
                'to_position_title' => $newPosition,
                'previous_supervisor_employee_id' => $lockedEmployee->supervisor_employee_id,
                'new_supervisor_employee_id' => $newSupervisor,
                'reason' => $data['reason'] ?? null,
                'status' => 'applied',
                'initiated_by_user_id' => $actor->id,
                'applied_at' => now(),
                'metadata' => ['authorization_anchor' => 'employee.department_id'],
            ]);

            $lockedEmployee->update([
                'department_id' => $targetDepartment->id,
                'position_title' => $newPosition,
                'supervisor_employee_id' => $newSupervisor,
            ]);

            $openWorkCount = WorkflowTransaction::query()
                ->where('assigned_employee_id', $lockedEmployee->id)
                ->whereNotIn('status', ['approved', 'disapproved', 'closed'])
                ->count();
            $propertyCount = AssetAssignment::query()
                ->where('employee_id', $lockedEmployee->id)
                ->whereNull('returned_at')
                ->count();

            $hr = Department::query()->where('code', 'HRMO')->first();
            $gso = Department::query()->where('code', 'GSO')->first();
            $oldOffice = Department::query()->find($fromDepartmentId);
            $dueAt = now()->addDays(3)->endOfDay();
            $workReviewRequired = $officeChanged && $openWorkCount > 0;
            $propertyReviewRequired = $officeChanged && $propertyCount > 0;

            foreach ([
                ['access_review', 'Review roles and office access after movement', $hr?->id, true, 'pending'],
                ['open_work_reassignment', 'Review and reassign open work items', $oldOffice?->id, $workReviewRequired, $workReviewRequired ? 'pending' : 'not_required'],
                ['property_accountability_review', 'Review assigned property and office accountability', $gso?->id, $propertyReviewRequired, $propertyReviewRequired ? 'pending' : 'not_required'],
            ] as [$key, $title, $ownerDepartmentId, $required, $status]) {
                EmployeeMovementTask::query()->create([
                    'employee_movement_id' => $movement->id,
                    'task_key' => $key,
                    'title' => $title,
                    'owner_department_id' => $ownerDepartmentId,
                    'is_required' => $required,
                    'status' => $status,
                    'due_at' => $required ? $dueAt : null,
                ]);
            }

            if ($lockedEmployee->user) {
                $this->notifications->notifyUser($lockedEmployee->user, [
                    'event_key' => 'employee-movement-'.$movement->id,
                    'source_domain' => 'hr_movement',
                    'source_type' => EmployeeMovement::class,
                    'source_id' => $movement->id,
                    'priority' => 'action_required',
                    'title' => 'Employment movement recorded',
                    'message' => 'Your office or employment assignment was updated effective '.$effectiveDate->toFormattedDateString().'.',
                    'action_url' => '/employees/'.$lockedEmployee->id,
                ]);
            }

            if ($workReviewRequired && $oldOffice) {
                $this->notifications->notifyDepartment($oldOffice, [
                    'event_key' => 'movement-work-review-'.$movement->id,
                    'source_domain' => 'hr_movement',
                    'source_type' => EmployeeMovement::class,
                    'source_id' => $movement->id,
                    'priority' => 'action_required',
                    'title' => 'Open work reassignment required',
                    'message' => $lockedEmployee->full_name.' moved offices with '.$openWorkCount.' open assigned work item(s).',
                    'action_url' => '/transactions',
                ]);
            }

            if ($propertyReviewRequired && $gso) {
                $this->notifications->notifyDepartment($gso, [
                    'event_key' => 'movement-property-review-'.$movement->id,
                    'source_domain' => 'hr_movement',
                    'source_type' => EmployeeMovement::class,
                    'source_id' => $movement->id,
                    'priority' => 'action_required',
                    'title' => 'Property accountability review required',
                    'message' => $lockedEmployee->full_name.' changed office assignment with '.$propertyCount.' active property assignment(s).',
                    'action_url' => '/property',
                ]);
            }

            $this->audit->record(
                $actor,
                'hr.employee.movement_applied',
                'Applied '.$movement->movement_type.' for '.$lockedEmployee->employee_number.' from office '.($oldOffice?->code ?? 'none').' to '.$targetDepartment->code.'.',
                'allowed',
                EmployeeMovement::class,
                $movement->id,
            );

            return $movement->fresh(['employee.department', 'fromDepartment', 'toDepartment', 'tasks.ownerDepartment']);
        });
    }

    public function completeMovementTask(User $actor, EmployeeMovementTask $task, ?string $notes = null): EmployeeMovementTask
    {
        return DB::transaction(function () use ($actor, $task, $notes): EmployeeMovementTask {
            $locked = EmployeeMovementTask::query()->lockForUpdate()->findOrFail($task->id);
            $this->assertTaskActor($actor, $locked->owner_department_id);

            if (in_array($locked->status, ['completed', 'not_required', 'waived'], true)) {
                return $locked;
            }

            $locked->update([
                'status' => 'completed',
                'completed_by_user_id' => $actor->id,
                'completed_at' => now(),
                'notes' => $notes,
            ]);

            $this->audit->record(
                $actor,
                'hr.employee.movement_task_completed',
                'Completed movement review task '.$locked->task_key.'.',
                'allowed',
                EmployeeMovementTask::class,
                $locked->id,
            );

            return $locked->fresh(['movement.employee', 'ownerDepartment']);
        });
    }

    private function assertHrActor(User $actor): void
    {
        if (! $this->isHrActor($actor)) {
            throw ValidationException::withMessages(['authorization' => 'Authorized HR administration is required.']);
        }
    }

    private function assertTaskActor(User $actor, ?int $ownerDepartmentId): void
    {
        if ($this->isHrActor($actor)) {
            return;
        }

        $actor->loadMissing('employee.department');
        $isOwnerOffice = $ownerDepartmentId !== null
            && (int) ($actor->employee?->department_id ?? 0) === (int) $ownerDepartmentId;
        $hasOfficeAuthority = $actor->isRole('department_head', 'department_staff', 'legislative_staff', 'mayor_staff');

        if (! $isOwnerOffice || ! $hasOfficeAuthority) {
            throw ValidationException::withMessages(['authorization' => 'The owning office or authorized HR administration must complete this task.']);
        }
    }

    private function isHrActor(User $actor): bool
    {
        $actor->loadMissing('employee.department');

        return $actor->isRole('system_admin')
            || ($actor->isRole('hr_officer') && $actor->employee?->department?->code === 'HRMO');
    }
}
