<?php

namespace App\Services;

use App\Domain\Workflow\Events\WorkflowTransactionCreated;
use App\Domain\Workflow\Events\WorkflowTransactionTransitioned;
use App\Domain\Workflow\ResolvedWorkflowTransition;
use App\Domain\Workflow\SlaResolver;
use App\Domain\Workflow\WorkflowDefinition;
use App\Domain\Workflow\WorkflowDefinitionResolver;
use App\Domain\Workflow\WorkflowDestinationResolver;
use App\Domain\Workflow\WorkflowTransitionRule;
use App\Models\Department;
use App\Models\Employee;
use App\Models\TransactionEvent;
use App\Models\User;
use App\Models\WorkflowTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;

class TransactionWorkflowService
{
    public function __construct(
        private readonly WorkflowDefinitionResolver $definitions,
        private readonly WorkflowDestinationResolver $destinations,
        private readonly SlaResolver $sla,
    ) {
    }

    public function create(User $actor, array $data): WorkflowTransaction
    {
        $originDepartmentId = $this->originDepartmentId($actor);
        $targetDepartment = $this->receivingDepartment(
            (int) $data['target_department_id'],
            $originDepartmentId,
        );

        return DB::transaction(
            fn (): WorkflowTransaction => $this->createWithinTransaction(
                $actor,
                $data,
                $originDepartmentId,
                $targetDepartment,
            ),
        );
    }

    public function createWithinExistingTransaction(User $actor, array $data): WorkflowTransaction
    {
        if (DB::connection()->transactionLevel() < 1) {
            throw new LogicException('Workflow creation through this boundary requires an existing database transaction.');
        }

        $originDepartmentId = $this->originDepartmentId($actor);
        $targetDepartment = $this->receivingDepartment(
            (int) $data['target_department_id'],
            $originDepartmentId,
        );

        return $this->createWithinTransaction(
            $actor,
            $data,
            $originDepartmentId,
            $targetDepartment,
        );
    }

    public function transition(
        User $actor,
        WorkflowTransaction $transaction,
        string $action,
        ?int $targetDepartmentId = null,
        ?int $assignedEmployeeId = null,
        ?string $remarks = null,
    ): WorkflowTransaction {
        return DB::transaction(
            fn (): WorkflowTransaction => $this->transitionWithinTransaction(
                $actor,
                $transaction,
                $action,
                $targetDepartmentId,
                $assignedEmployeeId,
                $remarks,
            ),
        );
    }

    private function createWithinTransaction(
        User $actor,
        array $data,
        int $originDepartmentId,
        Department $targetDepartment,
    ): WorkflowTransaction {
        $definition = $this->definitions->resolve($data['transaction_type']);
        $transaction = $this->persistTransaction(
            $actor,
            $data,
            $definition,
            $originDepartmentId,
            $targetDepartment,
        );
        $event = $this->recordCreatedEvent(
            $transaction,
            $actor,
            $originDepartmentId,
            (int) $targetDepartment->id,
            $data['remarks'] ?? null,
        );

        event(new WorkflowTransactionCreated(
            transactionId: $transaction->id,
            transactionEventId: $event->id,
            actorUserId: $actor->id,
        ));

        return $this->freshTransaction($transaction);
    }

    private function transitionWithinTransaction(
        User $actor,
        WorkflowTransaction $transaction,
        string $action,
        ?int $targetDepartmentId,
        ?int $assignedEmployeeId,
        ?string $remarks,
    ): WorkflowTransaction {
        $locked = WorkflowTransaction::query()->lockForUpdate()->findOrFail($transaction->id);
        $definition = $this->definitions->resolve($locked);
        $this->assertMutable($locked, $definition);

        $rule = $definition->transition($action);
        $resolved = $this->resolveTransition(
            $locked,
            $rule,
            $targetDepartmentId,
            $assignedEmployeeId,
            $remarks,
        );

        $this->applyTransition($locked, $rule, $resolved);
        $event = $this->recordTransitionEvent($locked, $actor, $action, $resolved);

        event(new WorkflowTransactionTransitioned(
            transactionId: $locked->id,
            transactionEventId: $event->id,
            actorUserId: $actor->id,
            action: $action,
            assignmentEmployeeId: $resolved->assignmentEmployeeId,
        ));

        return $this->freshTransaction($locked);
    }

