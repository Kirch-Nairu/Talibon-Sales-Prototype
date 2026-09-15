<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use App\Models\WorkflowTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase1OrganizationRoutingTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase_one_seed_has_thirty_executive_and_three_legislative_routing_nodes(): void
    {
        $this->seed();

        $this->assertSame(33, Department::query()->activeRoutable()->count());
        $this->assertSame(30, Department::query()->activeRoutable()->inBranch('executive')->count());
        $this->assertSame(3, Department::query()->activeRoutable()->inBranch('legislative')->count());

        foreach (['TPC', 'POPULATION', 'DPO', 'CTC', 'VICE_MAYOR', 'SB', 'SB_SECRETARY'] as $code) {
            $this->assertDatabaseHas('departments', [
                'code' => $code,
                'is_active' => true,
                'is_routable' => true,
            ]);
        }
    }

    public function test_legislative_office_is_a_normal_auditable_routing_destination(): void
    {
        $this->seed();

        $engineering = User::query()->where('email', 'engineering@talibon.demo')->firstOrFail();
        $sbSecretary = Department::query()->where('code', 'SB_SECRETARY')->firstOrFail();

        $this->actingAs($engineering)->post('/transactions', [
            'transaction_type' => 'document_review',
            'title' => 'Phase 1 Legislative Routing Test',
            'priority' => 'normal',
            'target_department_id' => $sbSecretary->id,
            'remarks' => 'Route to the legislative branch without bypassing the shared workflow.',
        ])->assertRedirect();

        $transaction = WorkflowTransaction::query()->where('title', 'Phase 1 Legislative Routing Test')->firstOrFail();

        $this->assertSame($sbSecretary->id, $transaction->current_department_id);
        $this->assertDatabaseHas('transaction_events', [
            'transaction_id' => $transaction->id,
            'to_department_id' => $sbSecretary->id,
            'action' => 'submitted',
        ]);
    }

    public function test_non_routable_office_is_rejected_as_a_new_transaction_destination(): void
    {
        $this->seed();

        $engineering = User::query()->where('email', 'engineering@talibon.demo')->firstOrFail();
        $dpo = Department::query()->where('code', 'DPO')->firstOrFail();
        $dpo->update(['is_routable' => false]);

        $this->actingAs($engineering)->post('/transactions', [
            'transaction_type' => 'internal_request',
            'title' => 'Should Not Route',
            'priority' => 'normal',
            'target_department_id' => $dpo->id,
        ])->assertSessionHasErrors('target_department_id');

        $this->assertDatabaseMissing('transactions', ['title' => 'Should Not Route']);
    }
}
