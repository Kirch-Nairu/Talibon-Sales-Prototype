<?php

namespace Tests\Feature;

use App\Domain\Correspondence\CorrespondenceClassification;
use App\Domain\Correspondence\CorrespondenceLifecycleState;
use App\Domain\Integration\IntegrationScope;
use App\Models\CorrespondenceEvent;
use App\Models\CorrespondenceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\IntegrationClient;
use App\Models\IntegrationClientCredential;
use App\Models\OutboxMessage;
use App\Models\TransactionEvent;
use App\Models\User;
use App\Models\WorkflowTransaction;
use App\Services\CorrespondenceAccessDecider;
use App\Services\CorrespondenceRoutingService;
use App\Services\IntegrationClientService;
use App\Services\IntegrationCredentialService;
use App\Services\TransactionWorkflowService;
use App\Services\TransactionalOutbox;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class CorrespondenceCoreBTest extends TestCase
{
    use RefreshDatabase;

    public function test_classified_correspondence_routes_through_existing_workflow_engine_atomically(): void
    {
        [$record, $origin, $router] = $this->classified(CorrespondenceClassification::Internal);
        $target = $this->department('TARGET');

        $response = $this->route($router, $record, $target)->assertOk();

        $record = $record->fresh('workflowTransaction');
        $workflow = $record->workflowTransaction;
        $this->assertSame(CorrespondenceLifecycleState::Routed, $record->lifecycle_state);
        $this->assertNotNull($record->routed_at);
        $this->assertSame($router->id, $record->routed_by_user_id);
        $this->assertInstanceOf(WorkflowTransaction::class, $workflow);
        $this->assertSame($workflow->id, $record->workflow_transaction_id);
        $this->assertSame($origin->id, $workflow->origin_department_id);
        $this->assertSame($target->id, $workflow->current_department_id);
        $this->assertSame($router->id, $workflow->created_by_user_id);
        $this->assertSame('document_review', $workflow->transaction_type);
        $this->assertSame('submitted', $workflow->status);
        $this->assertSame($workflow->reference_no, $response->json('correspondence.workflow_reference'));
        $this->assertDatabaseCount('transactions', 1);
        $this->assertDatabaseHas('transaction_events', [
            'transaction_id' => $workflow->id,
            'action' => 'submitted',
            'actor_user_id' => $router->id,
        ]);
        $this->assertDatabaseHas('correspondence_events', [
            'correspondence_record_id' => $record->id,
            'event' => 'routed',
            'actor_user_id' => $router->id,
        ]);
        $this->assertDatabaseHas('outbox_messages', [
            'event_type' => 'correspondence.routed',
            'aggregate_id' => $record->public_id,
        ]);
    }

    public function test_received_and_registered_correspondence_cannot_route_early(): void
    {
        $origin = $this->department('EARLY');
        $router = $this->human('department_head', $origin);
        $target = $this->department('EARLY-TARGET');
        [, , $token] = $this->credential([IntegrationScope::CorrespondenceReceive->value]);
        $record = $this->receiveRecord($token, 'early-'.Str::uuid());

        $this->route($router, $record, $target)->assertUnprocessable();
        $this->actingAs($router)
            ->postJson('/correspondence/'.$record->public_id.'/register')
            ->assertOk();
        $this->route($router, $record->fresh(), $target)->assertUnprocessable();

        $this->assertDatabaseCount('transactions', 0);
        $this->assertNull($record->fresh()->workflow_transaction_id);
    }

    public function test_unauthorized_office_cannot_route_classified_correspondence(): void
    {
        [$record] = $this->classified(CorrespondenceClassification::Internal);
        $otherOffice = $this->department('OTHER');
        $otherHead = $this->human('department_head', $otherOffice);
        $target = $this->department('OTHER-TARGET');

        $this->route($otherHead, $record, $target)->assertForbidden();

        $this->assertDatabaseCount('transactions', 0);
        $this->assertSame(CorrespondenceLifecycleState::Classified, $record->fresh()->lifecycle_state);
    }

    public function test_confidential_and_restricted_classification_gate_human_read_and_route(): void
    {
        [$confidential, $office, $head] = $this->classified(CorrespondenceClassification::Confidential);
        $staff = $this->human('department_staff', $office);
        $target = $this->department('CONF-TARGET');

        $this->actingAs($staff)
            ->getJson('/correspondence/'.$confidential->public_id)
            ->assertForbidden();
        $this->route($staff, $confidential, $target)->assertForbidden();
        $this->actingAs($head)
            ->getJson('/correspondence/'.$confidential->public_id)
            ->assertOk()
            ->assertJsonPath('correspondence.classification', 'confidential');

        [$restricted, $restrictedOffice, $restrictedHead] = $this->classified(CorrespondenceClassification::Restricted);
        $restrictedStaff = $this->human('department_staff', $restrictedOffice);
        $restrictedTarget = $this->department('REST-TARGET');

        $this->actingAs($restrictedStaff)
            ->getJson('/correspondence/'.$restricted->public_id)
            ->assertForbidden();
        $this->route($restrictedStaff, $restricted, $restrictedTarget)->assertForbidden();
        $this->route($restrictedHead, $restricted, $restrictedTarget)->assertOk();
    }

    public function test_system_admin_has_no_automatic_restricted_content_access(): void
    {
        [$record, $office] = $this->classified(CorrespondenceClassification::Restricted);
        $systemAdmin = $this->human('system_admin', $office);
        $target = $this->department('ADMIN-TARGET');
        $access = app(CorrespondenceAccessDecider::class);

        $this->assertFalse($access->canView($systemAdmin, $record));
        $this->assertFalse($access->canRoute($systemAdmin, $record));
        $this->actingAs($systemAdmin)
            ->getJson('/correspondence/'.$record->public_id)
            ->assertForbidden();
        $this->route($systemAdmin, $record, $target)->assertForbidden();
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_repeated_route_attempt_does_not_create_second_workflow_transaction_or_outbox(): void
    {
        [$record, , $router] = $this->classified(CorrespondenceClassification::Internal);
        $target = $this->department('ONCE');

        $this->route($router, $record, $target)->assertOk();
        $this->route($router, $record->fresh(), $target)->assertUnprocessable();

        $this->assertDatabaseCount('transactions', 1);
        $this->assertSame(1, OutboxMessage::query()->where('event_type', 'correspondence.routed')->count());
        $this->assertSame(1, CorrespondenceEvent::query()->where('event', 'routed')->count());
    }

    public function test_workflow_creation_validation_failure_leaves_correspondence_unchanged(): void
    {
        [$record, $origin, $router] = $this->classified(CorrespondenceClassification::Internal);
        $eventsBefore = CorrespondenceEvent::query()->count();
        $outboxBefore = OutboxMessage::query()->count();

        $this->route($router, $record, $origin)->assertUnprocessable();

        $record = $record->fresh();
        $this->assertSame(CorrespondenceLifecycleState::Classified, $record->lifecycle_state);
        $this->assertNull($record->workflow_transaction_id);
        $this->assertDatabaseCount('transactions', 0);
        $this->assertSame($eventsBefore, CorrespondenceEvent::query()->count());
        $this->assertSame($outboxBefore, OutboxMessage::query()->count());
    }

    public function test_late_route_failure_rolls_back_workflow_link_event_and_outbox_atomically(): void
    {
        [$record, , $router] = $this->classified(CorrespondenceClassification::Internal);
        $target = $this->department('ROLLBACK');
        $eventsBefore = CorrespondenceEvent::query()->count();
        $outboxBefore = OutboxMessage::query()->count();

        $mock = Mockery::mock(TransactionalOutbox::class);
        $mock->shouldReceive('record')->once()->andThrow(new RuntimeException('force route rollback'));
        $this->app->instance(TransactionalOutbox::class, $mock);

        try {
            app(CorrespondenceRoutingService::class)->route(
                $router,
                $record,
                ['target_department_id' => $target->id, 'priority' => 'normal'],
                (string) Str::uuid(),
            );
            $this->fail('Expected forced route rollback.');
        } catch (RuntimeException $exception) {
            $this->assertSame('force route rollback', $exception->getMessage());
        }

        $record = $record->fresh();
        $this->assertSame(CorrespondenceLifecycleState::Classified, $record->lifecycle_state);
        $this->assertNull($record->workflow_transaction_id);
        $this->assertNull($record->routed_at);
        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('transaction_events', 0);
        $this->assertSame($eventsBefore, CorrespondenceEvent::query()->count());
        $this->assertSame($outboxBefore, OutboxMessage::query()->count());
        $this->assertDatabaseMissing('correspondence_events', ['event' => 'routed']);
        $this->assertDatabaseMissing('outbox_messages', ['event_type' => 'correspondence.routed']);
    }

    public function test_routed_correspondence_event_remains_append_only(): void
    {
        [$record, , $router] = $this->classified(CorrespondenceClassification::Internal);
        $target = $this->department('APPEND');
        $this->route($router, $record, $target)->assertOk();
        $event = CorrespondenceEvent::query()->where('event', 'routed')->sole();

        $this->expectException(LogicException::class);
        $event->update(['remarks' => 'attempted mutation']);
    }

    public function test_machine_client_cannot_route_correspondence(): void
    {
        [$record] = $this->classified(CorrespondenceClassification::Internal);
        $target = $this->department('MACHINE-TARGET');
        [, , $token] = $this->credential([IntegrationScope::CorrespondenceReceive->value]);
        auth()->logout();

        $this->withToken($token)
            ->postJson('/correspondence/'.$record->public_id.'/route', [
                'target_department_id' => $target->id,
                'priority' => 'normal',
            ])
            ->assertUnauthorized();

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_routed_correspondence_cannot_enter_action_before_workflow_is_actionable(): void
    {
        [$record, , $router] = $this->classified(CorrespondenceClassification::Internal);
        $target = $this->department('WAIT');
        $targetHead = $this->human('department_head', $target);
        $this->route($router, $record, $target)->assertOk();

        $this->actingAs($targetHead)
            ->postJson('/correspondence/'.$record->public_id.'/act')
            ->assertUnprocessable();

        $this->assertSame(CorrespondenceLifecycleState::Routed, $record->fresh()->lifecycle_state);
        $this->assertDatabaseMissing('correspondence_events', ['event' => 'in_action']);
    }

    public function test_routed_correspondence_enters_action_from_valid_linked_assignment_state(): void
    {
        [$record, , $router] = $this->classified(CorrespondenceClassification::Internal);
        $target = $this->department('ACTION');
        $targetHead = $this->human('department_head', $target);
        $this->route($router, $record, $target)->assertOk();
        $workflow = $record->fresh()->workflowTransaction;

        app(TransactionWorkflowService::class)->transition(
            $targetHead,
            $workflow,
            'assign',
            assignedEmployeeId: $targetHead->employee->id,
            remarks: 'Take action.',
        );

        $response = $this->actingAs($targetHead)
            ->postJson('/correspondence/'.$record->public_id.'/act', ['remarks' => 'Work started.'])
            ->assertOk()
            ->assertJsonPath('correspondence.lifecycle_state', CorrespondenceLifecycleState::InAction->value);

        $record = $record->fresh();
        $this->assertSame(CorrespondenceLifecycleState::InAction, $record->lifecycle_state);
        $this->assertSame($targetHead->id, $record->action_started_by_user_id);
        $this->assertNotNull($record->action_started_at);
        $this->assertSame('submitted', $response->json('correspondence.workflow_status'));
        $this->assertSame(1, CorrespondenceEvent::query()->where('event', 'in_action')->count());
        $this->assertSame(1, OutboxMessage::query()->where('event_type', 'correspondence.in_action')->count());
    }

    public function test_terminal_or_mismatched_workflow_state_fails_closed_for_action(): void
    {
        [$record, , $router] = $this->classified(CorrespondenceClassification::Internal);
        $target = $this->department('MISMATCH');
        $targetHead = $this->human('department_head', $target);
        $this->route($router, $record, $target)->assertOk();
        $workflow = $record->fresh()->workflowTransaction;
        $workflow->forceFill(['status' => 'closed'])->save();

        $this->actingAs($targetHead)
            ->postJson('/correspondence/'.$record->public_id.'/act')
            ->assertUnprocessable();

        $this->assertSame(CorrespondenceLifecycleState::Routed, $record->fresh()->lifecycle_state);
        $this->assertDatabaseMissing('outbox_messages', ['event_type' => 'correspondence.in_action']);
    }

    public function test_act_event_and_outbox_are_emitted_exactly_once(): void
    {
        [$record, , $router] = $this->classified(CorrespondenceClassification::Internal);
        $target = $this->department('ACT-ONCE');
        $targetHead = $this->human('department_head', $target);
        $this->route($router, $record, $target)->assertOk();
        $workflow = $record->fresh()->workflowTransaction;
        app(TransactionWorkflowService::class)->transition(
            $targetHead,
            $workflow,
            'assign',
            assignedEmployeeId: $targetHead->employee->id,
        );

        $this->actingAs($targetHead)
            ->postJson('/correspondence/'.$record->public_id.'/act')
            ->assertOk();
        $this->actingAs($targetHead)
            ->postJson('/correspondence/'.$record->public_id.'/act')
            ->assertUnprocessable();

        $this->assertSame(1, CorrespondenceEvent::query()->where('event', 'in_action')->count());
        $this->assertSame(1, OutboxMessage::query()->where('event_type', 'correspondence.in_action')->count());
    }

    public function test_integration_status_after_route_and_action_remains_external_safe(): void
    {
        [$record, , $router, $token] = $this->classified(
            CorrespondenceClassification::Restricted,
            [IntegrationScope::CorrespondenceReceive->value, IntegrationScope::CorrespondenceStatusRead->value],
        );
        $target = $this->department('STATUS');
        $targetHead = $this->human('department_head', $target);
        $this->route($router, $record, $target)->assertOk();
        $workflow = $record->fresh()->workflowTransaction;
        app(TransactionWorkflowService::class)->transition(
            $targetHead,
            $workflow,
            'assign',
            assignedEmployeeId: $targetHead->employee->id,
        );
        $this->actingAs($targetHead)
            ->postJson('/correspondence/'.$record->public_id.'/act')
            ->assertOk();

        $response = $this->withToken($token)
            ->getJson('/api/v1/correspondence/'.$record->public_id)
            ->assertOk()
            ->assertJsonPath('correspondence.lifecycle_state', CorrespondenceLifecycleState::InAction->value)
            ->assertJsonPath('correspondence.classification', null)
            ->assertJsonMissingPath('correspondence.workflow_transaction_id')
            ->assertJsonMissingPath('correspondence.workflow_reference')
            ->assertJsonMissingPath('correspondence.routed_by_user_id')
            ->assertJsonMissingPath('correspondence.action_started_by_user_id');

        $this->assertStringNotContainsString($workflow->reference_no, $response->getContent());
    }

    public function test_human_read_surface_exposes_history_only_to_classification_authorized_context(): void
    {
        [$record, $office, $head] = $this->classified(CorrespondenceClassification::Internal);
        $other = $this->human('department_head', $this->department('READ-OTHER'));

        $this->actingAs($head)
            ->getJson('/correspondence/'.$record->public_id)
            ->assertOk()
            ->assertJsonPath('correspondence.lifecycle_state', 'classified')
            ->assertJsonCount(3, 'correspondence.history');

        $this->actingAs($other)
            ->getJson('/correspondence/'.$record->public_id)
            ->assertForbidden();

        $this->assertSame($office->id, $record->fresh()->receiving_department_id);
    }

    /**
     * @param  array<int, string>|null  $scopes
     * @return array{0: CorrespondenceRecord, 1: Department, 2: User, 3: string}
     */
    private function classified(
        CorrespondenceClassification $classification,
        ?array $scopes = null,
    ): array {
        $origin = $this->department('ORIGIN');
        $head = $this->human('department_head', $origin);
        [, , $token] = $this->credential($scopes ?? [IntegrationScope::CorrespondenceReceive->value]);
        $record = $this->receiveRecord($token, 'core-b-'.Str::uuid());

        $this->actingAs($head)
            ->postJson('/correspondence/'.$record->public_id.'/register')
            ->assertOk();
        $this->actingAs($head)
            ->postJson('/correspondence/'.$record->public_id.'/classify', [
                'classification' => $classification->value,
            ])
            ->assertOk();

        return [$record->fresh(), $origin, $head, $token];
    }

    private function route(User $actor, CorrespondenceRecord $record, Department $target)
    {
        return $this->actingAs($actor)
            ->postJson('/correspondence/'.$record->public_id.'/route', [
                'target_department_id' => $target->id,
                'priority' => 'normal',
                'remarks' => 'Route through the existing workflow engine.',
            ]);
    }

    /**
     * @param  array<int, string>  $scopes
     * @return array{0: IntegrationClient, 1: IntegrationClientCredential, 2: string}
     */
    private function credential(array $scopes): array
    {
        $client = app(IntegrationClientService::class)->create(
            'Correspondence Core B Client '.Str::uuid(),
            500,
        );
        $issued = app(IntegrationCredentialService::class)->issue($client, $scopes);
        $credential = IntegrationClientCredential::query()
            ->where('public_id', $issued->publicId)
            ->firstOrFail();

        return [$client, $credential, $issued->plainTextToken];
    }

    private function receiveRecord(string $token, string $key): CorrespondenceRecord
    {
        $this->withHeader('Idempotency-Key', $key)
            ->withToken($token)
            ->postJson('/api/v1/correspondence', [
                'source' => 'partner_system',
                'channel' => 'api',
                'sender_name' => 'External Sender',
                'sender_organization' => 'Partner Office',
                'sender_contact' => ['email' => 'sender@example.test'],
                'subject' => 'Correspondence Core B '.Str::random(8),
                'summary' => 'Routing and action workflow bridge test.',
                'originating_external_reference' => 'EXT-'.Str::upper(Str::random(10)),
            ])
            ->assertCreated();

        return CorrespondenceRecord::query()->latest('id')->firstOrFail();
    }

    private function department(string $suffix): Department
    {
        return Department::query()->create([
            'code' => 'CB-'.Str::upper(Str::random(5)).'-'.$suffix,
            'name' => 'Core B '.$suffix,
            'is_active' => true,
            'is_routable' => true,
        ]);
    }

    private function human(string $role, Department $department): User
    {
        $user = User::query()->create([
            'name' => 'Core B '.$role.' '.Str::random(5),
            'email' => Str::lower(Str::random(10)).'@example.test',
            'password' => 'password',
            'role' => $role,
            'is_active' => true,
        ]);

        Employee::query()->create([
            'employee_number' => 'CB-EMP-'.Str::upper(Str::random(10)),
            'full_name' => $user->name,
            'work_email' => $user->email,
            'user_id' => $user->id,
            'department_id' => $department->id,
            'position_title' => 'Correspondence Officer',
            'employment_status' => 'active',
        ]);

        return $user->fresh('employee');
    }
}
