<?php

namespace App\Services;

use App\Domain\Correspondence\CorrespondenceLifecycleState;
use App\Models\CorrespondenceRecord;
use App\Models\User;
use App\Models\WorkflowTransaction;
use Carbon\CarbonInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CorrespondenceRoutingService
{
    private const WORKFLOW_TYPE = 'document_review';

    public function __construct(
        private readonly CorrespondenceAccessDecider $access,
        private readonly TransactionWorkflowService $workflow,
        private readonly CorrespondenceWorkflowStateMapper $workflowStates,
        private readonly CorrespondenceEventRecorder $events,
        private readonly TransactionalOutbox $outbox,
        private readonly AuditLogger $audit,
    ) {
    }

    /** @param array{target_department_id:int, priority:string, due_at?:mixed, remarks?:mixed} $data */
    public function route(
        User $actor,
        CorrespondenceRecord $correspondence,
        array $data,
        string $correlationId,
    ): CorrespondenceRecord {
        return DB::transaction(function () use ($actor, $correspondence, $data, $correlationId): CorrespondenceRecord {
            $locked = $this->lockClassifiedForRoute($actor, $correspondence);
            $workflow = $this->createLinkedWorkflow($actor, $locked, $data);
            $routedAt = now();

            $this->applyRoutedState($locked, $actor, $workflow, $routedAt);
            $this->recordRoutedBoundary($locked, $actor, $workflow, $data, $correlationId, $routedAt);

            return $locked->fresh(['workflowTransaction']);
        });
    }

    public function markInAction(
        User $actor,
        CorrespondenceRecord $correspondence,
        string $correlationId,
        ?string $remarks = null,
    ): CorrespondenceRecord {
        return DB::transaction(function () use ($actor, $correspondence, $correlationId, $remarks): CorrespondenceRecord {
            $locked = $this->lockRoutedForAction($actor, $correspondence);
            $workflow = $this->lockActionableWorkflow($locked);
            $actedAt = now();

            $this->applyInActionState($locked, $actor, $actedAt);
            $this->recordInActionBoundary($locked, $actor, $workflow, $remarks, $correlationId, $actedAt);

            return $locked->fresh(['workflowTransaction']);
        });
    }

    private function lockClassifiedForRoute(User $actor, CorrespondenceRecord $correspondence): CorrespondenceRecord
    {
        $locked = CorrespondenceRecord::query()->lockForUpdate()->findOrFail($correspondence->id);

        if ($locked->lifecycle_state !== CorrespondenceLifecycleState::Classified) {
            throw ValidationException::withMessages(['correspondence' => 'Only classified correspondence can be routed.']);
        }

        if ($locked->workflow_transaction_id !== null) {
            throw ValidationException::withMessages(['correspondence' => 'This correspondence is already linked to a workflow transaction.']);
        }

        if (! $this->access->canRoute($actor, $locked)) {
            throw new AuthorizationException('You are not authorized to route this correspondence.');
        }

        return $locked;
    }

    /** @param array{target_department_id:int, priority:string, due_at?:mixed, remarks?:mixed} $data */
    private function createLinkedWorkflow(
        User $actor,
        CorrespondenceRecord $record,
        array $data,
    ): WorkflowTransaction {
        $workflow = $this->workflow->createWithinExistingTransaction($actor, [
            'transaction_type' => self::WORKFLOW_TYPE,
            'title' => Str::limit(($record->municipal_reference_no ?? $record->external_reference_no).' — '.$record->subject, 255, ''),
            'description' => $record->summary,
            'priority' => $data['priority'],
            'target_department_id' => $data['target_department_id'],
            'due_at' => $data['due_at'] ?? null,
            'remarks' => $data['remarks'] ?? null,
        ]);

        if (! $this->workflowStates->representsRouted($workflow)) {
            throw ValidationException::withMessages(['workflow' => 'The newly created workflow transaction is not in the expected routed state.']);
        }

        return $workflow;
    }

    private function applyRoutedState(
        CorrespondenceRecord $record,
        User $actor,
        WorkflowTransaction $workflow,
        CarbonInterface $routedAt,
    ): void {
        $record->forceFill([
            'workflow_transaction_id' => $workflow->id,
            'routed_by_user_id' => $actor->id,
            'routed_at' => $routedAt,
            'lifecycle_state' => CorrespondenceLifecycleState::Routed,
        ])->save();
    }

    /** @param array{target_department_id:int, priority:string, due_at?:mixed, remarks?:mixed} $data */
    private function recordRoutedBoundary(
        CorrespondenceRecord $record,
        User $actor,
        WorkflowTransaction $workflow,
        array $data,
        string $correlationId,
        CarbonInterface $routedAt,
    ): void {
        $actor->loadMissing('employee');
        $officeId = (int) $actor->employee->department_id;
        $metadata = [
            'workflow_transaction_id' => $workflow->id,
            'workflow_reference_no' => $workflow->reference_no,
            'target_department_id' => (int) $workflow->current_department_id,
        ];

        $this->events->record(
            $record,
            'routed',
            CorrespondenceLifecycleState::Classified,
            CorrespondenceLifecycleState::Routed,
            actor: $actor,
            officeDepartmentId: $officeId,
            remarks: $data['remarks'] ?? null,
            metadata: $metadata,
            correlationId: $correlationId,
            occurredAt: $routedAt,
        );
        $this->outbox->record('correspondence.routed', 'correspondence_record', $record->public_id, [
            'correspondence_public_id' => $record->public_id,
            'municipal_reference_no' => $record->municipal_reference_no,
            'lifecycle_state' => CorrespondenceLifecycleState::Routed->value,
            ...$metadata,
        ], $routedAt);
        $this->audit->record(
            $actor,
            'correspondence.routed',
            'Human municipal actor routed classified correspondence through the existing workflow engine.',
            entityType: 'correspondence_record',
            entityId: $record->id,
        );
    }

    private function lockRoutedForAction(User $actor, CorrespondenceRecord $correspondence): CorrespondenceRecord
    {
        $locked = CorrespondenceRecord::query()->lockForUpdate()->findOrFail($correspondence->id);

        if ($locked->lifecycle_state !== CorrespondenceLifecycleState::Routed) {
            throw ValidationException::withMessages(['correspondence' => 'Only routed correspondence can enter action.']);
        }

        if (! $this->access->canAct($actor, $locked)) {
            throw new AuthorizationException('You are not authorized to act on this correspondence.');
        }

        if ($locked->workflow_transaction_id === null) {
            throw ValidationException::withMessages(['workflow' => 'A linked workflow transaction is required before correspondence can enter action.']);
        }

        return $locked;
    }

    private function lockActionableWorkflow(CorrespondenceRecord $record): WorkflowTransaction
    {
        $workflow = WorkflowTransaction::query()->lockForUpdate()->find($record->workflow_transaction_id);

        if (! $workflow instanceof WorkflowTransaction || ! $this->workflowStates->permitsInAction($workflow)) {
            throw ValidationException::withMessages(['workflow' => 'The linked workflow transaction has not reached an actionable state.']);
        }

        return $workflow;
    }

    private function applyInActionState(CorrespondenceRecord $record, User $actor, CarbonInterface $actedAt): void
    {
        $record->forceFill([
            'action_started_by_user_id' => $actor->id,
            'action_started_at' => $actedAt,
            'lifecycle_state' => CorrespondenceLifecycleState::InAction,
        ])->save();
    }

    private function recordInActionBoundary(
        CorrespondenceRecord $record,
        User $actor,
        WorkflowTransaction $workflow,
        ?string $remarks,
        string $correlationId,
        CarbonInterface $actedAt,
    ): void {
        $actor->loadMissing('employee');
        $metadata = [
            'workflow_transaction_id' => $workflow->id,
            'workflow_reference_no' => $workflow->reference_no,
            'workflow_status' => $workflow->status,
            'assigned_employee_id' => $workflow->assigned_employee_id,
        ];

        $this->events->record(
            $record,
            'in_action',
            CorrespondenceLifecycleState::Routed,
            CorrespondenceLifecycleState::InAction,
            actor: $actor,
            officeDepartmentId: (int) $actor->employee->department_id,
            remarks: $remarks,
            metadata: $metadata,
            correlationId: $correlationId,
            occurredAt: $actedAt,
        );
        $this->outbox->record('correspondence.in_action', 'correspondence_record', $record->public_id, [
            'correspondence_public_id' => $record->public_id,
            'municipal_reference_no' => $record->municipal_reference_no,
            'lifecycle_state' => CorrespondenceLifecycleState::InAction->value,
            'current_department_id' => (int) $workflow->current_department_id,
            ...$metadata,
        ], $actedAt);
        $this->audit->record(
            $actor,
            'correspondence.in_action',
            'Human municipal actor synchronized routed correspondence to an actionable linked workflow state.',
            entityType: 'correspondence_record',
            entityId: $record->id,
        );
    }
}
