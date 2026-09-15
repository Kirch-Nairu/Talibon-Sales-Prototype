<?php

namespace App\Services\Reports;

use App\Models\Department;
use App\Models\User;
use App\Models\WorkflowTransaction;
use App\Services\TransactionVisibilityQuery;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class TransactionOperationalReportQuery
{
    public function __construct(
        private readonly TransactionVisibilityQuery $visibility,
        private readonly ReportValuePresenter $presenter,
    ) {}

    /** @return Collection<int, array<string, mixed>> */
    public function workload(User $actor, array $filters): Collection
    {
        $query = $this->filteredBase($actor, $filters, false)
            ->join('departments', 'departments.id', '=', 'transactions.current_department_id')
            ->select(['departments.id', 'departments.code', 'departments.name', 'departments.short_name'])
            ->selectRaw('SUM(CASE WHEN status NOT IN (?, ?, ?) THEN 1 ELSE 0 END) AS active', $this->terminal())
            ->selectRaw('SUM(CASE WHEN status NOT IN (?, ?, ?) AND due_at IS NOT NULL AND due_at < ? THEN 1 ELSE 0 END) AS overdue', [...$this->terminal(), now()])
            ->selectRaw('SUM(CASE WHEN status NOT IN (?, ?, ?) AND assigned_employee_id IS NOT NULL THEN 1 ELSE 0 END) AS assigned', $this->terminal())
            ->selectRaw('SUM(CASE WHEN status NOT IN (?, ?, ?) AND assigned_employee_id IS NULL THEN 1 ELSE 0 END) AS unassigned', $this->terminal())
            ->selectRaw($this->completedExpression($filters), $this->completedBindings($filters))
            ->groupBy(['departments.id', 'departments.code', 'departments.name', 'departments.short_name'])
            ->orderByDesc('overdue')
            ->orderByDesc('active')
            ->orderBy('departments.name');

        return $query->get()->map(fn ($row): array => [
            'officeId' => (int) $row->id,
            'office' => $row->short_name ?: $row->name,
            'officeCode' => $row->code,
            'active' => (int) $row->active,
            'overdue' => (int) $row->overdue,
            'completed' => (int) $row->completed,
            'assigned' => (int) $row->assigned,
            'unassigned' => (int) $row->unassigned,
        ]);
    }

    public function rows(string $report, User $actor, array $filters, bool $paginate = true): mixed
    {
        $query = $this->rowQuery($report, $actor, $filters);
        $mapper = fn (WorkflowTransaction $transaction): array => $this->serialize($report, $transaction);

        if ($paginate) {
            return $query->paginate(25)->withQueryString()->through($mapper);
        }

        return $query->lazy(500)->map($mapper);
    }

    /** @return array<int, array{id:int,label:string}> */
    public function officeOptions(User $actor): array
    {
        $base = $this->visibility->scope($actor);
        $ids = (clone $base)->select('current_department_id')
            ->union((clone $base)->select('origin_department_id'));

        return Department::query()->where('is_active', true)->whereIn('id', $ids)
            ->orderBy('name')->get(['id', 'code', 'name', 'short_name'])
            ->map(fn (Department $office): array => [
                'id' => (int) $office->id,
                'label' => ($office->short_name ?: $office->name).' ('.$office->code.')',
            ])->all();
    }

    /** @return array<string, array<int, string>> */
    public function valueOptions(User $actor): array
    {
        $base = $this->visibility->scope($actor);

        return [
            'statuses' => (clone $base)->distinct()->orderBy('status')->pluck('status')->all(),
            'priorities' => (clone $base)->distinct()->orderBy('priority')->pluck('priority')->all(),
            'transactionTypes' => (clone $base)->distinct()->orderBy('transaction_type')->pluck('transaction_type')->all(),
        ];
    }

    private function rowQuery(string $report, User $actor, array $filters): Builder
    {
        $query = $this->filteredBase($actor, $filters)
            ->with([
                'originDepartment:id,name,short_name',
                'currentDepartment:id,name,short_name',
                'assignedEmployee:id,full_name',
            ]);

        return match ($report) {
            'transaction-aging' => $query->whereNotIn('status', $this->terminal())
                ->orderByRaw('due_at IS NULL')->orderBy('due_at')->orderBy('id'),
            'completed-work' => $query->whereIn('status', $this->terminal())
                ->whereNotNull('completed_at')->orderByDesc('completed_at')->orderByDesc('id'),
            'overdue-action-required' => $query->whereNotIn('status', $this->terminal())
                ->whereNotNull('due_at')->where('due_at', '<', now())
                ->orderBy('due_at')->orderBy('id'),
        };
    }

    private function filteredBase(User $actor, array $filters, bool $applyDates = true): Builder
    {
        $query = $this->visibility->scope($actor);
        foreach (['status', 'priority', 'transaction_type'] as $filter) {
            if (isset($filters[$filter])) {
                $query->where($filter, $filters[$filter]);
            }
        }
        if (isset($filters['office'])) {
            $query->where('current_department_id', $filters['office']);
        }
        if ($applyDates) {
            $column = isset($filters['_date_column']) ? $filters['_date_column'] : 'received_at';
            $this->applyDates($query, $filters, $column);
        }

        return $query;
    }

    private function applyDates(Builder $query, array $filters, string $column): void
    {
        $qualified = $column === 'received_at' ? 'COALESCE(received_at, created_at)' : $column;
        if (isset($filters['date_from'])) {
            $query->whereRaw("{$qualified} >= ?", [$this->boundary($filters['date_from'], false)]);
        }
        if (isset($filters['date_to'])) {
            $query->whereRaw("{$qualified} <= ?", [$this->boundary($filters['date_to'], true)]);
        }
    }

    /** @return array<string, mixed> */
    private function serialize(string $report, WorkflowTransaction $transaction): array
    {
        $received = $transaction->received_at ?? $transaction->created_at;
        $now = now();
        $base = [
            'id' => $transaction->id,
            'reference' => $transaction->reference_no,
            'title' => $transaction->title,
            'originOffice' => $transaction->originDepartment?->short_name ?? $transaction->originDepartment?->name,
            'status' => $this->presenter->label($transaction->status),
            'detailUrl' => route('transactions.show', $transaction, false),
        ];

        if ($report === 'completed-work') {
            return $base + [
                'finalOffice' => $transaction->currentDepartment?->short_name ?? $transaction->currentDepartment?->name,
                'completedAt' => $this->presenter->timestamp($transaction->completed_at),
                'processingDuration' => $this->presenter->duration($received, $transaction->completed_at),
                'finalStatus' => $this->presenter->label($transaction->status),
            ];
        }

        if ($report === 'overdue-action-required') {
            return $base + [
                'currentOffice' => $transaction->currentDepartment?->short_name ?? $transaction->currentDepartment?->name,
                'assignee' => $transaction->assignedEmployee?->full_name ?? 'Unassigned',
                'dueAt' => $this->presenter->timestamp($transaction->due_at),
                'overdueBy' => $this->presenter->duration($transaction->due_at, $now),
                'priority' => $this->presenter->label($transaction->priority),
            ];
        }

        $overdue = $transaction->due_at?->lt($now) ?? false;

        return $base + [
            'type' => $this->presenter->label($transaction->transaction_type),
            'currentOffice' => $transaction->currentDepartment?->short_name ?? $transaction->currentDepartment?->name,
            'assignee' => $transaction->assignedEmployee?->full_name ?? 'Unassigned',
            'priority' => $this->presenter->label($transaction->priority),
            'receivedAt' => $this->presenter->timestamp($received),
            'dueAt' => $this->presenter->timestamp($transaction->due_at),
            'age' => $this->presenter->duration($received, $now),
            'dueState' => $overdue ? 'Overdue' : ($transaction->due_at ? 'On track' : 'No due date'),
            'overdueBy' => $overdue ? $this->presenter->duration($transaction->due_at, $now) : null,
        ];
    }

    private function completedExpression(array $filters): string
    {
        $conditions = 'status IN (?, ?, ?) AND completed_at IS NOT NULL';
        if (isset($filters['date_from'])) {
            $conditions .= ' AND completed_at >= ?';
        }
        if (isset($filters['date_to'])) {
            $conditions .= ' AND completed_at <= ?';
        }

        return "SUM(CASE WHEN {$conditions} THEN 1 ELSE 0 END) AS completed";
    }

    private function completedBindings(array $filters): array
    {
        $bindings = $this->terminal();
        if (isset($filters['date_from'])) {
            $bindings[] = $this->boundary($filters['date_from'], false);
        }
        if (isset($filters['date_to'])) {
            $bindings[] = $this->boundary($filters['date_to'], true);
        }

        return $bindings;
    }

    private function boundary(string $date, bool $end): CarbonImmutable
    {
        $value = CarbonImmutable::parse($date, config('app.timezone'));

        return $end ? $value->endOfDay() : $value->startOfDay();
    }

    /** @return array<int, string> */
    private function terminal(): array
    {
        return array_values(config('workflow.default.terminal_statuses'));
    }
}