    private function persistTransaction(
        User $actor,
        array $data,
        WorkflowDefinition $definition,
        int $originDepartmentId,
        Department $targetDepartment,
    ): WorkflowTransaction {
        $transaction = WorkflowTransaction::query()->create([
            'transaction_type' => $data['transaction_type'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'priority' => $data['priority'],
            'origin_department_id' => $originDepartmentId,
            'current_department_id' => $targetDepartment->id,
            'created_by_user_id' => $actor->id,
            'status' => $definition->initialStatus(),
            'received_at' => now(),
            'due_at' => $this->sla->resolve($data['priority'], $data['due_at'] ?? null),
        ]);

        $transaction->update([
            'reference_no' => sprintf('TAL-%s-%06d', now()->format('Y'), $transaction->id),
        ]);

        return $transaction;
    }

    private function resolveTransition(
        WorkflowTransaction $transaction,
        WorkflowTransitionRule $rule,
        ?int $targetDepartmentId,
        ?int $assignedEmployeeId,
        ?string $remarks,
    ): ResolvedWorkflowTransition {
        $fromDepartmentId = (int) $transaction->current_department_id;
        $assignment = $this->resolveAssignment(
            $rule,
            $fromDepartmentId,
            $assignedEmployeeId,
            $transaction->assigned_employee_id,
        );

        return new ResolvedWorkflowTransition(
            previousStatus: $transaction->status,
            newStatus: $rule->status ?? $transaction->status,
            fromDepartmentId: $fromDepartmentId,
            toDepartmentId: $this->destinations->resolve($rule, $transaction, $targetDepartmentId),
            assignmentEmployeeId: $assignment,
            remarks: $this->eventRemarks($rule, $assignment, $remarks),
        );
    }

    private function applyTransition(
        WorkflowTransaction $transaction,
        WorkflowTransitionRule $rule,
        ResolvedWorkflowTransition $resolved,
    ): void {
        $transaction->update([
            'current_department_id' => $resolved->toDepartmentId,
            'assigned_employee_id' => $resolved->assignmentEmployeeId,
            'status' => $resolved->newStatus,
            'received_at' => $rule->refreshReceivedAt ? now() : $transaction->received_at,
            'completed_at' => $rule->completes ? now() : $transaction->completed_at,
        ]);
    }

    private function originDepartmentId(User $actor): int
    {
        $originDepartmentId = $actor->employee?->department_id;

        if (! $originDepartmentId || ! Department::query()->activeRoutable()->whereKey($originDepartmentId)->exists()) {
            throw ValidationException::withMessages([
                'department' => 'An active routable employee office is required.',
            ]);
        }

        return (int) $originDepartmentId;
    }

    private function receivingDepartment(int $targetDepartmentId, int $originDepartmentId): Department
    {
        if ($targetDepartmentId === $originDepartmentId) {
            throw ValidationException::withMessages([
                'target_department_id' => 'Select a different receiving office.',
            ]);
        }

        $targetDepartment = Department::query()->activeRoutable()->whereKey($targetDepartmentId)->first();
        if (! $targetDepartment) {
            throw ValidationException::withMessages([
                'target_department_id' => 'Select an active routable receiving office.',
            ]);
        }

        return $targetDepartment;
    }

    private function assertMutable(
        WorkflowTransaction $transaction,
        WorkflowDefinition $definition,
    ): void {
        if ($definition->isTerminal($transaction->status)) {
            throw ValidationException::withMessages([
                'action' => 'This transaction is already in a terminal state.',
            ]);
        }
    }

    private function resolveAssignment(
        WorkflowTransitionRule $rule,
        int $departmentId,
        ?int $assignedEmployeeId,
        ?int $currentAssignmentId,
    ): ?int {
        if (! $rule->requiresAssignment) {
            return $rule->clearAssignment ? null : $currentAssignmentId;
        }

        if (! $assignedEmployeeId) {
            throw ValidationException::withMessages([
                'assigned_employee_id' => 'Choose an employee from the current office.',
            ]);
        }

        return Employee::query()
            ->whereKey($assignedEmployeeId)
            ->where('department_id', $departmentId)
            ->where('employment_status', 'active')
            ->firstOrFail()
            ->id;
    }

    private function eventRemarks(
        WorkflowTransitionRule $rule,
        ?int $assignmentId,
        ?string $remarks,
    ): ?string {
        if (! $rule->requiresAssignment || ! $assignmentId) {
            return $remarks;
        }

        $employee = Employee::query()->findOrFail($assignmentId);

        return trim(($remarks ? $remarks.' ' : '')."Assigned to {$employee->full_name}.");
    }

    private function recordCreatedEvent(
        WorkflowTransaction $transaction,
        User $actor,
        int $fromDepartmentId,
        int $toDepartmentId,
        ?string $remarks,
    ): TransactionEvent {
        return $this->recordEvent(
            $transaction,
            $actor,
            'submitted',
            'draft',
            $transaction->status,
            $fromDepartmentId,
            $toDepartmentId,
            $remarks,
        );
    }

    private function recordTransitionEvent(
        WorkflowTransaction $transaction,
        User $actor,
        string $action,
        ResolvedWorkflowTransition $resolved,
    ): TransactionEvent {
        return $this->recordEvent(
            $transaction,
            $actor,
            $action,
            $resolved->previousStatus,
            $resolved->newStatus,
            $resolved->fromDepartmentId,
            $resolved->toDepartmentId,
            $resolved->remarks,
        );
    }

    private function recordEvent(
        WorkflowTransaction $transaction,
        User $actor,
        string $action,
        string $previousStatus,
        string $newStatus,
        int $fromDepartmentId,
        int $toDepartmentId,
        ?string $remarks,
    ): TransactionEvent {
        return TransactionEvent::query()->create([
            'transaction_id' => $transaction->id,
            'actor_user_id' => $actor->id,
            'from_department_id' => $fromDepartmentId,
            'to_department_id' => $toDepartmentId,
            'action' => $action,
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
            'remarks' => $remarks,
            'created_at' => now(),
        ]);
    }

    private function freshTransaction(WorkflowTransaction $transaction): WorkflowTransaction
    {
        return $transaction->fresh([
            'originDepartment',
            'currentDepartment',
            'creator',
            'assignedEmployee',
        ]);
    }
}
