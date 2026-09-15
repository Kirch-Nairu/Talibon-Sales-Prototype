<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\User;
use App\Models\WorkflowTransaction;
use Illuminate\Database\Eloquent\Builder;

final class DashboardOfficeQuery
{
    public function __construct(
        private readonly TransactionVisibilityQuery $visibility,
        private readonly DashboardWorkPresenter $presenter,
    ) {}

    /** @return array<string, mixed> */
    public function workspace(User $actor): array
    {
        $actor->loadMissing('employee.department');
        $departmentId = (int) $actor->employee->department_id;
        $terminal = $this->terminalStatuses();
        $now = now();
        $dueSoonEnd = $now->copy()->addDay();
        $authorized = $this->visibility->scope($actor);
        $summary = $this->summary($authorized, $departmentId, $terminal, $now);

        return [
            'metrics' => [
                'active' => $this->metric('Active Office Work', $summary->active, '/transactions?view=office_queue'),
                'incoming' => $this->metric('Incoming', $summary->incoming, '/transactions?view=office_queue'),
                'outgoing' => $this->metric('Outgoing', $summary->outgoing, '/transactions?view=office_queue'),
                'inProgress' => $this->metric('In Progress', $summary->in_progress, '/transactions?view=office_queue'),
                'waitingExternally' => $this->metric('Waiting Externally', $summary->waiting_externally, '/transactions?view=office_queue'),
                'overdue' => $this->metric('Office Overdue', $summary->overdue, '/transactions?view=escalations'),
                'unassigned' => $this->metric('Unassigned', $summary->unassigned, '/transactions?view=unassigned'),
                'recentlyCompleted' => $this->metric('Recently Completed', $summary->recently_completed, '/reports?report=completed-work&office_id='.$departmentId),
                'escalations' => $this->metric('Escalations', $summary->escalations, '/transactions?view=escalations'),
            ],
            'statusOverview' => $this->statusOverview($authorized, $departmentId, $terminal),
            'staffWorkload' => $this->staffWorkloadFor($actor, clone $authorized),
            'oldestUnresolved' => $this->oldestUnresolved(
                $authorized,
                $departmentId,
                $terminal,
                $now,
                $dueSoonEnd,
            ),
        ];
    }

    /** @param array<int, string> $terminal */
    private function summary(Builder $authorized, int $departmentId, array $terminal, $now): object
    {
        $placeholders = implode(', ', array_fill(0, count($terminal), '?'));
        $active = "status NOT IN ({$placeholders})";
        $complete = "status IN ({$placeholders})";

        return (clone $authorized)
            ->selectRaw("SUM(CASE WHEN {$active} AND current_department_id = ? THEN 1 ELSE 0 END) AS active", [...$terminal, $departmentId])
            ->selectRaw("SUM(CASE WHEN {$active} AND current_department_id = ? AND origin_department_id <> ? THEN 1 ELSE 0 END) AS incoming", [...$terminal, $departmentId, $departmentId])
            ->selectRaw("SUM(CASE WHEN {$active} AND origin_department_id = ? AND current_department_id <> ? THEN 1 ELSE 0 END) AS outgoing", [...$terminal, $departmentId, $departmentId])
            ->selectRaw("SUM(CASE WHEN {$active} AND current_department_id = ? AND assigned_employee_id IS NOT NULL THEN 1 ELSE 0 END) AS in_progress", [...$terminal, $departmentId])
            ->selectRaw("SUM(CASE WHEN {$active} AND origin_department_id = ? AND current_department_id <> ? THEN 1 ELSE 0 END) AS waiting_externally", [...$terminal, $departmentId, $departmentId])
            ->selectRaw("SUM(CASE WHEN {$active} AND current_department_id = ? AND due_at IS NOT NULL AND due_at < ? THEN 1 ELSE 0 END) AS overdue", [...$terminal, $departmentId, $now])
            ->selectRaw("SUM(CASE WHEN {$active} AND current_department_id = ? AND assigned_employee_id IS NULL THEN 1 ELSE 0 END) AS unassigned", [...$terminal, $departmentId])
            ->selectRaw("SUM(CASE WHEN {$complete} AND completed_at IS NOT NULL AND completed_at >= ? AND (current_department_id = ? OR origin_department_id = ?) THEN 1 ELSE 0 END) AS recently_completed", [...$terminal, $now->copy()->subDays(30), $departmentId, $departmentId])
            ->selectRaw("SUM(CASE WHEN {$active} AND current_department_id = ? AND ((due_at IS NOT NULL AND due_at < ?) OR priority = 'urgent') THEN 1 ELSE 0 END) AS escalations", [...$terminal, $departmentId, $now])
            ->firstOrFail();
    }

    /** @param array<int, string> $terminal */
    private function statusOverview(Builder $authorized, int $departmentId, array $terminal): array
    {
        return (clone $authorized)
            ->where('current_department_id', $departmentId)
            ->whereNotIn('status', $terminal)
            ->select('status')
            ->selectRaw('COUNT(*) AS aggregate')
            ->groupBy('status')
            ->orderByDesc('aggregate')
            ->limit(8)
            ->get()
            ->map(fn ($row): array => [
                'status' => $row->status,
                'count' => (int) $row->aggregate,
            ])
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    public function staffWorkloadFor(User $actor, ?Builder $authorized = null): array
    {
        $actor->loadMissing('employee.department');
        $departmentId = (int) $actor->employee->department_id;
        $terminal = $this->terminalStatuses();
        $now = now();
        $authorized ??= $this->visibility->scope($actor);

        $rows = (clone $authorized)
            ->where('current_department_id', $departmentId)
            ->whereNotIn('status', $terminal)
            ->whereNotNull('assigned_employee_id')
            ->select('assigned_employee_id')
            ->selectRaw('COUNT(*) AS active')
            ->selectRaw('SUM(CASE WHEN due_at IS NOT NULL AND due_at < ? THEN 1 ELSE 0 END) AS overdue', [$now])
            ->selectRaw('COUNT(*) AS requires_action')
            ->groupBy('assigned_employee_id')
            ->orderByDesc('overdue')
            ->orderByDesc('active')
            ->limit(10)
            ->get();

        $employees = Employee::query()
            ->whereIn('id', $rows->pluck('assigned_employee_id')->all())
            ->get(['id', 'full_name', 'position_title'])
            ->keyBy('id');

        return $rows->map(function ($row) use ($employees): ?array {
            $employee = $employees->get((int) $row->assigned_employee_id);

            return $employee ? [
                'employee' => $employee->full_name,
                'position' => $employee->position_title,
                'active' => (int) $row->active,
                'overdue' => (int) $row->overdue,
                'requiresAction' => (int) $row->requires_action,
            ] : null;
        })->filter()->values()->all();
    }

    /** @param array<int, string> $terminal */
    private function oldestUnresolved(
        Builder $authorized,
        int $departmentId,
        array $terminal,
        $now,
        $dueSoonEnd,
    ): array {
        return (clone $authorized)
            ->where('current_department_id', $departmentId)
            ->whereNotIn('status', $terminal)
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
            ->orderByRaw('CASE WHEN received_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('received_at')
            ->orderBy('id')
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
        return ['label' => $label, 'value' => (int) $value, 'link' => $link];
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
