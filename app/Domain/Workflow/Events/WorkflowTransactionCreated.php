<?php

namespace App\Domain\Workflow\Events;

final readonly class WorkflowTransactionCreated
{
    public function __construct(
        public int $transactionId,
        public int $transactionEventId,
        public int $actorUserId,
    ) {
    }
}
