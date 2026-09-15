<?php

namespace App\Services;

use App\Domain\Workflow\WorkflowDefinition;
use App\Domain\Workflow\WorkflowDefinitionResolver;
use App\Models\Department;
use App\Models\Employee;
use App\Models\TransactionEvent;
use App\Models\User;
use App\Models\WorkflowTransaction;

final class TransactionLiveQuery
{
    public function __construct(
        private readonly WorkflowDefinitionResolver $definitions,
    ) {
    }

    /** @return array<string, mixed> */
    public function snapshot(
        User $actor,
        WorkflowTransaction $transaction,
        ?int $afterEventId = null,
        bool $includeEvents = true,
    ): array {
        $transaction->loadMissing([
            'currentDepartment:id,code,name,short_name',
            'assignedEmployee:id,employee_number,full_name,department_id,position_title',
        ]);

        $definition = $this->definitions->resolve($transaction);
        $terminal = $definition->isTerminal($transaction->status);
        $permissions = [
            'canTransition' => ! $terminal && $actor->can('transition', $transaction),
            'canMayorDecision' => ! $terminal && $actor->can('mayorDecision', $transaction),
            'canAssign' => ! $terminal && $actor->can('assign', $transaction),
        ];

        return [
            'transaction' => [
                'status' => $transaction->status,
                'current_department' => $this->office($transaction->currentDepartment),
                'assigned_employee' => $this->employee($transaction->assignedEmployee),
                'received_at' => $transaction->received_at?->toIso8601String(),
                'due_at' => $transaction->due_at?->toIso8601String(),
                'completed_at' => $transaction->completed_at?->toIso8601String(),
            ],
            'accountability' => $this->accountability($transaction, $definition),
            'permissions' => $permissions,
            'assignableEmployees' => $permissions['canAssign']
                ? $this->assignableEmployees($transaction)
                : [],
            'events' => $includeEvents
                ? $this->eventsAfter($transaction, $afterEventId)
                : [],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function assignableEmployees(WorkflowTransaction $transaction): array
    {
        return Employee::query()
            ->where('department_id', $transaction->current_department_id)
            ->where('employment_status', 'active')
            ->orderBy('full_name')
            ->limit(100)
            ->get(['id', 'employee_number', 'full_name', 'position_title'])
            ->map(fn (Employee $employee): array => [
                'id' => (int) $employee->id,
                'employee_number' => $employee->employee_number,
                'full_name' => $employee->full_name,
                'position_title' => $employee->position_title,
            ])
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function eventsAfter(
        WorkflowTransaction $transaction,
        ?int $afterEventId,
    ): array {
        return TransactionEvent::query()
            ->where('transaction_id', $transaction->id)
            ->when(
                $afterEventId !== null,
                fn ($query) => $query->where('id', '>', $afterEventId),
            )
            ->with([
                'actor:id,name',
                'fromDepartment:id,code,name,short_name',
                'toDepartment:id,code,name,short_name',
            ])
            ->orderBy('created_at')
            ->orderBy('id')
            ->limit(50)
            ->get()
            ->map(fn (TransactionEvent $event): array => [
                'id' => (int) $event->id,
                'action' => $event->action,
                'previous_status' => $event->previous_status,
                'new_status' => $event->new_status,
                'remarks' => $event->remarks,
                'created_at' => $event->created_at?->toIso8601String(),
                'actor' => [
                    'name' => $event->actor?->name ?? 'System',
                ],
                'from_department' => $this->office($event->fromDepartment),
                'to_department' => $this->office($event->toDepartment),
            ])
            ->all();
    }

    /** @return array<string, mixed> */
    private function accountability(
        WorkflowTransaction $transaction,
        WorkflowDefinition $definition,
    ): array {
        $dueState = 'on_track';

        if ($definition->isTerminal($transaction->status)) {
            $dueState = 'completed';
        } elseif ($transaction->due_at?->isPast()) {
            $dueState = 'overdue';
        } elseif ($transaction->due_at && $transaction->due_at->lessThanOrEqualTo(now()->addDay())) {
            $dueState = 'due_soon';
        }

        return [
            'dueState' => $dueState,
            'timeInCurrentOffice' => $transaction->received_at
                ? $transaction->received_at->diffForHumans(now(), true)
                : 'Not recorded',
            'receivedAt' => $transaction->received_at?->toIso8601String(),
            'dueAt' => $transaction->due_at?->toIso8601String(),
            'completedAt' => $transaction->completed_at?->toIso8601String(),
        ];
    }

    private function office(?Department $department): ?array
    {
        return $department ? [
            'id' => (int) $department->id,
            'code' => $department->code,
            'name' => $department->name,
            'short_name' => $department->short_name,
        ] : null;
    }

    private function employee(?Employee $employee): ?array
    {
        return $employee ? [
            'id' => (int) $employee->id,
            'employee_number' => $employee->employee_number,
            'full_name' => $employee->full_name,
            'position_title' => $employee->position_title,
        ] : null;
    }
}
