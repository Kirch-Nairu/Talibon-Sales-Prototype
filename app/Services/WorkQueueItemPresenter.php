<?php

namespace App\Services;

use App\Models\Department;
use App\Models\User;
use App\Models\WorkflowTransaction;

final class WorkQueueItemPresenter
{
    /** @return array<string, mixed> */
    public function present(WorkflowTransaction $transaction, User $actor): array
    {
        $active = ! in_array($transaction->status, $this->terminalStatuses(), true);
        $employeeId = $actor->employee?->id;
        $requiresAction = $active
            && $employeeId
            && (int) $transaction->assigned_employee_id === (int) $employeeId;

        return [
            'id' => $transaction->id,
            'reference' => $transaction->reference_no,
            'title' => $transaction->title,
            'transactionType' => $transaction->transaction_type,
            'priority' => $transaction->priority,
            'status' => $transaction->status,
            'originOffice' => $this->office($transaction->originDepartment),
            'currentOffice' => $this->office($transaction->currentDepartment),
            'assignedEmployee' => $transaction->assignedEmployee ? [
                'name' => $transaction->assignedEmployee->full_name,
                'position' => $transaction->assignedEmployee->position_title,
            ] : null,
            'receivedAt' => $transaction->received_at?->toIso8601String(),
            'dueAt' => $transaction->due_at?->toIso8601String(),
            'completedAt' => $transaction->completed_at?->toIso8601String(),
            'updatedAt' => $transaction->updated_at?->toIso8601String(),
            'ageInOffice' => $transaction->received_at?->diffForHumans(now(), true),
            'dueState' => $this->dueState($transaction, $active),
            'requiresAction' => $requiresAction,
            'expectedAction' => $this->expectedAction($transaction, $actor, $active, $requiresAction),
        ];
    }

    private function dueState(WorkflowTransaction $transaction, bool $active): string
    {
        if (! $active) {
            return 'completed';
        }

        if ($transaction->due_at?->isPast()) {
            return 'overdue';
        }

        return $transaction->due_at?->lessThanOrEqualTo(now()->addDay())
            ? 'due_soon'
            : 'on_track';
    }

    private function expectedAction(
        WorkflowTransaction $transaction,
        User $actor,
        bool $active,
        bool $requiresAction,
    ): string {
        if (! $active) {
            return 'No further workflow action';
        }

        if ($requiresAction) {
            return match ($transaction->status) {
                'for_approval' => 'Complete the executive decision',
                'for_review' => 'Review and take the next workflow action',
                'information_requested' => 'Provide the requested information',
                'returned' => 'Review the returned work',
                default => 'Open and take the next workflow action',
            };
        }

        $actorDepartmentId = $actor->employee?->department_id;
        if ($actor->isRole('department_head')
            && (int) $transaction->current_department_id === (int) $actorDepartmentId
            && ! $transaction->assigned_employee_id) {
            return 'Assign an office owner';
        }

        if ((int) $transaction->origin_department_id === (int) $actorDepartmentId
            && (int) $transaction->current_department_id !== (int) $actorDepartmentId) {
            $office = $transaction->currentDepartment?->short_name
                ?? $transaction->currentDepartment?->name
                ?? 'another office';

            return 'Awaiting action from '.$office;
        }

        if ($transaction->assignedEmployee) {
            return 'Assigned to '.$transaction->assignedEmployee->full_name;
        }

        return 'Open the authoritative workflow';
    }

    /** @return array{id:int,code:string,name:string,shortName:?string}|null */
    private function office(?Department $department): ?array
    {
        return $department ? [
            'id' => (int) $department->id,
            'code' => $department->code,
            'name' => $department->name,
            'shortName' => $department->short_name,
        ] : null;
    }

    /** @return array<int, string> */
    private function terminalStatuses(): array
    {
        return array_values(config('workflow.default.terminal_statuses', [
            'approved', 'disapproved', 'closed',
        ]));
    }
}
