<?php

namespace App\Services;

use App\Models\Department;
use App\Models\User;
use App\Models\WorkflowTransaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

final class WorkQueueQuery
{
    public function __construct(
        private readonly TransactionVisibilityQuery $visibility,
        private readonly WorkQueueExperienceResolver $experiences,
        private readonly WorkQueueItemPresenter $presenter,
        private readonly DashboardOfficeQuery $officeDashboard,
    ) {}

    /** @param array<string, mixed> $filters */
    public function workspace(User $actor, array $filters): array
    {
        $actor->loadMissing('employee.department');
        $experience = $this->experiences->resolve($actor);
        $view = (string) ($filters['view'] ?? 'all');
        abort_unless(in_array($view, $experience['allowedViews'], true), 403);

        $authorized = $this->visibility->scope($actor);
        $filtered = $this->applyCommonFilters(clone $authorized, $filters);
        $personal = $this->personalScope(clone $authorized, $actor);
        $optionsBase = $experience['profile'] === 'department_head' ? $authorized : $personal;

        return [
            'records' => $view === 'staff_workload'
                ? $this->emptyRecords()
                : $this->records($filtered, $actor, $view),
            'filters' => [
                'view' => $view,
                'search' => (string) ($filters['search'] ?? ''),
                'status' => (string) ($filters['status'] ?? ''),
                'priority' => (string) ($filters['priority'] ?? ''),
                'office_id' => isset($filters['office_id']) ? (int) $filters['office_id'] : null,
            ],
            'scopeGroups' => $this->scopeGroups($filtered, $actor, $experience['scopeGroups']),
            'filterOptions' => [
                'statuses' => $this->statusOptions($optionsBase),
                'priorities' => ['normal', 'high', 'urgent'],
                'offices' => $this->officeOptions($optionsBase, $actor),
            ],
            'experience' => [
                'profile' => $experience['profile'],
                'department' => $experience['department'],
                'hasOfficeScope' => $experience['profile'] === 'department_head',
            ],
            'staffWorkload' => $view === 'staff_workload'
                ? $this->officeDashboard->staffWorkloadFor($actor, clone $filtered)
                : [],
        ];
    }

    /** @param array<string, mixed> $filters */
    private function applyCommonFilters(Builder $query, array $filters): Builder
    {
        $search = Str::lower(trim((string) ($filters['search'] ?? '')));
        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function (Builder $match) use ($like): void {
                $match->whereRaw('LOWER(reference_no) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(title) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(transaction_type) LIKE ?', [$like])
                    ->orWhereRaw("LOWER(COALESCE(description, '')) LIKE ?", [$like])
                    ->orWhereHas('originDepartment', fn (Builder $office) => $office->whereRaw('LOWER(name) LIKE ?', [$like]))
                    ->orWhereHas('currentDepartment', fn (Builder $office) => $office->whereRaw('LOWER(name) LIKE ?', [$like]))
                    ->orWhereHas('assignedEmployee', fn (Builder $employee) => $employee->whereRaw('LOWER(full_name) LIKE ?', [$like]));
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', (string) $filters['status']);
        }

        if (! empty($filters['priority'])) {
            $query->where('priority', (string) $filters['priority']);
        }

        if (! empty($filters['office_id'])) {
            $query->where('current_department_id', (int) $filters['office_id']);
        }

        return $query;
    }

