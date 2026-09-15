<?php

namespace Tests\Feature;

use App\Http\Middleware\RequireMfaAssurance;
use App\Models\AuditLog;
use App\Models\CorrespondenceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Memorandum;
use App\Models\MemoRecipient;
use App\Models\User;
use App\Models\WorkflowTransaction;
use App\Services\PortalNavigationAccess;
use App\Services\TransactionLiveQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

class H4SystemAdminActionCompletenessTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_admin_can_assign_and_transition_nonterminal_transactions_without_widening_department_authority(): void
    {
        $this->seed();

        $admin = $this->user('admin@talibon.demo');
        $engineering = $this->user('engineering@talibon.demo');
        $employee = $this->user('employee@talibon.demo');
        $budget = $this->department('BUDGET');
        $budgetAssignee = Employee::query()
            ->where('department_id', $budget->id)
            ->where('employment_status', 'active')
            ->firstOrFail();
        $transaction = $this->transaction($engineering, $budget, 'submitted');

        $this->assertTrue($admin->can('transition', $transaction));
        $this->assertTrue($admin->can('assign', $transaction));
        $this->assertTrue($admin->can('mayorDecision', $transaction));

        $live = app(TransactionLiveQuery::class)->snapshot($admin, $transaction, includeEvents: false);
        $this->assertTrue($live['permissions']['canTransition']);
        $this->assertTrue($live['permissions']['canAssign']);
        $this->assertTrue($live['permissions']['canMayorDecision']);

        $this->withoutMiddleware(RequireMfaAssurance::class);

        $this->actingAs($admin)->post("/transactions/{$transaction->id}/transition", [
            'action' => 'assign',
            'assigned_employee_id' => $budgetAssignee->id,
            'remarks' => 'Synthetic H4 system-admin assignment proof.',
        ])->assertRedirect();

        $transaction->refresh();
        $this->assertSame((int) $budgetAssignee->id, (int) $transaction->assigned_employee_id);
        $this->assertSame((int) $budget->id, (int) $transaction->current_department_id);
        $this->assertDatabaseHas('transaction_events', [
            'transaction_id' => $transaction->id,
            'action' => 'assign',
        ]);

        $this->actingAs($admin)->post("/transactions/{$transaction->id}/transition", [
            'action' => 'mark_review',
            'remarks' => 'Synthetic H4 system-admin transition proof.',
        ])->assertRedirect();

        $transaction->refresh();
        $this->assertSame('for_review', $transaction->status);
        $this->assertDatabaseHas('transaction_events', [
            'transaction_id' => $transaction->id,
            'action' => 'mark_review',
            'new_status' => 'for_review',
        ]);

        $blocked = $this->transaction($engineering, $budget, 'submitted');
        $this->assertFalse($employee->can('transition', $blocked));
        $this->assertFalse($employee->can('assign', $blocked));
        $this->assertFalse($engineering->can('transition', $blocked));
        $this->assertFalse($engineering->can('assign', $blocked));

        $this->actingAs($employee)->post("/transactions/{$blocked->id}/transition", [
            'action' => 'mark_review',
        ])->assertForbidden();

        $this->actingAs($engineering)->post("/transactions/{$blocked->id}/transition", [
            'action' => 'assign',
            'assigned_employee_id' => $budgetAssignee->id,
        ])->assertForbidden();
    }

    public function test_system_admin_can_make_mayor_decision_but_terminal_state_still_fails_closed(): void
    {
        $this->seed();

        $admin = $this->user('admin@talibon.demo');
        $engineering = $this->user('engineering@talibon.demo');
        $employee = $this->user('employee@talibon.demo');
        $mayorOffice = $this->department('MAYOR');
        $transaction = $this->transaction($engineering, $mayorOffice, 'for_approval');

        $this->assertTrue($admin->can('mayorDecision', $transaction));
        $this->withoutMiddleware(RequireMfaAssurance::class);

        $this->actingAs($admin)->post("/transactions/{$transaction->id}/transition", [
            'action' => 'approve',
            'remarks' => 'Synthetic H4 system-admin Mayor decision proof.',
        ])->assertRedirect();

        $transaction->refresh();
        $this->assertSame('approved', $transaction->status);
        $this->assertNotNull($transaction->completed_at);
        $this->assertDatabaseHas('transaction_events', [
            'transaction_id' => $transaction->id,
            'action' => 'approve',
            'new_status' => 'approved',
        ]);

        $terminal = app(TransactionLiveQuery::class)->snapshot($admin, $transaction, includeEvents: false);
        $this->assertFalse($terminal['permissions']['canTransition']);
        $this->assertFalse($terminal['permissions']['canAssign']);
        $this->assertFalse($terminal['permissions']['canMayorDecision']);

        $eventCount = $transaction->events()->count();
        $this->actingAs($admin)
            ->from("/transactions/{$transaction->id}")
            ->post("/transactions/{$transaction->id}/transition", [
                'action' => 'disapprove',
                'remarks' => 'Must fail because the workflow is terminal.',
            ])
            ->assertRedirect("/transactions/{$transaction->id}")
            ->assertSessionHasErrors('action');

        $transaction->refresh();
        $this->assertSame('approved', $transaction->status);
        $this->assertSame($eventCount, $transaction->events()->count());

        $blocked = $this->transaction($engineering, $mayorOffice, 'for_approval');
        $this->assertFalse($employee->can('mayorDecision', $blocked));
        $this->assertFalse($engineering->can('mayorDecision', $blocked));

        $this->actingAs($employee)->post("/transactions/{$blocked->id}/transition", [
            'action' => 'approve',
        ])->assertForbidden();

        $this->actingAs($engineering)->post("/transactions/{$blocked->id}/transition", [
            'action' => 'disapprove',
        ])->assertForbidden();
    }

    public function test_system_admin_mayor_workspace_direct_route_and_navigation_authority_agree(): void
    {
        $this->seed();

        $admin = $this->user('admin@talibon.demo');
        $employee = $this->user('employee@talibon.demo');
        $engineering = $this->user('engineering@talibon.demo');
        $navigation = app(PortalNavigationAccess::class);

        $this->assertTrue($navigation->for($admin)['mayorOffice']);
        $this->assertFalse($navigation->for($employee)['mayorOffice']);
        $this->assertFalse($navigation->for($engineering)['mayorOffice']);

        $source = file_get_contents(resource_path('js/navigation/portalNavigation.ts'));
        $this->assertIsString($source);
        $this->assertStringContainsString(
            "system_administration: [\n        { label: 'Home', destinations: ['dashboard'] },\n        { label: 'Attention', destinations: ['mayorOffice'] },",
            $source,
        );
        $this->assertStringContainsString('.filter((item) => permissions[item.permission])', $source);
        $this->assertStringNotContainsString('role', strtolower($source));

        $this->withoutMiddleware(RequireMfaAssurance::class);
        $this->actingAs($admin)->get('/mayor-office')->assertOk();
        $this->actingAs($employee)->get('/mayor-office')->assertForbidden();
        $this->actingAs($engineering)->get('/mayor-office')->assertForbidden();
    }

    public function test_system_admin_can_publish_and_view_memoranda_without_granting_municipal_authority_to_department_actors(): void
    {
        $this->seed();

        $admin = $this->user('admin@talibon.demo');
        $engineering = $this->user('engineering@talibon.demo');
        $employee = $this->user('employee@talibon.demo');
        $budget = $this->department('BUDGET');
        $recipientEmployee = Employee::query()
            ->where('department_id', $budget->id)
            ->whereNotNull('user_id')
            ->where('employment_status', 'active')
            ->firstOrFail();

        $this->withoutMiddleware(RequireMfaAssurance::class);
        $this->actingAs($admin)->post('/memoranda', [
            'memo_number' => 'H4-MEMO-'.Str::upper(Str::random(10)),
            'title' => 'H4 System Administration Memorandum Authority',
            'body' => 'Synthetic memorandum used only for H4 authority regression coverage.',
            'audience_type' => 'employees',
            'audience_ids' => [$recipientEmployee->id],
            'requires_acknowledgement' => true,
            'classification' => 'confidential',
            'expires_at' => null,
        ])->assertRedirect();

        $memo = Memorandum::query()->where('title', 'H4 System Administration Memorandum Authority')->firstOrFail();
        $this->assertSame('published', $memo->status);
        $this->assertDatabaseHas('memo_recipients', [
            'memorandum_id' => $memo->id,
            'user_id' => $recipientEmployee->user_id,
        ]);
        $this->assertSame(1, MemoRecipient::query()->where('memorandum_id', $memo->id)->count());
        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $admin->id,
            'action' => 'memorandum.published',
            'outcome' => 'allowed',
        ]);

        $this->actingAs($admin)->get("/memoranda/{$memo->id}")->assertOk();
        $this->actingAs($engineering)->get("/memoranda/{$memo->id}")->assertForbidden();
        $this->actingAs($employee)->get("/memoranda/{$memo->id}")->assertForbidden();

        $payload = [
            'memo_number' => 'H4-DENIED-'.Str::upper(Str::random(10)),
            'title' => 'Denied municipal publication',
            'body' => 'Must not be published by an ordinary department actor.',
            'audience_type' => 'employees',
            'audience_ids' => [$recipientEmployee->id],
            'requires_acknowledgement' => false,
            'classification' => 'internal',
            'expires_at' => null,
        ];
        $this->actingAs($engineering)->post('/memoranda', $payload)->assertForbidden();
        $this->actingAs($employee)->post('/memoranda', $payload)->assertForbidden();
    }

    public function test_h4_preserves_restricted_confidential_health_and_mfa_boundaries(): void
    {
        $this->seed();

        $admin = $this->user('admin@talibon.demo');
        $targetEmployee = $this->user('employee@talibon.demo')->employee;
        $adminOffice = $admin->employee->department;

        $restricted = $this->correspondence($adminOffice, 'restricted');
        $confidential = $this->correspondence($adminOffice, 'confidential');

        $this->withoutMiddleware(RequireMfaAssurance::class);
        $restrictedResponse = $this->actingAs($admin)
            ->get('/correspondence/'.$restricted->public_id.'/workspace')
            ->assertForbidden();
        $this->assertStringNotContainsString($restricted->subject, $restrictedResponse->getContent());

        $confidentialResponse = $this->actingAs($admin)
            ->get('/correspondence/'.$confidential->public_id.'/workspace')
            ->assertForbidden();
        $this->assertStringNotContainsString($confidential->subject, $confidentialResponse->getContent());

        $this->actingAs($admin)->get('/hris/health/'.$targetEmployee->id)->assertForbidden();
        $this->assertTrue(AuditLog::query()
            ->where('actor_user_id', $admin->id)
            ->where('action', 'hr.health.access')
            ->where('outcome', 'denied')
            ->exists());

        foreach (['transactions.transition', 'mayor-office', 'memoranda.store', 'memoranda.show'] as $routeName) {
            $route = Route::getRoutes()->getByName($routeName);
            $this->assertNotNull($route);
            $middleware = $route->gatherMiddleware();
            $this->assertContains('auth', $middleware, "{$routeName} must remain authenticated.");
            $this->assertContains('active', $middleware, "{$routeName} must preserve active-account enforcement.");
            $this->assertContains('mfa.assured', $middleware, "{$routeName} must preserve MFA assurance.");
        }
    }

    private function user(string $email): User
    {
        return User::query()->where('email', $email)->with('employee.department')->firstOrFail();
    }

    private function department(string $code): Department
    {
        return Department::query()->where('code', $code)->firstOrFail();
    }

    private function transaction(User $creator, Department $currentDepartment, string $status): WorkflowTransaction
    {
        $terminal = in_array($status, ['approved', 'disapproved', 'closed'], true);

        return WorkflowTransaction::query()->create([
            'reference_no' => 'H4-TX-'.Str::upper(Str::random(12)),
            'transaction_type' => 'document_review',
            'title' => 'H4 authority fixture '.Str::random(8),
            'description' => 'Synthetic H4 transaction authority fixture.',
            'priority' => 'normal',
            'origin_department_id' => $creator->employee->department_id,
            'current_department_id' => $currentDepartment->id,
            'created_by_user_id' => $creator->id,
            'status' => $status,
            'received_at' => now()->subMinute(),
            'due_at' => now()->addDay(),
            'completed_at' => $terminal ? now() : null,
        ]);
    }

    private function correspondence(Department $department, string $classification): CorrespondenceRecord
    {
        return CorrespondenceRecord::query()->create([
            'public_id' => (string) Str::uuid(),
            'external_reference_no' => 'H4-EXT-'.Str::upper(Str::random(12)),
            'source' => 'email',
            'channel' => 'official_email',
            'sender_name' => 'Synthetic H4 Sender',
            'sender_organization' => 'Synthetic H4 Office',
            'sender_contact' => ['email' => 'h4-synthetic@example.test'],
            'subject' => 'H4 protected '.$classification.' correspondence '.Str::random(6),
            'summary' => 'Synthetic protected correspondence for H4 regression coverage.',
            'received_at' => now()->subHours(3),
            'receiving_department_id' => $department->id,
            'registered_at' => now()->subHours(2),
            'municipal_reference_no' => 'H4-COR-'.Str::upper(Str::random(12)),
            'classification' => $classification,
            'classified_at' => now()->subHour(),
            'lifecycle_state' => 'classified',
        ]);
    }
}
