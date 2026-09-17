<?php

namespace Tests\Feature;

use App\Domain\Correspondence\CorrespondenceClassification;
use App\Domain\Correspondence\CorrespondenceLifecycleState;
use App\Domain\TravelOrders\TravelOrderStatus;
use App\Models\CorrespondenceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\TravelOrder;
use App\Models\User;
use App\Models\WorkflowTransaction;
use App\Services\AuthenticationAssurance;
use App\Services\TransactionWorkflowService;
use App\Support\ValidatedListReturn;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class W03ContextContinuityTest extends TestCase
{
    use RefreshDatabase;

    public function test_return_target_strips_nested_context_and_rejects_unsafe_or_wrong_routes(): void
    {
        $this->assertSame(
            '/transactions?view=all&page=2&priority=high',
            ValidatedListReturn::validate(
                '/transactions?view=all&return_to=%2Ftransactions%3Fpage%3D9&page=2&priority=high',
                '/transactions',
            ),
        );

        $this->assertSame(
            '/transactions?view=all&page=2',
            ValidatedListReturn::validate(
                '/transactions?view=all&return_to%5Bstale%5D=%2Ftransactions%3Fpage%3D9&page=2',
                '/transactions',
            ),
        );

        foreach ([
            'https://evil.example/transactions?page=9',
            '//evil.example/transactions?page=9',
            '/correspondence?page=9',
            '/transactions\\evil?page=9',
            '/transactions?search=bad%encoding',
            "/transactions?search=line%0Abreak",
        ] as $unsafe) {
            $this->assertSame('/transactions', ValidatedListReturn::validate($unsafe, '/transactions'));
        }
    }

    public function test_transaction_transition_preserves_filtered_paged_context_without_nesting(): void
    {
        $office = $this->department('TX-STAY');
        $actor = $this->human('department_head', $office);
        $transaction = $this->transaction($office, $office, $actor, 'Context-preserving transaction');
        $returnTo = '/transactions?view=all&priority=high&page=2';
        $nested = $returnTo.'&return_to='.rawurlencode('/transactions?page=9');

        $this->actingAs($actor)
            ->from('/transactions/'.$transaction->id.'?return_to='.rawurlencode($nested))
            ->post('/transactions/'.$transaction->id.'/transition', [
                'action' => 'mark_review',
                'remarks' => 'Preserve list context.',
            ])
            ->assertRedirect(route('transactions.show', [
                'transaction' => $transaction,
                'return_to' => $returnTo,
            ], false));

        $this->assertSame('for_review', $transaction->fresh()->status);
    }

    public function test_transaction_transition_returns_to_validated_list_when_actor_loses_detail_access(): void
    {
        $origin = $this->department('TX-ORIGIN');
        $current = $this->department('TX-CURRENT');
        $target = $this->department('TX-TARGET');
        $creator = $this->human('department_head', $origin);
        $actor = $this->human('department_head', $current);
        $transaction = $this->transaction($origin, $current, $creator, 'Forwarded out of actor scope');
        $returnTo = '/transactions?view=office_queue&priority=urgent&page=2';

        $this->actingAs($actor)
            ->from('/transactions/'.$transaction->id.'?return_to='.rawurlencode($returnTo))
            ->post('/transactions/'.$transaction->id.'/transition', [
                'action' => 'forward',
                'target_department_id' => $target->id,
                'remarks' => 'Forward beyond current office scope.',
            ])
            ->assertRedirect($returnTo);

        $this->assertSame($target->id, $transaction->fresh()->current_department_id);
        $this->actingAs($actor)->get('/transactions/'.$transaction->id)->assertForbidden();
    }

    public function test_correspondence_register_and_classify_preserve_validated_list_context(): void
    {
        $office = $this->department('COR-REGISTER');
        $actor = $this->human('department_head', $office);
        $record = $this->correspondence('Context intake', CorrespondenceLifecycleState::Received);
        $returnTo = '/correspondence?search=permit&page=2';
        $detail = '/correspondence/'.$record->public_id.'/workspace';

        $this->actingAs($actor)
            ->from($detail.'?return_to='.rawurlencode($returnTo))
            ->post($detail.'/register')
            ->assertRedirect(route('correspondence.workspace.show', [
                'correspondence' => $record,
                'return_to' => $returnTo,
            ], false));

        $this->assertSame(CorrespondenceLifecycleState::Registered, $record->fresh()->lifecycle_state);

        $this->actingAs($actor)
            ->from($detail.'?return_to='.rawurlencode($returnTo))
            ->post($detail.'/classify', [
                'classification' => 'internal',
                'remarks' => 'Classification retains originating list context.',
            ])
            ->assertRedirect(route('correspondence.workspace.show', [
                'correspondence' => $record,
                'return_to' => $returnTo,
            ], false));

        $this->assertSame(CorrespondenceLifecycleState::Classified, $record->fresh()->lifecycle_state);
    }

    public function test_correspondence_route_returns_to_validated_filtered_list_context(): void
    {
        $origin = $this->department('COR-ROUTE-ORIGIN');
        $target = $this->department('COR-ROUTE-TARGET');
        $actor = $this->human('department_head', $origin);
        $record = $this->correspondence(
            'Context route',
            CorrespondenceLifecycleState::Classified,
            $origin,
            CorrespondenceClassification::Internal,
        );
        $returnTo = '/correspondence?search=routing&page=3';
        $detail = '/correspondence/'.$record->public_id.'/workspace';

        $this->actingAs($actor)
            ->from($detail.'?return_to='.rawurlencode($returnTo))
            ->post($detail.'/route', [
                'target_department_id' => $target->id,
                'priority' => 'normal',
                'remarks' => 'Route while retaining list context.',
            ])
            ->assertRedirect($returnTo);

        $this->assertSame(CorrespondenceLifecycleState::Routed, $record->fresh()->lifecycle_state);
    }

    public function test_correspondence_act_preserves_validated_list_context_on_workspace_redirect(): void
    {
        $origin = $this->department('COR-ACT-ORIGIN');
        $target = $this->department('COR-ACT-TARGET');
        $originHead = $this->human('department_head', $origin);
        $targetHead = $this->human('department_head', $target);
        $record = $this->correspondence(
            'Context action',
            CorrespondenceLifecycleState::Classified,
            $origin,
            CorrespondenceClassification::Internal,
        );

        $this->actingAs($originHead)->post('/correspondence/'.$record->public_id.'/workspace/route', [
            'target_department_id' => $target->id,
            'priority' => 'normal',
        ])->assertRedirect('/correspondence');

        $workflow = $record->fresh()->workflowTransaction;
        app(TransactionWorkflowService::class)->transition(
            $targetHead,
            $workflow,
            'assign',
            assignedEmployeeId: $targetHead->employee->id,
        );

        $returnTo = '/correspondence?search=actionable&page=2';
        $detail = '/correspondence/'.$record->public_id.'/workspace';
        $this->actingAs($targetHead)
            ->from($detail.'?return_to='.rawurlencode($returnTo))
            ->post($detail.'/act', ['remarks' => 'Action started with context intact.'])
            ->assertRedirect(route('correspondence.workspace.show', [
                'correspondence' => $record,
                'return_to' => $returnTo,
            ], false));

        $this->assertSame(CorrespondenceLifecycleState::InAction, $record->fresh()->lifecycle_state);
    }

    public function test_travel_order_status_mutation_preserves_filtered_paged_list_context(): void
    {
        $mayor = $this->department('TRAVEL-MAYOR', 'MAYOR');
        $office = $this->department('TRAVEL-OFFICE');
        $approver = $this->human('mayor_approver', $mayor);
        $traveler = $this->human('employee', $office);
        $order = $this->travelOrder($office, $traveler->employee);
        $returnTo = '/travel-orders?office_id='.$office->id.'&search=official&page=2';

        $this->assure($approver)
            ->from('/travel-orders/'.$order->public_id.'?return_to='.rawurlencode($returnTo))
            ->post('/travel-orders/'.$order->public_id.'/status', [
                'status' => 'completed',
                'remarks' => 'Travel concluded.',
            ])
            ->assertRedirect(route('travel-orders.show', [
                'travelOrder' => $order,
                'return_to' => $returnTo,
            ], false));

        $this->assertSame(TravelOrderStatus::Completed, $order->fresh()->status);
    }

    private function assure(User $user): static
    {
        return $this->actingAs($user)->withSession([
            AuthenticationAssurance::SESSION_USER_KEY => $user->id,
            AuthenticationAssurance::SESSION_VERSION_KEY => $user->mfa_version,
            AuthenticationAssurance::SESSION_VERIFIED_AT_KEY => now()->timestamp,
        ]);
    }

    private function department(string $suffix, ?string $code = null): Department
    {
        return Department::query()->create([
            'code' => $code ?? 'W03-'.Str::upper(Str::random(5)).'-'.$suffix,
            'name' => 'W03 '.$suffix,
            'short_name' => 'W03-'.$suffix,
            'branch' => 'executive',
            'office_type' => 'department',
            'sort_order' => 10,
            'is_routable' => true,
            'is_active' => true,
        ]);
    }

    private function human(string $role, Department $department): User
    {
        $user = User::query()->create([
            'name' => 'W03 '.$role.' '.Str::random(5),
            'email' => Str::lower(Str::random(10)).'@example.test',
            'password' => 'password',
            'role' => $role,
            'is_active' => true,
        ]);

        if (in_array($role, config('identity.privileged_roles', []), true)) {
            $user->forceFill([
                'mfa_secret' => 'w03-context-mfa-secret',
                'mfa_confirmed_at' => now(),
                'mfa_version' => 0,
            ])->save();
        }

        Employee::query()->create([
            'employee_number' => 'W03-EMP-'.Str::upper(Str::random(10)),
            'full_name' => $user->name,
            'work_email' => $user->email,
            'user_id' => $user->id,
            'department_id' => $department->id,
            'position_title' => 'W03 Context Officer',
            'employment_status' => 'active',
        ]);

        return $user->fresh('employee.department');
    }

    private function transaction(
        Department $origin,
        Department $current,
        User $creator,
        string $title,
    ): WorkflowTransaction {
        return WorkflowTransaction::query()->create([
            'reference_no' => 'W03-TX-'.Str::upper(Str::random(12)),
            'transaction_type' => 'internal_request',
            'title' => $title,
            'description' => 'W03 context continuity transaction.',
            'priority' => 'high',
            'origin_department_id' => $origin->id,
            'current_department_id' => $current->id,
            'created_by_user_id' => $creator->id,
            'status' => 'submitted',
            'received_at' => now()->subHours(2),
            'due_at' => now()->addDays(2),
        ]);
    }

    private function correspondence(
        string $subject,
        CorrespondenceLifecycleState $state,
        ?Department $department = null,
        ?CorrespondenceClassification $classification = null,
    ): CorrespondenceRecord {
        $registered = $state !== CorrespondenceLifecycleState::Received;
        $classified = $state === CorrespondenceLifecycleState::Classified;

        return CorrespondenceRecord::query()->create([
            'public_id' => (string) Str::uuid(),
            'external_reference_no' => 'W03-COR-'.Str::upper(Str::random(12)),
            'source' => 'email',
            'channel' => 'official_email',
            'sender_name' => 'W03 Sender',
            'sender_organization' => 'W03 Office',
            'subject' => $subject,
            'summary' => 'W03 context continuity correspondence.',
            'received_at' => now()->subHours(5),
            'receiving_department_id' => $department?->id,
            'registered_at' => $registered ? now()->subHours(4) : null,
            'municipal_reference_no' => $registered ? 'TAL-COR-W03-'.Str::upper(Str::random(8)) : null,
            'classification' => $classification?->value,
            'classified_at' => $classified ? now()->subHours(3) : null,
            'lifecycle_state' => $state->value,
        ]);
    }

    private function travelOrder(Department $department, Employee $employee): TravelOrder
    {
        $order = TravelOrder::query()->create([
            'reference_number' => 'W03-TO-'.Str::upper(Str::random(10)),
            'issuance_date' => '2026-09-01',
            'purpose' => 'W03 official travel context test',
            'destination' => 'W03 destination',
            'department_id' => $department->id,
            'travel_start_date' => '2026-09-05',
            'travel_end_date' => '2026-09-06',
            'status' => TravelOrderStatus::Approved,
        ]);
        $order->issuedTo()->sync([$employee->id]);

        return $order->fresh();
    }
}
