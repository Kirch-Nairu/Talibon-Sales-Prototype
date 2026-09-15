<?php

namespace App\Services;

use App\Models\Department;
use App\Models\WorkflowTransaction;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;

final class DashboardWorkPresenter
{
    /** @param array<int, string> $terminalStatuses */
    public function present(
        WorkflowTransaction $transaction,
        array $terminalStatuses,
        CarbonInterface $now,
        CarbonInterface $dueSoonEnd,
    ): array {
        return [
            'reference' => $transaction->reference_no,
            'title' => $transaction->title,
            'transactionType' => Str::headline($transaction->transaction_type),
            'status' => $transaction->status,
            'priority' => $transaction->priority,
            'originOffice' => $this->office($transaction->originDepartment),
            'currentOffice' => $this->office($transaction->currentDepartment),
            'assignedEmployee' => $transaction->assignedEmployee ? [
                'name' => $transaction->assignedEmployee->full_name,
                'position' => $transaction->assignedEmployee->position_title,
            ] : null,
            'receivedAt' => $transaction->received_at?->toIso8601String(),
            'dueAt' => $transaction->due_at?->toIso8601String(),
            'updatedAt' => $transaction->updated_at?->toIso8601String(),
            'ageInOffice' => $transaction->received_at?->diffForHumans($now, true),
            'dueState' => $this->dueState($transaction, $terminalStatuses, $now, $dueSoonEnd),
            'detailUrl' => route('transactions.show', $transaction, false),
        ];
    }

    /** @param array<int, string> $terminalStatuses */
    private function dueState(
        WorkflowTransaction $transaction,
        array $terminalStatuses,
        CarbonInterface $now,
        CarbonInterface $dueSoonEnd,
    ): string {
        if (in_array($transaction->status, $terminalStatuses, true)) {
            return 'completed';
        }

        if ($transaction->due_at?->lt($now)) {
            return 'overdue';
        }

        if ($transaction->due_at?->betweenIncluded($now, $dueSoonEnd)) {
            return 'due_soon';
        }

        return 'on_track';
    }

    private function office(?Department $department): ?array
    {
        return $department ? [
            'code' => $department->code,
            'name' => $department->name,
            'shortName' => $department->short_name,
        ] : null;
    }
}