    private function personalScope(Builder $query, User $actor): Builder
    {
        $employeeId = $actor->employee?->id;

        return $query->where(function (Builder $personal) use ($actor, $employeeId): void {
            $personal->where('created_by_user_id', $actor->id)
                ->when($employeeId, fn (Builder $item) => $item->orWhere('assigned_employee_id', $employeeId));
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $groups
     * @return array<int, array<string, mixed>>
     */
    private function scopeGroups(Builder $filtered, User $actor, array $groups): array
    {
        return collect($groups)->map(function (array $group) use ($filtered, $actor): array {
            $group['views'] = collect($group['views'])->map(function (array $view) use ($filtered, $actor): array {
                $view['count'] = $this->applyView(clone $filtered, $actor, $view['key'])->count();

                return $view;
            })->all();

            return $group;
        })->all();
    }

    private function records(Builder $filtered, User $actor, string $view): LengthAwarePaginator
    {
        $query = $this->applyView(clone $filtered, $actor, $view)
            ->select([
                'id', 'reference_no', 'transaction_type', 'title', 'priority',
                'origin_department_id', 'current_department_id',
                'assigned_employee_id', 'status', 'received_at', 'due_at', 'completed_at',
                'updated_at',
            ])
            ->with([
                'originDepartment:id,code,name,short_name',
                'currentDepartment:id,code,name,short_name',
                'assignedEmployee:id,employee_number,full_name,department_id,position_title',
            ]);

        $this->orderForQueue($query, $view);

        return $query->paginate(25)
            ->withQueryString()
            ->through(fn (WorkflowTransaction $transaction): array => $this->presenter->present($transaction, $actor));
    }

    private function applyView(Builder $query, User $actor, string $view): Builder
    {
        $terminal = $this->terminalStatuses();
        $departmentId = $actor->employee?->department_id;
        $employeeId = $actor->employee?->id;

        if (in_array($view, [
            'all', 'needs_my_action', 'assigned_to_me', 'due_soon', 'overdue',
            'recently_updated', 'waiting_on_others', 'recently_completed',
        ], true)) {
            $query = $this->personalScope($query, $actor);
        }

        return match ($view) {
            'all' => $this->active($query, $terminal),
            'needs_my_action', 'assigned_to_me' => $this->active($query, $terminal)
                ->where('assigned_employee_id', $employeeId),
            'due_soon' => $this->active($query, $terminal)
                ->where('assigned_employee_id', $employeeId)
                ->whereBetween('due_at', [now(), now()->addDay()]),
            'overdue' => $this->active($query, $terminal)
                ->where('assigned_employee_id', $employeeId)
                ->whereNotNull('due_at')
                ->where('due_at', '<', now()),
            'recently_updated' => $this->active($query, $terminal)
                ->where('updated_at', '>=', now()->subDays(7)),
            'waiting_on_others' => $this->active($query, $terminal)
                ->where('created_by_user_id', $actor->id)
                ->where('origin_department_id', $departmentId)
                ->where('current_department_id', '!=', $departmentId),
            'recently_completed' => $this->recentlyCompleted($query, $terminal),
            'office_queue' => $this->active($query, $terminal),
            'staff_workload' => $this->active($query, $terminal)
                ->where('current_department_id', $departmentId),
            'unassigned' => $this->active($query, $terminal)
                ->where('current_department_id', $departmentId)
                ->whereNull('assigned_employee_id'),
            'escalations' => $this->active($query, $terminal)
                ->where('current_department_id', $departmentId)
                ->where(function (Builder $attention): void {
                    $attention->where('priority', 'urgent')
                        ->orWhere(fn (Builder $overdue) => $overdue
                            ->whereNotNull('due_at')
                            ->where('due_at', '<', now()));
                }),
            default => $query->whereRaw('1 = 0'),
        };
    }

    /** @param array<int, string> $terminal */
    private function active(Builder $query, array $terminal): Builder
    {
        return $query->whereNotIn('status', $terminal);
    }

    /** @param array<int, string> $terminal */
    private function recentlyCompleted(Builder $query, array $terminal): Builder
    {
        return $query->whereIn('status', $terminal)
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', now()->subDays(30));
    }

    private function orderForQueue(Builder $query, string $view): void
    {
        if ($view === 'recently_completed') {
            $query->orderByDesc('completed_at')->orderByDesc('id');

            return;
        }

        if ($view === 'recently_updated') {
            $query->orderByDesc('updated_at')->orderByDesc('id');

            return;
        }

        $query->orderByRaw('CASE WHEN due_at IS NOT NULL AND due_at < ? THEN 0 ELSE 1 END', [now()])
            ->orderByRaw("CASE WHEN priority = 'urgent' THEN 0 WHEN priority = 'high' THEN 1 ELSE 2 END")
            ->orderByRaw('CASE WHEN due_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('due_at')
            ->orderByDesc('updated_at');
    }

    private function emptyRecords(): LengthAwarePaginator
    {
        return new LengthAwarePaginator([], 0, 25, 1, [
            'path' => request()->url(),
            'query' => request()->query(),
        ]);
    }

    /** @return array<int, string> */
    private function statusOptions(Builder $base): array
    {
        return (clone $base)->whereNotNull('status')
            ->distinct()
            ->orderBy('status')
            ->pluck('status')
            ->values()
            ->all();
    }

    /** @return array<int, array{id:int,code:string,name:string,shortName:?string}> */
    private function officeOptions(Builder $base, User $actor): array
    {
        $ids = (clone $base)->distinct()->pluck('current_department_id')->filter()->all();
        if ($actor->employee?->department_id) {
            $ids[] = $actor->employee->department_id;
        }

        return Department::query()
            ->where('is_active', true)
            ->whereIn('id', array_values(array_unique($ids)))
            ->orderBy('branch')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'short_name'])
            ->map(fn (Department $department): array => [
                'id' => (int) $department->id,
                'code' => $department->code,
                'name' => $department->name,
                'shortName' => $department->short_name,
            ])
            ->all();
    }

    /** @return array<int, string> */
    private function terminalStatuses(): array
    {
        return array_values(config('workflow.default.terminal_statuses', [
            'approved', 'disapproved', 'closed',
        ]));
    }
}
