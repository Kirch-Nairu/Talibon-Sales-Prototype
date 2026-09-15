<?php

namespace App\Services\Reports;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

final class CorePortalReportService
{
    public function __construct(
        private readonly CorePortalReportCatalog $catalog,
        private readonly TransactionOperationalReportQuery $transactions,
        private readonly CorrespondenceStatusReportQuery $correspondence,
        private readonly DocumentMovementReportQuery $movements,
    ) {}

    public function page(string $report, User $actor, array $filters): array
    {
        $options = $this->options($report, $actor);
        $this->authorizeOfficeFilter($filters, $options['offices']);

        return [
            'catalog' => $this->catalog->forClient(),
            'activeReport' => $report,
            'filters' => $filters,
            'filterOptions' => $options,
            'result' => $this->normalize($this->result($report, $actor, $filters, true)),
        ];
    }

    public function exportRows(string $report, User $actor, array $filters): iterable
    {
        $this->authorizeOfficeFilter($filters, $this->options($report, $actor)['offices']);

        return $this->result($report, $actor, $filters, false);
    }

    public function columns(string $report): array
    {
        return $this->catalog->get($report)['columns'];
    }

    private function result(string $report, User $actor, array $filters, bool $paginate): mixed
    {
        if ($report === 'office-workload') {
            return $this->transactions->workload($actor, $filters);
        }
        if (in_array($report, ['transaction-aging', 'completed-work', 'overdue-action-required'], true)) {
            $dateColumn = match ($report) {
                'completed-work' => 'completed_at',
                'overdue-action-required' => 'due_at',
                default => 'received_at',
            };

            return $this->transactions->rows($report, $actor, [...$filters, '_date_column' => $dateColumn], $paginate);
        }

        return $report === 'correspondence-status'
            ? $this->correspondence->rows($actor, $filters, $paginate)
            : $this->movements->rows($actor, $filters, $paginate);
    }

    private function options(string $report, User $actor): array
    {
        if (in_array($report, ['office-workload', 'transaction-aging', 'completed-work', 'overdue-action-required'], true)) {
            return [
                'offices' => $this->transactions->officeOptions($actor),
                ...$this->transactions->valueOptions($actor),
                'lifecycles' => [],
                'classifications' => [],
            ];
        }

        return [
            'offices' => $report === 'document-movement'
                ? $this->movements->officeOptions($actor)
                : $this->correspondence->officeOptions($actor),
            'statuses' => [],
            'priorities' => [],
            'transactionTypes' => [],
            ...$this->correspondence->valueOptions($actor),
        ];
    }

    private function authorizeOfficeFilter(array $filters, array $offices): void
    {
        if (! isset($filters['office'])) {
            return;
        }

        $allowed = collect($offices)->contains(
            fn (array $office): bool => (int) $office['id'] === (int) $filters['office'],
        );
        abort_unless($allowed, 403, 'The selected office is outside your report scope.');
    }

    private function normalize(mixed $result): array
    {
        if ($result instanceof LengthAwarePaginator) {
            return $result->toArray();
        }

        $rows = collect($result)->values();

        return [
            'data' => $rows->all(),
            'total' => $rows->count(),
            'current_page' => 1,
            'last_page' => 1,
            'per_page' => $rows->count(),
            'links' => [],
        ];
    }
}
