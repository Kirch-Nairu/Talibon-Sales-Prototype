<?php

namespace Tests\Feature;

use App\Domain\Workflow\Authorization\TransactionAccessDecider;
use App\Domain\Workflow\Authorization\TransactionAuthorizationContextFactory;
use App\Domain\Workflow\Authorization\TransactionCapabilities;
use App\Models\Department;
use App\Models\User;
use App\Models\WorkflowTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class TransactionAuthorizationNormalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_context_exposes_current_authorization_inputs_without_expanding_access(): void
    {
        $this->seed();

        $engineering = User::query()->where('email', 'engineering@talibon.demo')->firstOrFail();
        $budget = User::query()->where('email', 'budget@talibon.demo')->firstOrFail();
        $transaction = $this->transaction($engineering, $budget);
        $transaction->update(['assigned_employee_id' => $budget->employee->id]);

        $context = app(TransactionAuthorizationContextFactory::class)->make(
            $budget,
            TransactionAccessDecider::TRANSITION,
            $transaction->fresh(),
        );

        $this->assertSame(['department_head'], $context->actorRoles);
        $this->assertTrue($context->can(TransactionCapabilities::TRANSITION));
        $this->assertTrue($context->can(TransactionCapabilities::ASSIGN));
        $this->assertSame($budget->employee->department_id, $context->actorOfficeId);
        $this->assertSame($transaction->current_department_id, $context->resourceOfficeId);
        $this->assertSame($transaction->origin_department_id, $context->resourceOriginOfficeId);
        $this->assertSame($budget->employee->id, $context->resourceAssignedEmployeeId);
        $this->assertTrue($context->isActorAssigned());
        $this->assertSame([], $context->delegatedOfficeIds);
        $this->assertNull($context->classification);
        $this->assertSame('submitted', $context->workflowState);
        $this->assertSame(TransactionAccessDecider::TRANSITION, $context->requestedAction);
    }

    public function test_policy_preserves_existing_role_and_office_behavior(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@talibon.demo')->firstOrFail();
        $mayor = User::query()->where('email', 'mayor@talibon.demo')->firstOrFail();
        $engineering = User::query()->where('email', 'engineering@talibon.demo')->firstOrFail();
        $budget = User::query()->where('email', 'budget@talibon.demo')->firstOrFail();
        $employee = User::query()->where('email', 'employee@talibon.demo')->firstOrFail();
        $transaction = $this->transaction($engineering, $budget);

        $this->assertTrue(Gate::forUser($admin)->allows('view', $transaction));
        $this->assertTrue(Gate::forUser($admin)->allows('transition', $transaction));
        $this->assertTrue(Gate::forUser($admin)->allows('assign', $transaction));
        $this->assertTrue(Gate::forUser($admin)->allows('mayorDecision', $transaction));

        $this->assertTrue(Gate::forUser($engineering)->allows('view', $transaction));
        $this->assertFalse(Gate::forUser($engineering)->allows('transition', $transaction));
        $this->assertTrue(Gate::forUser($budget)->allows('transition', $transaction));
        $this->assertTrue(Gate::forUser($budget)->allows('assign', $transaction));
        $this->assertTrue(Gate::forUser($mayor)->allows('view', $transaction));
        $this->assertFalse(Gate::forUser($mayor)->allows('transition', $transaction));
        $this->assertFalse(Gate::forUser($mayor)->allows('mayorDecision', $transaction));
        $this->assertFalse(Gate::forUser($employee)->allows('view', $transaction));

        $mayorOffice = Department::query()->where('code', 'MAYOR')->firstOrFail();
        $transaction->update(['current_department_id' => $mayorOffice->id]);

        $this->assertTrue(Gate::forUser($mayor)->allows('mayorDecision', $transaction->fresh()));
    }

    private function transaction(User $originUser, User $currentUser): WorkflowTransaction
    {
        return WorkflowTransaction::query()->create([
            'reference_no' => 'AUTH-COMPAT-0001',
            'transaction_type' => 'internal_request',
            'title' => 'Authorization compatibility fixture',
            'priority' => 'normal',
            'origin_department_id' => $originUser->employee->department_id,
            'current_department_id' => $currentUser->employee->department_id,
            'created_by_user_id' => $originUser->id,
            'status' => 'submitted',
            'received_at' => now(),
            'due_at' => now()->addDays(5),
        ]);
    }
}
