<?php

namespace App\Services\Reports;

use App\Models\CorrespondenceEvent;
use App\Models\CorrespondenceRecord;
use App\Models\Department;
use App\Models\TransactionEvent;
use App\Models\User;
use App\Services\CorrespondenceAccessDecider;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class DocumentMovementReportQuery
{
    public function __construct(
        private readonly CorrespondenceAccessDecider $access,
        private readonly ReportValuePresenter $presenter,
    ) {}

    public function rows(User $actor, array $filters, bool $paginate = true): mixed
    {
        $query = $this->query($actor, $filters);

        if ($paginate) {
            $paginator = $query->paginate(25)->withQueryString();
            $paginator->setCollection($paginator->getCollection()->map(fn ($row): array => $this->serialize($row)));

            return $paginator;
        }

        return $query->cursor()->map(fn ($row): array => $this->serialize($row));
    }

    /** @return array<int, array{id:int,label:string}> */
    public function officeOptions(User $actor): array
    {
        $visible = $this->visibleIds($actor);
        $ids = DB::table('correspondence_records as cr')
            ->leftJoin('transactions as tx', 'tx.id', '=', 'cr.workflow_transaction_id')
            ->whereIn('cr.id', $visible)
            ->selectRaw('COALESCE(tx.current_department_id, cr.receiving_department_id) AS office_id');

        return Department::query()->where('is_active', true)->whereIn('id', $ids)
            ->orderBy('name')->get(['id', 'code', 'name', 'short_name'])
            ->map(fn (Department $office): array => [
                'id' => (int) $office->id,
                'label' => ($office->short_name ?: $office->name).' ('.$office->code.')',
            ])->all();
    }

    private function query(User $actor, array $filters): Builder
    {
        $correspondence = $this->correspondenceEvents($actor, $filters);
        $workflow = $this->workflowEvents($actor, $filters);

        return DB::query()->fromSub($correspondence->unionAll($workflow), 'movement_rows')
            ->orderByDesc('occurred_at')
            ->orderBy('sort_rank')
            ->orderBy('source_id');
    }

    private function correspondenceEvents(User $actor, array $filters): Builder
    {
        $submitted = DB::table('transaction_events')
            ->where('action', 'submitted')
            ->selectRaw('DISTINCT ON (transaction_id) transaction_id, id, from_department_id, to_department_id')
            ->orderBy('transaction_id')->orderBy('created_at')->orderBy('id');

        $query = DB::table('correspondence_events as ce')
            ->join('correspondence_records as cr', 'cr.id', '=', 'ce.correspondence_record_id')
            ->leftJoin('transactions as tx', 'tx.id', '=', 'cr.workflow_transaction_id')
            ->leftJoinSub($submitted, 'submitted_event', fn ($join) => $join
                ->on('submitted_event.transaction_id', '=', 'tx.id'))
            ->leftJoin('users as actor_user', 'actor_user.id', '=', 'ce.actor_user_id')
            ->leftJoin('integration_clients as actor_client', 'actor_client.id', '=', 'ce.integration_client_actor_id')
            ->leftJoin('departments as event_office', 'event_office.id', '=', 'ce.office_department_id')
            ->leftJoin('departments as fallback_from', 'fallback_from.id', '=', 'submitted_event.from_department_id')
            ->leftJoin('departments as fallback_to', 'fallback_to.id', '=', 'submitted_event.to_department_id')
            ->leftJoin('departments as route_target', function ($join): void {
                $join->on('route_target.id', '=', DB::raw("NULLIF(ce.metadata->>'target_department_id', '')::bigint"));
            })
            ->leftJoin('departments as accountable_office', 'accountable_office.id', '=', 'tx.current_department_id')
            ->leftJoin('departments as receiving_office', 'receiving_office.id', '=', 'cr.receiving_department_id')
            ->leftJoin('employees as assignee', 'assignee.id', '=', 'tx.assigned_employee_id')
            ->whereIn('cr.id', $this->visibleIds($actor))
            ->selectRaw("'correspondence' AS source")
            ->selectRaw('ce.id AS source_id, cr.id AS record_id, cr.public_id, cr.municipal_reference_no, cr.subject')
            ->selectRaw('ce.event, ce.remarks, ce.occurred_at')
            ->selectRaw('COALESCE(actor_user.name, actor_client.name) AS actor_name')
            ->selectRaw("CASE WHEN ce.event = 'routed' THEN COALESCE(event_office.short_name, event_office.name, fallback_from.short_name, fallback_from.name) END AS from_office")
            ->selectRaw("CASE WHEN ce.event = 'routed' THEN COALESCE(route_target.short_name, route_target.name, fallback_to.short_name, fallback_to.name) END AS to_office")
            ->selectRaw('COALESCE(accountable_office.short_name, accountable_office.name, receiving_office.short_name, receiving_office.name) AS accountable_office')
            ->selectRaw('assignee.full_name AS assignee_name')
            ->selectRaw("CASE WHEN ce.event = 'in_action' THEN 2 ELSE 0 END AS sort_rank")
            ->selectRaw(
                "(EXISTS (SELECT 1 FROM document_links dl WHERE dl.linkable_type = ? AND dl.linkable_id = ce.id) OR (ce.event = 'routed' AND EXISTS (SELECT 1 FROM document_links sdl WHERE sdl.linkable_type = ? AND sdl.linkable_id = submitted_event.id))) AS has_evidence",
                [(new CorrespondenceEvent)->getMorphClass(), (new TransactionEvent)->getMorphClass()],
            );

        return $this->applyRecordFilters($query, $filters, 'ce.occurred_at');
    }

    private function workflowEvents(User $actor, array $filters): Builder
    {
        $query = DB::table('transaction_events as te')
            ->join('transactions as tx', 'tx.id', '=', 'te.transaction_id')
            ->join('correspondence_records as cr', 'cr.workflow_transaction_id', '=', 'tx.id')
            ->leftJoin('users as actor_user', 'actor_user.id', '=', 'te.actor_user_id')
            ->leftJoin('departments as from_office', 'from_office.id', '=', 'te.from_department_id')
            ->leftJoin('departments as to_office', 'to_office.id', '=', 'te.to_department_id')
            ->leftJoin('departments as accountable_office', 'accountable_office.id', '=', 'tx.current_department_id')
            ->leftJoin('departments as receiving_office', 'receiving_office.id', '=', 'cr.receiving_department_id')
            ->leftJoin('employees as assignee', 'assignee.id', '=', 'tx.assigned_employee_id')
            ->whereIn('cr.id', $this->visibleIds($actor))
            ->where(function (Builder $events): void {
                $events->where('te.action', '!=', 'submitted')
                    ->orWhereNotExists(function (Builder $route): void {
                        $route->selectRaw('1')->from('correspondence_events as route_event')
                            ->whereColumn('route_event.correspondence_record_id', 'cr.id')
                            ->where('route_event.event', 'routed');
                    });
            })
            ->selectRaw("'workflow' AS source")
            ->selectRaw('te.id AS source_id, cr.id AS record_id, cr.public_id, cr.municipal_reference_no, cr.subject')
            ->selectRaw('te.action AS event, te.remarks, te.created_at AS occurred_at')
            ->selectRaw('actor_user.name AS actor_name')
            ->selectRaw('COALESCE(from_office.short_name, from_office.name) AS from_office')
            ->selectRaw('COALESCE(to_office.short_name, to_office.name) AS to_office')
            ->selectRaw('COALESCE(accountable_office.short_name, accountable_office.name, receiving_office.short_name, receiving_office.name) AS accountable_office')
            ->selectRaw('assignee.full_name AS assignee_name')
            ->selectRaw('1 AS sort_rank')
            ->selectRaw(
                'EXISTS (SELECT 1 FROM document_links dl WHERE dl.linkable_type = ? AND dl.linkable_id = te.id) AS has_evidence',
                [(new TransactionEvent)->getMorphClass()],
            );

        return $this->applyRecordFilters($query, $filters, 'te.created_at');
    }

    private function applyRecordFilters(Builder $query, array $filters, string $dateColumn): Builder
    {
        if (isset($filters['office'])) {
            $query->whereRaw('COALESCE(tx.current_department_id, cr.receiving_department_id) = ?', [$filters['office']]);
        }
        if (isset($filters['lifecycle'])) {
            $query->where('cr.lifecycle_state', $filters['lifecycle']);
        }
        if (isset($filters['classification'])) {
            $query->where('cr.classification', $filters['classification']);
        }
        if (isset($filters['date_from'])) {
            $query->where($dateColumn, '>=', CarbonImmutable::parse($filters['date_from'], config('app.timezone'))->startOfDay());
        }
        if (isset($filters['date_to'])) {
            $query->where($dateColumn, '<=', CarbonImmutable::parse($filters['date_to'], config('app.timezone'))->endOfDay());
        }

        return $query;
    }

    private function visibleIds(User $actor): \Illuminate\Database\Eloquent\Builder
    {
        return $this->access->scopeVisibleTo(CorrespondenceRecord::query(), $actor)->select('correspondence_records.id');
    }

    /** @return array<string, mixed> */
    private function serialize(object $row): array
    {
        return [
            'id' => $row->source.':'.$row->source_id,
            'municipalReference' => $row->municipal_reference_no ?? 'Pending registration',
            'subject' => $row->subject,
            'event' => $this->presenter->label($row->event),
            'source' => $this->presenter->label($row->source),
            'fromOffice' => $row->from_office,
            'toOffice' => $row->to_office,
            'actor' => $row->actor_name,
            'remarks' => $row->remarks,
            'occurredAt' => CarbonImmutable::parse($row->occurred_at)->timezone(config('app.timezone'))->toIso8601String(),
            'hasEvidence' => (bool) $row->has_evidence ? 'Yes' : 'No',
            'accountableOffice' => $row->accountable_office,
            'assignee' => $row->assignee_name ?? 'Unassigned',
            'detailUrl' => route('correspondence.workspace.show', ['correspondence' => $row->public_id], false),
        ];
    }
}
