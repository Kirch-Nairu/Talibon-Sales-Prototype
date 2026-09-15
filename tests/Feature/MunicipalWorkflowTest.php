<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use App\Models\WorkflowTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MunicipalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_engineering_budget_mayor_approval_flow_preserves_routing_events(): void
    {
        $this->seed();

        $engineering = User::query()->where('email', 'engineering@talibon.demo')->firstOrFail();
        $budgetUser = User::query()->where('email', 'budget@talibon.demo')->firstOrFail();
        $mayorUser = User::query()->where('email', 'mayor@talibon.demo')->firstOrFail();
        $budget = Department::query()->where('code', 'BUDGET')->firstOrFail();
        $mayor = Department::query()->where('code', 'MAYOR')->firstOrFail();

        $this->actingAs($engineering)
            ->post('/transactions', [
                'transaction_type' => 'funding_request',
                'title' => 'Feature Test Road Rehabilitation Request',
                'description' => 'Synthetic automated workflow proof.',
                'priority' => 'high',
                'target_department_id' => $budget->id,
                'remarks' => 'For budget review.',
            ])
            ->assertRedirect();

        $transaction = WorkflowTransaction::query()
            ->where('title', 'Feature Test Road Rehabilitation Request')
            ->firstOrFail();

        $this->assertSame($budget->id, $transaction->current_department_id);
        $this->assertSame('submitted', $transaction->status);
        $this->assertDatabaseHas('transaction_events', [
            'transaction_id' => $transaction->id,
            'action' => 'submitted',
        ]);

        $this->actingAs($budgetUser)
            ->post("/transactions/{$transaction->id}/transition", [
                'action' => 'mark_review',
                'remarks' => 'Budget review started.',
            ])
            ->assertRedirect("/transactions/{$transaction->id}");

        $this->actingAs($budgetUser)
            ->post("/transactions/{$transaction->id}/transition", [
                'action' => 'send_to_mayor',
                'remarks' => 'Budget review complete. Forwarded for executive approval.',
            ])
            ->assertRedirect('/transactions');

        $transaction->refresh();
        $this->assertSame($mayor->id, $transaction->current_department_id);
        $this->assertSame('for_approval', $transaction->status);
        $this->assertNull($transaction->assigned_employee_id);
        $this->assertDatabaseHas('transaction_events', [
            'transaction_id' => $transaction->id,
            'action' => 'send_to_mayor',
            'previous_status' => 'for_review',
            'new_status' => 'for_approval',
            'from_department_id' => $budget->id,
            'to_department_id' => $mayor->id,
        ]);

        $this->actingAs($budgetUser)
            ->get("/transactions/{$transaction->id}")
            ->assertForbidden();

        $this->actingAs($engineering)
            ->get("/transactions/{$transaction->id}")
            ->assertOk();

        $this->actingAs($mayorUser)
            ->get("/transactions/{$transaction->id}")
            ->assertOk();

        $this->actingAs($mayorUser)
            ->post("/transactions/{$transaction->id}/transition", [
                'action' => 'approve',
                'remarks' => 'Approved in automated prototype proof.',
            ])
            ->assertRedirect("/transactions/{$transaction->id}");

        $transaction->refresh();
        $this->assertSame('approved', $transaction->status);
        $this->assertSame(4, $transaction->events()->count());
        $this->assertDatabaseHas('transaction_events', [
            'transaction_id' => $transaction->id,
            'action' => 'approve',
            'new_status' => 'approved',
        ]);
    }
}
