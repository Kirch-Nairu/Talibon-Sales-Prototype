<?php

namespace App\Services;

use App\Models\CorrespondenceEvent;
use App\Models\CorrespondenceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use App\Models\WorkflowTransaction;

final class CorrespondenceDetailPresenter
{
    public function __construct(
        private readonly CorrespondenceAccessDecider $access,
        private readonly CorrespondenceWorkflowStateMapper $workflowStates,
        private readonly CorrespondenceTraceQuery $trace,
    ) {
    }

    /** @return array<string, mixed> */
    public function jsonContract(CorrespondenceRecord $record): array
    {
        $this->loadCore($record);

        return [
            'public_id' => $record->public_id,
            'reference' => $record->municipal_reference_no ?? $record->external_reference_no,
            'lifecycle_state' => $record->lifecycle_state->value,
            'classification' => $record->classification?->value,
            'subject' => $record->subject,
            'summary' => $record->summary,
            'sender_name' => $record->sender_name,
            'sender_organization' => $record->sender_organization,
            'received_at' => $record->received_at?->toISOString(),
            'registered_at' => $record->registered_at?->toISOString(),
            'classified_at' => $record->classified_at?->toISOString(),
            'routed_at' => $record->routed_at?->toISOString(),
            'action_started_at' => $record->action_started_at?->toISOString(),
            'receiving_department' => $record->receivingDepartment,
            'workflow' => $record->workflowTransaction,
            'history' => $this->jsonHistory($record),
        ];
    }

    /** @return array<string, mixed> */
    public function workspace(User $actor, CorrespondenceRecord $record): array
    {
        $this->loadCore($record);
        $workflow = $record->workflowTransaction;
        $currentOffice = $workflow?->currentDepartment ?? $record->receivingDepartment;
        $capabilities = $this->capabilities($actor, $record);
        $trace = $this->trace->forRecord($record);

        return [
            'correspondence' => [
                'publicId' => $record->public_id,
                'reference' => $record->municipal_reference_no ?? $record->external_reference_no,
                'municipalReference' => $record->municipal_reference_no,
                'externalReference' => $record->external_reference_no,
                'lifecycleState' => $record->lifecycle_state->value,
                'classification' => $record->classification?->value,
                'source' => [
                    'senderName' => $record->sender_name,
                    'senderOrganization' => $record->sender_organization,
                    'source' => $record->source,
                    'channel' => $record->channel,
                ],
                'content' => [
                    'subject' => $record->subject,
                    'summary' => $record->summary,
                ],
                'accountability' => [
                    'currentOffice' => $this->office($currentOffice),
                    'receivingOffice' => $this->office($record->receivingDepartment),
                    'workflow' => $this->workflow($actor, $workflow),
                ],
                'dates' => [
                    'receivedAt' => $record->received_at?->toISOString(),
                    'registeredAt' => $record->registered_at?->toISOString(),
                    'classifiedAt' => $record->classified_at?->toISOString(),
                    'routedAt' => $record->routed_at?->toISOString(),
                    'actionStartedAt' => $record->action_started_at?->toISOString(),
                ],
            ],
            'timeline' => $trace['timeline'],
            'capabilities' => $capabilities,
            'routeOptions' => $capabilities['canRoute']
                ? $this->routeOptions($actor)
                : [],
            'evidence' => $trace['evidence'],
        ];
    }

    private function loadCore(CorrespondenceRecord $record): void
    {
        $record->load([
            'receivingDepartment:id,code,name,short_name',
            'workflowTransaction:id,reference_no,status,current_department_id,assigned_employee_id',
            'workflowTransaction.currentDepartment:id,code,name,short_name',
            'workflowTransaction.assignedEmployee:id,employee_number,full_name,department_id,position_title',
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    private function jsonHistory(CorrespondenceRecord $record): array
    {
        return $record->events()
            ->with([
                'actorUser:id,name',
                'officeDepartment:id,code,name,short_name',
            ])
            ->orderBy('id')
            ->get()
            ->map(fn (CorrespondenceEvent $event): array => [
                'event' => $event->event,
                'previous_lifecycle_state' => $event->previous_lifecycle_state?->value,
                'new_lifecycle_state' => $event->new_lifecycle_state?->value,
                'actor_user' => $event->actorUser?->only(['id', 'name']),
                'office' => $event->officeDepartment?->only(['id', 'code', 'name', 'short_name']),
                'remarks' => $event->remarks,
                'occurred_at' => $event->occurred_at?->toISOString(),
            ])
            ->all();
    }

    /** @return array<string, mixed>|null */
    private function workflow(User $actor, ?WorkflowTransaction $workflow): ?array
    {
        if (! $workflow) {
            return null;
        }

        return [
            'reference' => $workflow->reference_no,
            'status' => $workflow->status,
            'currentOffice' => $this->office($workflow->currentDepartment),
            'assignedEmployee' => $this->employee($workflow->assignedEmployee),
            'detailUrl' => $actor->can('view', $workflow)
                ? route('transactions.show', $workflow, false)
                : null,
        ];
    }

    /** @return array{code:string,name:string,shortName:?string}|null */
    private function office(?Department $department): ?array
    {
        if (! $department) {
            return null;
        }

        return [
            'code' => $department->code,
            'name' => $department->name,
            'shortName' => $department->short_name,
        ];
    }

    /** @return array{employeeNumber:string,name:string,position:?string}|null */
    private function employee(?Employee $employee): ?array
    {
        if (! $employee) {
            return null;
        }

        return [
            'employeeNumber' => $employee->employee_number,
            'name' => $employee->full_name,
            'position' => $employee->position_title,
        ];
    }

    /** @return array<int, array{id:int,code:string,name:string,shortName:?string}> */
    private function routeOptions(User $actor): array
    {
        $actor->loadMissing('employee');
        $actorDepartmentId = $actor->employee?->department_id;

        return Department::query()
            ->activeRoutable()
            ->when($actorDepartmentId, fn ($query) => $query->where('id', '!=', $actorDepartmentId))
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

    /** @return array{canRegister:bool,canClassify:bool,canRoute:bool,canAct:bool} */
    private function capabilities(User $actor, CorrespondenceRecord $record): array
    {
        $workflow = $record->workflowTransaction;
        $canAct = $this->access->canAct($actor, $record)
            && $workflow instanceof WorkflowTransaction
            && $this->workflowStates->permitsInAction($workflow);

        return [
            'canRegister' => $this->access->canRegister($actor, $record),
            'canClassify' => $this->access->canClassify($actor, $record),
            'canRoute' => $this->access->canRoute($actor, $record),
            'canAct' => $canAct,
        ];
    }
}
