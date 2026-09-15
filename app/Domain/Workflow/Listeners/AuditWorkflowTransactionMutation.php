<?php

namespace App\Domain\Workflow\Listeners;

use App\Domain\Workflow\Events\WorkflowTransactionCreated;
use App\Domain\Workflow\Events\WorkflowTransactionTransitioned;
use App\Models\TransactionEvent;
use App\Models\User;
use App\Models\WorkflowTransaction;
use App\Services\AuditLogger;

final readonly class AuditWorkflowTransactionMutation
{
    public function __construct(private AuditLogger $audit)
    {
    }

    public function handle(
        WorkflowTransactionCreated|WorkflowTransactionTransitioned $domainEvent,
    ): void {
        $actor = User::query()->findOrFail($domainEvent->actorUserId);
        $transaction = WorkflowTransaction::query()->findOrFail($domainEvent->transactionId);

        if ($domainEvent instanceof WorkflowTransactionCreated) {
            $this->audit->record(
                $actor,
                'transaction.created',
                "Created and routed {$transaction->reference_no}.",
                'allowed',
                WorkflowTransaction::class,
                $transaction->id,
            );

            return;
        }

        $event = TransactionEvent::query()->findOrFail($domainEvent->transactionEventId);
        $this->audit->record(
            $actor,
            'transaction.'.$domainEvent->action,
            sprintf(
                '%s changed from %s to %s.',
                $transaction->reference_no,
                $event->previous_status,
                $event->new_status,
            ),
            'allowed',
            WorkflowTransaction::class,
            $transaction->id,
        );
    }
}
