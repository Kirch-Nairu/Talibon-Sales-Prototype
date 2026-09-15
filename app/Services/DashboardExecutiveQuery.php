<?php

namespace App\Services;

use App\Models\Department;
use App\Models\User;
use App\Models\WorkflowTransaction;
use Illuminate\Database\Eloquent\Builder;

final class DashboardExecutiveQuery
{
    public function __construct(
        private readonly TransactionVisibilityQuery $visibility,
        private readonly DashboardTransactionQuery $transactions,
        private readonly DashboardWorkPresenter $presenter,
    ) {}

    /** @return array<string, mixed> */
    public function workspace(User $actor): array
    {
        abort_unless($this->visibility->canViewAll($actor), 403);

        $transaction = $this->transactions->workspace($actor);
        $summary = $transaction['municipalOverview'] ?? [];
        $workload = $transaction['departmentWorkload'] ?? [];
        $terminal = $this->terminalStatuses();
        $now = now();
        $dueSoonEnd = $now->copy()->addDay();
        $authorized = $this->visibility->scope($actor);
        $mayorDepartmentId = Department::query()
            ->where('is_active', true)
            ->where('code', 'MAYOR')
            ->value('id');
        $pendingExecutiveAction = $mayorDepartmentId
            ? (clone $authorized)
                ->where('current_department_id', $mayorDepartmentId)
                ->where('status', 'for_approval')
                ->count()
            : 0;

        return [
            'metrics' => [
                'pendingExecutiveAction' => $this->metric('Pending Mayor Action', $pendingExecutiveAction, '/transactions?status=for_approval'),
                'activeMunicipalWork' => $this->metric('Active Municipal Work', $summary['activeMunicipalWork'] ?? 0, '/transactions'),
                'municipalOverdue' => $this->metric('Municipal Overdue', $summary['municipalOverdue'] ?? 0, '/transactions?view=overdue'),
                'dueSoon' => $this->metric('Due Soon', $summary['dueSoon'] ?? 0, '/transactions?view=due_soon'),
                'bottleneckOffices' => $this->metric('Offices With Overdue Work', collect($workload)->where('overdue', '>', 0)->count(), '/reports'),
                'recentlyCompleted' => $this->metric('Recently Completed', $summary['completedThisMonth'] ?? 0, '/transactions?view=recently_completed'),
            ],
            'summary' => $summary,
            'departmentWorkload' => $workload,
            'oldestUnresolved' => $this->workList(
                $authorized,
                $terminal,
                $now,
                $dueSoonEnd,
                oldest: true,
            ),
            'recentlyCompleted' => $this->workList(
                $authorized,
                $terminal,
                $now,
                $dueSoonEnd,
                oldest: false,
            ),
        ];
    }

    /** @param array<int, string> $terminal */
    private function workList(
        Builder $authorized,
        array $terminal,
        $now,
        $dueSoonEnd,
        bool $oldest,
    ): array {
        $query = (clone $authorized)
            ->select([
                'id', 'reference_no', 'transaction_type', 'title', 'priority', 'status',
                'origin_department_id', 'current_department_id', 'assigned_employee_id',
                'received_at', 'due_at', 'completed_at', 'updated_at',
            ])
            ->with([
                'originDepartment:id,code,name,short_name',
                'currentDepartment:id,code,name,short_name',
                'assignedEmployee:id,full_name,position_title',
            ]);

        if ($oldest) {
            $query->whereNotIn('status', $terminal)
                ->orderByRaw('CASE WHEN received_at IS NULL THEN 1 ELSE 0 END')
                ->orderBy('received_at')
                ->orderBy('id');
        } else {
            $query->whereIn('status', $terminal)
                ->whereNotNull('completed_at')
                ->where('completed_at', '>=', $now->copy()->subDays(30))
                ->orderByDesc('completed_at')
                ->orderByDesc('id');
        }

        return $query->limit(5)
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
