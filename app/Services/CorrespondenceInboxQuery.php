<?php

namespace App\Services;

use App\Models\CorrespondenceRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class CorrespondenceInboxQuery
{
    private const TERMINAL_WORKFLOW_STATUSES = ['approved', 'disapproved', 'closed'];

    public function __construct(
        private readonly CorrespondenceAccessDecider $access,
    ) {
    }

    public function paginate(User $actor, array $filters): LengthAwarePaginator
    {
        $query = CorrespondenceRecord::query()
            ->with([
                'receivingDepartment:id,code,name,short_name',
                'workflowTransaction:id,reference_no,status,current_department_id,assigned_employee_id,due_at',
                'workflowTransaction.currentDepartment:id,code,name,short_name',
                'workflowTransaction.assignedEmployee:id,employee_number,full_name,department_id,position_title',
            ]);

        $this->access->scopeVisibleTo($query, $actor);
        $this->applyFilters($query, $actor, $filters);

        return $query
            ->orderByDesc('received_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (CorrespondenceRecord $record): array => $this->serialize($actor, $record));
    }

    private function applyFilters(Builder $query, User $actor, array $filters): void
    {
        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $needle = '%'.strtolower($search).'%';
            $query->where(function (Builder $matching) use ($needle): void {
                foreach ([
                    'municipal_reference_no',
                    'external_reference_no',
                    'sender_name',
                    'sender_organization',
                    'source',
                    'subject',
                ] as $column) {
                    $matching->orWhereRaw("LOWER({$column}) LIKE ?", [$needle]);
                }
            });
        }

        if (! empty($filters['lifecycle'])) {
            $query->where('lifecycle_state', $filters['lifecycle']);
        }

        if (! empty($filters['classification'])) {
            $query->where('classification', $filters['classification']);
        }

        if (! empty($filters['office_id'])) {
            $this->applyOfficeFilter($query, (int) $filters['office_id']);
        }

        if (! empty($filters['assigned_to_me'])) {
            $employeeId = $actor->employee?->id;
            $employeeId
                ? $query->whereHas('workflowTransaction', fn (Builder $workflow) => $workflow->where('assigned_employee_id', $employeeId))
                : $query->whereRaw('1 = 0');
        }

        if (! empty($filters['action_required'])) {
            $this->access->scopeActionRequired($query, $actor);
        }

        if (($filters['aging'] ?? null) === 'overdue') {
            $query->whereHas('workflowTransaction', function (Builder $workflow): void {
                $workflow->whereNotNull('due_at')
                    ->where('due_at', '<', now())
                    ->whereNotIn('status', self::TERMINAL_WORKFLOW_STATUSES);
            });
        }
    }

    private function applyOfficeFilter(Builder $query, int $officeId): void
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

    private function serialize(User $actor, CorrespondenceRecord $record): array
    {
        $workflow = $record->workflowTransaction;
        $currentOffice = $workflow?->currentDepartment ?? $record->receivingDepartment;
        $assignee = $workflow?->assignedEmployee;
        $overdue = $workflow?->due_at?->isPast() === true
            && ! in_array($workflow->status, self::TERMINAL_WORKFLOW_STATUSES, true);

        return [
            'publicId' => $record->public_id,
            'reference' => $record->municipal_reference_no ?? $record->external_reference_no,
            'sender' => [
                'name' => $record->sender_name,
                'organization' => $record->sender_organization,
                'source' => $record->source,
                'channel' => $record->channel,
            ],
            'subject' => $record->subject,
            'classification' => $record->classification?->value,
            'lifecycleState' => $record->lifecycle_state->value,
            'currentOffice' => $currentOffice ? [
                'id' => $currentOffice->id,
                'code' => $currentOffice->code,
                'name' => $currentOffice->name,
                'shortName' => $currentOffice->short_name,
            ] : null,
            'assignedEmployee' => $assignee ? [
                'id' => $assignee->id,
                'employeeNumber' => $assignee->employee_number,
                'name' => $assignee->full_name,
                'position' => $assignee->position_title,
            ] : null,
            'workflowReference' => $workflow?->reference_no,
            'receivedAt' => $record->received_at?->toIso8601String(),
            'age' => $record->received_at?->diffForHumans(now(), true) ?? 'Not recorded',
            'actionRequired' => $this->access->requiresAction($actor, $record),
            'overdue' => $overdue,
        ];
    }
}
