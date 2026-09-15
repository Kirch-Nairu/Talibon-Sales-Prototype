<?php

namespace App\Services;

use App\Models\CorrespondenceRecord;
use App\Models\Department;
use App\Models\TravelOrder;
use App\Models\User;
use App\Models\WorkflowTransaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class RecordsSearchQuery
{
    public function __construct(
        private readonly CorrespondenceAccessDecider $correspondenceAccess,
        private readonly TransactionVisibilityQuery $transactionVisibility,
        private readonly TravelOrderAccess $travelOrderAccess,
        private readonly RecordsResultPresenter $presenter,
    ) {
    }

    /** @param array<string, mixed> $filters */
    public function workspace(User $actor, array $filters): array
    {
        $actor->loadMissing('employee.department');
        $recordType = (string) ($filters['record_type'] ?? 'all');

        $correspondenceBase = $this->authorizedCorrespondence($actor);
        $transactionBase = $this->transactionVisibility->scope($actor);
        $travelOrderBase = $this->travelOrderAccess->scopeVisibleTo(TravelOrder::query(), $actor);

        $correspondence = clone $correspondenceBase;
        $transactions = clone $transactionBase;
        $travelOrders = clone $travelOrderBase;
        $this->applyCorrespondenceFilters($correspondence, $filters);
        $this->applyTransactionFilters($transactions, $filters);
        $this->applyTravelOrderFilters($travelOrders, $filters);

        return [
            'records' => $this->paginate($recordType, $correspondence, $transactions, $travelOrders),
            'filters' => [
                'search' => (string) ($filters['search'] ?? ''),
                'record_type' => $recordType,
                'state' => (string) ($filters['state'] ?? ''),
                'office_id' => isset($filters['office_id']) ? (int) $filters['office_id'] : null,
                'date_from' => (string) ($filters['date_from'] ?? ''),
                'date_to' => (string) ($filters['date_to'] ?? ''),
            ],
            'filterOptions' => [
                'recordTypes' => [
                    ['value' => 'all', 'label' => 'All Records'],
                    ['value' => 'correspondence', 'label' => 'Correspondence'],
                    ['value' => 'transaction', 'label' => 'Inter-Office Transactions'],
                    ['value' => 'travel_order', 'label' => 'Approved Travel Orders'],
                ],
                'states' => $this->stateOptions(
                    $recordType,
                    $correspondenceBase,
                    $transactionBase,
                    $travelOrderBase,
                ),
                'offices' => $this->officeOptions(
                    $recordType,
                    $correspondenceBase,
                    $transactionBase,
                    $travelOrderBase,
                ),
            ],
        ];
    }

    private function authorizedCorrespondence(User $actor): Builder
    {
        $query = CorrespondenceRecord::query();
        $this->correspondenceAccess->scopeVisibleTo($query, $actor);

        return $query;
    }

    /** @param array<string, mixed> $filters */
    private function applyCorrespondenceFilters(Builder $query, array $filters): void
    {
        $search = Str::lower(trim((string) ($filters['search'] ?? '')));
        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function (Builder $match) use ($like): void {
                foreach ([
                    'municipal_reference_no',
                    'external_reference_no',
                    'subject',
                    'summary',
                    'sender_name',
                    'sender_organization',
                    'source',
                ] as $column) {
                    $match->orWhereRaw("LOWER(COALESCE({$column}, '')) LIKE ?", [$like]);
                }

                $match->orWhere(function (Builder $office) use ($like): void {
                    $office->whereHas(
                        'workflowTransaction.currentDepartment',
                        fn (Builder $department) => $this->matchOffice($department, $like),
                    )->orWhere(function (Builder $unlinked) use ($like): void {
                        $unlinked->whereNull('workflow_transaction_id')
                            ->whereHas(
                                'receivingDepartment',
                                fn (Builder $department) => $this->matchOffice($department, $like),
                            );
                    });
                })->orWhereHas(
                    'workflowTransaction.assignedEmployee',
                    fn (Builder $employee) => $employee->whereRaw("LOWER(COALESCE(full_name, '')) LIKE ?", [$like]),
                );
            });
        }

        if (! empty($filters['state'])) {
            $query->where('lifecycle_state', (string) $filters['state']);
        }

        if (! empty($filters['office_id'])) {
            $this->applyCorrespondenceOffice($query, (int) $filters['office_id']);
        }

        $this->applyDateBounds($query, 'received_at', $filters);
    }

    /** @param array<string, mixed> $filters */
    private function applyTransactionFilters(Builder $query, array $filters): void
    {
        $search = Str::lower(trim((string) ($filters['search'] ?? '')));
        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function (Builder $match) use ($like): void {
                foreach (['reference_no', 'title', 'description', 'transaction_type'] as $column) {
                    $match->orWhereRaw("LOWER(COALESCE({$column}, '')) LIKE ?", [$like]);
                }

                $match->orWhereHas('originDepartment', fn (Builder $department) => $this->matchOffice($department, $like))
                    ->orWhereHas('currentDepartment', fn (Builder $department) => $this->matchOffice($department, $like))
                    ->orWhereHas(
                        'assignedEmployee',
                        fn (Builder $employee) => $employee->whereRaw("LOWER(COALESCE(full_name, '')) LIKE ?", [$like]),
                    );
            });
        }

        if (! empty($filters['state'])) {
            $query->where('status', (string) $filters['state']);
        }

        if (! empty($filters['office_id'])) {
            $query->where('current_department_id', (int) $filters['office_id']);
        }

        $this->applyTransactionDateBounds($query, $filters);
    }

    /** @param array<string, mixed> $filters */
    private function applyTravelOrderFilters(Builder $query, array $filters): void
    {
        $search = Str::lower(trim((string) ($filters['search'] ?? '')));
        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function (Builder $match) use ($like): void {
                foreach (['reference_number', 'purpose', 'destination'] as $column) {
                    $match->orWhereRaw("LOWER(COALESCE({$column}, '')) LIKE ?", [$like]);
                }

                $match->orWhereHas('department', fn (Builder $department) => $this->matchOffice($department, $like))
                    ->orWhereHas('issuedTo', function (Builder $employee) use ($like): void {
                        $employee->whereRaw("LOWER(COALESCE(full_name, '')) LIKE ?", [$like])
                            ->orWhereRaw("LOWER(COALESCE(employee_number, '')) LIKE ?", [$like]);
                    });
            });
        }

        if (! empty($filters['state'])) {
            $query->where('status', (string) $filters['state']);
        }

        if (! empty($filters['office_id'])) {
            $query->where('department_id', (int) $filters['office_id']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('travel_end_date', '>=', (string) $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('travel_start_date', '<=', (string) $filters['date_to']);
        }
    }

    private function matchOffice(Builder $query, string $like): Builder
    {
        return $query->where(function (Builder $office) use ($like): void {
            $office->whereRaw("LOWER(COALESCE(name, '')) LIKE ?", [$like])
                ->orWhereRaw("LOWER(COALESCE(code, '')) LIKE ?", [$like])
                ->orWhereRaw("LOWER(COALESCE(short_name, '')) LIKE ?", [$like]);
        });
    }

    private function applyCorrespondenceOffice(Builder $query, int $officeId): void
    {
        $query->where(function (Builder $office) use ($officeId): void {
            $office->whereHas(
                'workflowTransaction',
                fn (Builder $workflow) => $workflow->where('current_department_id', $officeId),
            )->orWhere(function (Builder $unlinked) use ($officeId): void {
                $unlinked->whereNull('workflow_transaction_id')
                    ->where('receiving_department_id', $officeId);
            });
        });
    }

    /** @param array<string, mixed> $filters */
    private function applyDateBounds(Builder $query, string $column, array $filters): void
    {
        if (! empty($filters['date_from'])) {
            $query->whereDate($column, '>=', (string) $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate($column, '<=', (string) $filters['date_to']);
        }
    }

    /** @param array<string, mixed> $filters */
    private function applyTransactionDateBounds(Builder $query, array $filters): void
    {
        foreach (['date_from' => '>=', 'date_to' => '<='] as $key => $operator) {
            if (empty($filters[$key])) {
                continue;
            }

            $date = (string) $filters[$key];
            $query->where(function (Builder $bounded) use ($operator, $date): void {
                $bounded->whereDate('received_at', $operator, $date)
                    ->orWhere(function (Builder $legacy) use ($operator, $date): void {
                        $legacy->whereNull('received_at')
                            ->whereDate('created_at', $operator, $date);
                    });
            });
        }
    }

    private function paginate(
        string $recordType,
        Builder $correspondence,
        Builder $transactions,
        Builder $travelOrders,
    ): LengthAwarePaginator {
        $sources = [];

        if ($this->includesSource($recordType, 'correspondence')) {
            $sources[] = $correspondence->selectRaw(
                "'correspondence' AS record_type, CAST(public_id AS TEXT) AS record_key, received_at AS record_date, id AS sort_id",
            );
        }

        if ($this->includesSource($recordType, 'transaction')) {
            $sources[] = $transactions->selectRaw(
                "'transaction' AS record_type, CAST(id AS TEXT) AS record_key, COALESCE(received_at, created_at) AS record_date, id AS sort_id",
            );
        }

        if ($this->includesSource($recordType, 'travel_order')) {
            $sources[] = $travelOrders->selectRaw(
                "'travel_order' AS record_type, CAST(public_id AS TEXT) AS record_key, issuance_date AS record_date, id AS sort_id",
            );
        }

        $union = array_shift($sources);
        foreach ($sources as $source) {
            $union->unionAll($source);
        }

        $paginator = DB::query()
            ->fromSub($union->toBase(), 'records_registry')
            ->orderByDesc('record_date')
            ->orderByDesc('sort_id')
            ->paginate(25)
            ->withQueryString();

        return $this->presenter->hydratePage($paginator);
    }

    private function stateOptions(
        string $recordType,
        Builder $correspondence,
        Builder $transactions,
        Builder $travelOrders,
    ): array {
        $values = collect();

        if ($this->includesSource($recordType, 'correspondence')) {
            $values = $values->merge($this->stateValues($correspondence, 'lifecycle_state'));
        }

        if ($this->includesSource($recordType, 'transaction')) {
            $values = $values->merge($this->stateValues($transactions, 'status'));
        }

        if ($this->includesSource($recordType, 'travel_order')) {
            $values = $values->merge($this->stateValues($travelOrders, 'status'));
        }

        return $values
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->map(fn (string $value): array => [
                'value' => $value,
                'label' => Str::headline($value),
            ])
            ->all();
    }

    private function officeOptions(
        string $recordType,
        Builder $correspondence,
        Builder $transactions,
        Builder $travelOrders,
    ): array {
        $ids = collect();

        if ($this->includesSource($recordType, 'correspondence')) {
            $workflowIds = (clone $correspondence)
                ->whereNotNull('workflow_transaction_id')
                ->select('workflow_transaction_id');

            $ids = $ids
                ->merge(
                    WorkflowTransaction::query()
                        ->whereIn('id', $workflowIds)
                        ->distinct()
                        ->pluck('current_department_id'),
                )
                ->merge(
                    (clone $correspondence)
                        ->whereNull('workflow_transaction_id')
                        ->whereNotNull('receiving_department_id')
                        ->distinct()
                        ->pluck('receiving_department_id'),
                );
        }

        if ($this->includesSource($recordType, 'transaction')) {
            $ids = $ids->merge((clone $transactions)->distinct()->pluck('current_department_id'));
        }

        if ($this->includesSource($recordType, 'travel_order')) {
            $ids = $ids->merge((clone $travelOrders)->distinct()->pluck('department_id'));
        }

        return Department::query()
            ->where('is_active', true)
            ->whereIn('id', $ids->filter()->unique()->values()->all())
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

    private function stateValues(Builder $query, string $column)
    {
        return (clone $query)
            ->distinct()
            ->pluck($column)
            ->map(fn ($value): string => $value instanceof \BackedEnum
                ? (string) $value->value
                : (string) $value);
    }

    private function includesSource(string $recordType, string $source): bool
    {
        return $recordType === 'all' || $recordType === $source;
    }
}
