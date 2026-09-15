<?php

namespace App\Domain\Workflow\Listeners;

use App\Domain\Workflow\Events\WorkflowTransactionCreated;
use App\Domain\Workflow\Events\WorkflowTransactionTransitioned;
use App\Models\WorkflowTransaction;
use App\Services\CalendarService;

final readonly class SyncWorkflowTransactionCalendar
{
    public function __construct(private CalendarService $calendar)
    {
    }

    public function handle(
        WorkflowTransactionCreated|WorkflowTransactionTransitioned $domainEvent,
    ): void {
        $transaction = WorkflowTransaction::query()->findOrFail($domainEvent->transactionId);
        $this->calendar->syncTransactionDue($transaction);
    }
}
