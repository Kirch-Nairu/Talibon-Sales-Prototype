<?php

namespace App\Services\Reports;

use App\Models\CorrespondenceRecord;
use App\Models\Department;
use App\Models\User;
use App\Services\CorrespondenceAccessDecider;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class CorrespondenceStatusReportQuery
{
    public function __construct(
        private readonly CorrespondenceAccessDecider $access,
        private readonly ReportValuePresenter $presenter,
    ) {}

    public function rows(User $actor, array $filters, bool $paginate = true): mixed
    {
        $query = $this->query($actor, $filters);
        $mapper = fn (CorrespondenceRecord $record): array => $this->serialize($record);

        return $paginate
            ? $query->paginate(25)->withQueryString()->through($mapper)
            : $query->lazy(500)->map($mapper);
    }

    /** @return array<int, array{id:int,label:string}> */
    public function officeOptions(User $actor): array
    {
        $visible = $this->visible($actor)
            ->leftJoin('transactions', 'transactions.id', '=', 'correspondence_records.workflow_transaction_id')
            ->selectRaw('COALESCE(transactions.current_department_id, correspondence_records.receiving_department_id) AS office_id')
            ->whereNotNull(DB::raw('COALESCE(transactions.current_department_id, correspondence_records.receiving_department_id)'));

        return Department::query()->where('is_active', true)->whereIn('id', $visible)
            ->orderBy('name')->get(['id', 'code', 'name', 'short_name'])
            ->map(fn (Department $office): array => [
                'id' => (int) $office->id,
                'label' => ($office->short_name ?: $office->name).' ('.$office->code.')',
            ])->all();
    }

    /** @return array<string, array<int, string>> */
    public function valueOptions(User $actor): array
    {
        $base = $this->visible($actor);

        return [
            'lifecycles' => (clone $base)->distinct()->orderBy('lifecycle_state')
                ->toBase()->pluck('lifecycle_state')->all(),
            'classifications' => (clone $base)->whereNotNull('classification')->distinct()
                ->orderBy('classification')->toBase()->pluck('classification')->all(),
        ];
    }

    private function query(User $actor, array $filters): Builder
    {
        $correspondenceLast = DB::table('correspondence_events')
            ->selectRaw('correspondence_record_id, MAX(occurred_at) AS last_at')
            ->groupBy('correspondence_record_id');
        $workflowLast = DB::table('transaction_events')
            ->selectRaw('transaction_id, MAX(created_at) AS last_at')
            ->groupBy('transaction_id');

        $query = $this->visible($actor)
            ->leftJoin('transactions', 'transactions.id', '=', 'correspondence_records.workflow_transaction_id')
            ->leftJoinSub($correspondenceLast, 'correspondence_last', fn ($join) => $join
                ->on('correspondence_last.correspondence_record_id', '=', 'correspondence_records.id'))
            ->leftJoinSub($workflowLast, 'workflow_last', fn ($join) => $join
                ->on('workflow_last.transaction_id', '=', 'transactions.id'))
            ->select('correspondence_records.*')
            ->selectRaw('GREATEST(correspondence_last.last_at, workflow_last.last_at) AS report_last_movement_at')
            ->with([
                'receivingDepartment:id,name,short_name',
                'workflowTransaction:id,current_department_id,assigned_employee_id',
                'workflowTransaction.currentDepartment:id,name,short_name',
                'workflowTransaction.assignedEmployee:id,full_name',
            ]);

        if (isset($filters['office'])) {
            $query->whereRaw(
                'COALESCE(transactions.current_department_id, correspondence_records.receiving_department_id) = ?',
                [$filters['office']],
            );
        }
        if (isset($filters['lifecycle'])) {
            $query->where('correspondence_records.lifecycle_state', $filters['lifecycle']);
        }
        if (isset($filters['classification'])) {
            $query->where('correspondence_records.classification', $filters['classification']);
        }
        $this->applyDates($query, $filters, 'correspondence_records.received_at');

        return $query->orderByDesc('correspondence_records.received_at')
            ->orderByDesc('correspondence_records.id');
    }

    private function visible(User $actor): Builder
    {
        return $this->access->scopeVisibleTo(CorrespondenceRecord::query(), $actor);
    }

    /** @return array<string, mixed> */
    private function serialize(CorrespondenceRecord $record): array
    {
        $workflow = $record->workflowTransaction;
        $accountable = $workflow?->currentDepartment ?? $record->receivingDepartment;
        $last = $record->getAttribute('report_last_movement_at');

        return [
            'id' => $record->public_id,
            'municipalReference' => $record->municipal_reference_no ?? 'Pending registration',
            'externalReference' => $record->external_reference_no,
            'subject' => $record->subject,
            'sender' => $record->sender_name,
            'organization' => $record->sender_organization,
            'receivedAt' => $this->presenter->timestamp($record->received_at),
            'lifecycle' => $this->presenter->label($record->lifecycle_state->value),
            'classification' => $this->presenter->label($record->classification?->value) ?? 'Unclassified',
            'receivingOffice' => $record->receivingDepartment?->short_name ?? $record->receivingDepartment?->name,
            'accountableOffice' => $accountable?->short_name ?? $accountable?->name,
            'assignee' => $workflow?->assignedEmployee?->full_name ?? 'Unassigned',
            'lastMovementAt' => $last ? CarbonImmutable::parse($last)->timezone(config('app.timezone'))->toIso8601String() : null,
            'detailUrl' => route('correspondence.workspace.show', $record, false),
        ];
    }

    private function applyDates(Builder $query, array $filters, string $column): void
    {
        if (isset($filters['date_from'])) {
            $query->where($column, '>=', CarbonImmutable::parse($filters['date_from'], config('app.timezone'))->startOfDay());
        }
        if (isset($filters['date_to'])) {
            $query->where($column, '<=', CarbonImmutable::parse($filters['date_to'], config('app.timezone'))->endOfDay());
        }
    }
}
