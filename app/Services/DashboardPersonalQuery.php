<?php

namespace App\Services;

use App\Models\User;
use App\Models\WorkflowTransaction;
use Illuminate\Database\Eloquent\Builder;

final class DashboardPersonalQuery
{
    public function __construct(
        private readonly TransactionVisibilityQuery $visibility,
        private readonly DashboardWorkPresenter $presenter,
    ) {}

    /** @return array<string, mixed> */
    public function workspace(User $actor): array
    {
        $actor->loadMissing('employee.department');
        $employeeId = (int) $actor->employee->id;
        $departmentId = (int) $actor->employee->department_id;
        $now = now();
        $dueSoonEnd = $now->copy()->addDay();
        $terminal = $this->terminalStatuses();
        $personal = $this->personalScope($this->visibility->scope($actor), $actor);
        $summary = $this->summary(
            $personal,
            $actor->id,
            $employeeId,
            $departmentId,
            $terminal,
            $now,
            $dueSoonEnd,
        );

        return [
            'metrics' => [
                'needsAction' => $this->metric('Needs Action', $summary->needs_action, '/transactions?view=needs_my_action'),
                'assignedToMe' => $this->metric('Assigned to Me', $summary->assigned_to_me, '/transactions?view=assigned_to_me'),
                'dueSoon' => $this->metric('Due Soon', $summary->due_soon, '/transactions?view=due_soon'),
                'overdue' => $this->metric('Overdue', $summary->overdue, '/transactions?view=overdue'),
                'recentlyUpdated' => $this->metric('Recently Updated', $summary->recently_updated, '/transactions?view=recently_updated'),
                'waitingOnAnotherOffice' => $this->metric('Waiting on Another Office', $summary->waiting_on_another_office, '/transactions?view=waiting_on_others'),
                'completedRecently' => $this->metric('Completed Recently', $summary->completed_recently, '/transactions?view=recently_completed'),
            ],
            'recentWork' => $this->recentWork($personal, $terminal, $now, $dueSoonEnd),
        ];
    }

    private function personalScope(Builder $authorized, User $actor): Builder
    {
        $employeeId = $actor->employee?->id;

        return $authorized->where(function (Builder $personal) use ($actor, $employeeId): void {
            $personal->where('created_by_user_id', $actor->id)
                ->when($employeeId, fn (Builder $query) => $query->orWhere('assigned_employee_id', $employeeId));
        });
    }

    /** @param array<int, string> $terminal */
    private function summary(
        Builder $personal,
        int $userId,
        int $employeeId,
        int $departmentId,
        array $terminal,
        $now,
        $dueSoonEnd,
    ): object {
        $placeholders = implode(', ', array_fill(0, count($terminal), '?'));
        $active = "status NOT IN ({$placeholders})";
        $complete = "status IN ({$placeholders})";

        return (clone $personal)
            ->selectRaw("SUM(CASE WHEN {$active} AND assigned_employee_id = ? THEN 1 ELSE 0 END) AS needs_action", [...$terminal, $employeeId])
            ->selectRaw("SUM(CASE WHEN {$active} AND assigned_employee_id = ? THEN 1 ELSE 0 END) AS assigned_to_me", [...$terminal, $employeeId])
            ->selectRaw("SUM(CASE WHEN {$active} AND assigned_employee_id = ? AND due_at BETWEEN ? AND ? THEN 1 ELSE 0 END) AS due_soon", [...$terminal, $employeeId, $now, $dueSoonEnd])
            ->selectRaw("SUM(CASE WHEN {$active} AND assigned_employee_id = ? AND due_at IS NOT NULL AND due_at < ? THEN 1 ELSE 0 END) AS overdue", [...$terminal, $employeeId, $now])
            ->selectRaw("SUM(CASE WHEN {$active} AND updated_at >= ? THEN 1 ELSE 0 END) AS recently_updated", [...$terminal, $now->copy()->subDays(7)])
            ->selectRaw("SUM(CASE WHEN {$active} AND created_by_user_id = ? AND origin_department_id = ? AND current_department_id <> ? THEN 1 ELSE 0 END) AS waiting_on_another_office", [...$terminal, $userId, $departmentId, $departmentId])
            ->selectRaw("SUM(CASE WHEN {$complete} AND completed_at IS NOT NULL AND completed_at >= ? THEN 1 ELSE 0 END) AS completed_recently", [...$terminal, $now->copy()->subDays(30)])
            ->firstOrFail();
    }

    /** @param array<int, string> $terminal */
    private function recentWork(Builder $personal, array $terminal, $now, $dueSoonEnd): array
    {
        return (clone $personal)
            ->select([
                'id', 'reference_no', 'transaction_type', 'title', 'priority', 'status',
                'origin_department_id', 'current_department_id', 'assigned_employee_id',
                'received_at', 'due_at', 'updated_at',
            ])
            ->with([
                'originDepartment:id,code,name,short_name',
                'currentDepartment:id,code,name,short_name',
                'assignedEmployee:id,full_name,position_title',
            ])
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->limit(5)
            ->get()
            ->map(fn (WorkflowTransaction $transaction): array => $this->presenter->present(
                $transaction,
                $terminal,
                $now,
                $dueSoonEnd,
            ))
            ->all();
    }

    private function metric(string $label, mixed $value, string $link): array
    {
        return [
            'label' => $label,
            'value' => (int) $value,
            'link' => $link,
        ];
    }

    /** @return array<int, string> */
    private function terminalStatuses(): array
    {
        return array_values(config('workflow.default.terminal_statuses', [
            'approved',
            'disapproved',
            'closed',
        ]));
    }
}
