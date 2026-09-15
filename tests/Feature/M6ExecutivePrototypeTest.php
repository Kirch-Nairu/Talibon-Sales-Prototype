<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use App\Models\WorkflowTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class M6ExecutivePrototypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_seed_represents_350_portal_identities_with_seven_featured_demo_credentials(): void
    {
        $demoPassword = Str::random(32);
        config()->set('prototype.demo_password', $demoPassword);
        $this->seed();

        $this->assertSame(350, Employee::query()->where('employment_status', 'active')->count());
        $this->assertSame(350, Employee::query()->whereNotNull('user_id')->count());
        $this->assertSame(350, User::query()->where('is_active', true)->count());
        $this->assertGreaterThanOrEqual(28, Department::query()->where('is_active', true)->count());

        foreach ([
            'admin@talibon.demo',
            'mayor@talibon.demo',
            'engineering@talibon.demo',
            'budget@talibon.demo',
            'hr@talibon.demo',
            'legislative@talibon.demo',
            'employee@talibon.demo',
        ] as $email) {
            $user = User::query()->where('email', $email)->firstOrFail();
            $this->assertTrue($user->is_active);
            $this->assertTrue(Hash::check($demoPassword, $user->password));
        }
    }

    public function test_transaction_has_deadline_and_can_be_assigned_inside_receiving_office(): void
    {
        $this->seed();

        $engineering = User::query()->where('email', 'engineering@talibon.demo')->firstOrFail();
        $budgetUser = User::query()->where('email', 'budget@talibon.demo')->firstOrFail();
        $budget = Department::query()->where('code', 'BUDGET')->firstOrFail();
        $budgetEmployee = Employee::query()->where('department_id', $budget->id)->where('employment_status', 'active')->firstOrFail();

        $this->actingAs($engineering)->post('/transactions', [
            'transaction_type' => 'funding_request',
            'title' => 'M6 Accountability Feature Test',
            'priority' => 'high',
            'target_department_id' => $budget->id,
            'remarks' => 'Route with accountable deadline.',
        ])->assertRedirect();

        $transaction = WorkflowTransaction::query()->where('title', 'M6 Accountability Feature Test')->firstOrFail();
        $this->assertNotNull($transaction->received_at);
        $this->assertNotNull($transaction->due_at);

        $this->actingAs($budgetUser)->post("/transactions/{$transaction->id}/transition", [
            'action' => 'assign',
            'assigned_employee_id' => $budgetEmployee->id,
            'remarks' => 'Assigned for review.',
        ])->assertRedirect();

        $transaction->refresh();
        $this->assertSame($budgetEmployee->id, $transaction->assigned_employee_id);
        $this->assertDatabaseHas('transaction_events', [
            'transaction_id' => $transaction->id,
            'action' => 'assign',
        ]);
    }

    public function test_executive_surfaces_are_not_available_to_engineering_account(): void
    {
        $this->seed();

        $engineering = User::query()->where('email', 'engineering@talibon.demo')->firstOrFail();
        $mayor = User::query()->where('email', 'mayor@talibon.demo')->firstOrFail();

        $this->actingAs($engineering)->get('/operations')->assertForbidden();
        $this->actingAs($engineering)->get('/reports')->assertOk();

        $this->actingAs($mayor)->get('/operations')->assertOk();
        $this->actingAs($mayor)->get('/reports')->assertOk();
    }

    public function test_mayor_approver_has_executive_actions_without_generic_office_routing(): void
    {
        $this->seed();

        $mayor = User::query()->where('email', 'mayor@talibon.demo')->firstOrFail();
        $transaction = WorkflowTransaction::query()->whereHas('currentDepartment', fn ($query) => $query->where('code', 'MAYOR'))->firstOrFail();

        $this->assertTrue($mayor->can('mayorDecision', $transaction));
        $this->assertFalse($mayor->can('transition', $transaction));
    }

    public function test_hr_payroll_page_remains_parked_while_core_reports_excludes_payroll_export(): void
    {
        $this->seed();

        $hr = User::query()->where('email', 'hr@talibon.demo')->firstOrFail();
        $engineering = User::query()->where('email', 'engineering@talibon.demo')->firstOrFail();

        $this->actingAs($hr)->get('/hris/payroll')->assertOk();
        $this->actingAs($hr)->get('/reports/export/payroll-summary')->assertNotFound();
        $this->actingAs($engineering)->get('/reports/export/payroll-summary')->assertNotFound();
    }
}
