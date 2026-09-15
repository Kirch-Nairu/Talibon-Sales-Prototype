<?php

namespace Tests\Feature;

use App\Domain\Workflow\Events\WorkflowTransactionCreated;
use App\Domain\Workflow\Events\WorkflowTransactionTransitioned;
use App\Models\Department;
use App\Models\User;
use App\Services\TransactionWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class WorkflowDomainEventBoundaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_mutations_dispatch_typed_events_and_keep_reactions_synchronous(): void
    {
        $this->seed();

        $created = [];
        $transitioned = [];
        Event::listen(WorkflowTransactionCreated::class, function ($event) use (&$created): void {
            $created[] = $event;
        });
        Event::listen(WorkflowTransactionTransitioned::class, function ($event) use (&$transitioned): void {
            $transitioned[] = $event;
        });

        $engineering = User::query()->where('email', 'engineering@talibon.demo')->firstOrFail();
        $budgetUser = User::query()->where('email', 'budget@talibon.demo')->firstOrFail();
        $budget = Department::query()->where('code', 'BUDGET')->firstOrFail();
        $workflow = app(TransactionWorkflowService::class);

        $transaction = $workflow->create($engineering, [
            'transaction_type' => 'internal_request',
            'title' => 'Domain event compatibility proof',
            'description' => 'Synthetic normalization fixture.',
            'priority' => 'high',
            'target_department_id' => $budget->id,
            'remarks' => 'Initial route.',
        ]);

        $this->assertCount(1, $created);
        $this->assertSame($transaction->id, $created[0]->transactionId);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'transaction.created',
            'entity_id' => $transaction->id,
        ]);
        $this->assertDatabaseHas('calendar_events', [
            'event_key' => 'transaction-due-'.$transaction->id,
        ]);
        $this->assertDatabaseHas('platform_notifications', [
            'user_id' => $budgetUser->id,
            'event_key' => 'transaction-event-'.$created[0]->transactionEventId,
        ]);

        $updated = $workflow->transition(
            $budgetUser,
            $transaction,
            'mark_review',
            remarks: 'Review started.',
        );

        $this->assertCount(1, $transitioned);
        $this->assertSame($updated->id, $transitioned[0]->transactionId);
        $this->assertSame('mark_review', $transitioned[0]->action);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'transaction.mark_review',
            'entity_id' => $updated->id,
        ]);
        $this->assertDatabaseHas('transaction_events', [
            'transaction_id' => $updated->id,
            'action' => 'mark_review',
            'new_status' => 'for_review',
        ]);
    }
}
