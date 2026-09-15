<?php

namespace App\Domain\Workflow\Events;

final readonly class WorkflowTransactionTransitioned
{
    public function __construct(
        public int $transactionId,
        public int $transactionEventId,
        public int $actorUserId,
        public string $action,
        public ?int $assignmentEmployeeId,
    ) {
    }
}
