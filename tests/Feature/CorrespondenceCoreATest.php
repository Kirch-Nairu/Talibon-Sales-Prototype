<?php

namespace Tests\Feature;

use App\Domain\Correspondence\CorrespondenceClassification;
use App\Domain\Correspondence\CorrespondenceLifecycleState;
use App\Domain\Integration\IntegrationErrorCode;
use App\Domain\Integration\IntegrationRequestAttributes;
use App\Domain\Integration\IntegrationScope;
use App\Models\AuditLog;
use App\Models\CorrespondenceEvent;
use App\Models\CorrespondenceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\IntegrationClient;
use App\Models\IntegrationClientCredential;
use App\Models\OutboxMessage;
use App\Models\User;
use App\Services\CorrespondenceAccessDecider;
use App\Services\CorrespondenceReceiveService;
use App\Services\IntegrationClientService;
use App\Services\IntegrationCredentialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;
use RuntimeException;
use Tests\TestCase;

class CorrespondenceCoreATest extends TestCase
{
    use RefreshDatabase;

    public function test_integration_receive_with_valid_scope_creates_authoritative_received_boundary(): void
    {
        [$client, $credential, $token] = $this->credential([
            IntegrationScope::CorrespondenceReceive->value,
            IntegrationScope::CorrespondenceStatusRead->value,
        ]);

        $response = $this->receive($token, 'receive-'.Str::uuid(), $this->payload('Initial correspondence'));

        $response->assertCreated()
            ->assertJsonPath('correspondence.lifecycle_state', CorrespondenceLifecycleState::Received->value);

        $record = CorrespondenceRecord::query()->sole();
        $this->assertSame($client->id, $record->receiving_integration_client_id);
        $this->assertSame($credential->id, $record->receiving_integration_client_credential_id);
        $this->assertSame($credential->public_id, $record->received_source_identity);
        $this->assertNull($record->registered_by_user_id);
        $this->assertNull($record->classified_by_user_id);
        $this->assertNull($record->workflow_transaction_id);

        $event = CorrespondenceEvent::query()->sole();
        $this->assertSame('received', $event->event);
        $this->assertSame($client->id, $event->integration_client_actor_id);
        $this->assertNull($event->actor_user_id);
        $this->assertSame($response->json('correlation_id'), $event->correlation_id);

        $outbox = OutboxMessage::query()->sole();
        $this->assertSame('correspondence.received', $outbox->event_type);
        $this->assertSame($record->public_id, $outbox->aggregate_id);
        $this->assertSame($record->public_id, $response->json('correspondence.public_id'));
    }

    public function test_receive_scope_is_required(): void
    {
        [, , $token] = $this->credential([IntegrationScope::CorrespondenceStatusRead->value]);

        $this->receive($token, 'missing-receive-'.Str::uuid(), $this->payload('Denied'))
            ->assertForbidden()
            ->assertJsonPath('error.code', IntegrationErrorCode::ScopeDenied->value);

        $this->assertDatabaseCount('correspondence_records', 0);
    }

    public function test_status_scope_is_required(): void
    {
        [, , $token] = $this->credential([IntegrationScope::CorrespondenceReceive->value]);
        $record = $this->receiveRecord($token, 'status-scope-'.Str::uuid(), $this->payload('Status scope'));

        $this->withToken($token)
            ->getJson('/api/v1/correspondence/'.$record->public_id)
            ->assertForbidden()
            ->assertJsonPath('error.code', IntegrationErrorCode::ScopeDenied->value);
    }

    public function test_integration_client_cannot_read_another_clients_correspondence(): void
    {
        [, , $tokenA] = $this->credential([
            IntegrationScope::CorrespondenceReceive->value,
            IntegrationScope::CorrespondenceStatusRead->value,
        ]);
        [, , $tokenB] = $this->credential([IntegrationScope::CorrespondenceStatusRead->value]);
        $record = $this->receiveRecord($tokenA, 'owner-'.Str::uuid(), $this->payload('Owned by A'));

        $this->withToken($tokenB)
            ->getJson('/api/v1/correspondence/'.$record->public_id)
            ->assertNotFound()
            ->assertJsonPath('error.code', IntegrationErrorCode::CorrespondenceNotFound->value);
    }

    public function test_same_receive_key_and_payload_executes_once_and_replays_same_logical_result(): void
    {
        [, , $token] = $this->credential([IntegrationScope::CorrespondenceReceive->value]);
        $key = 'receive-replay-'.Str::uuid();
        $payload = $this->payload('Replay correspondence');

        $original = $this->receive($token, $key, $payload);
        $replay = $this->receive($token, $key, $payload);

        $original->assertCreated();
        $replay->assertCreated()->assertHeader('Idempotency-Replayed', 'true');
        $this->assertSame($original->json('correspondence'), $replay->json('correspondence'));
        $this->assertNotSame($original->json('correlation_id'), $replay->json('correlation_id'));
        $this->assertDatabaseCount('correspondence_records', 1);
        $this->assertDatabaseCount('correspondence_events', 1);
        $this->assertDatabaseCount('outbox_messages', 1);
    }

    public function test_same_receive_key_with_different_payload_conflicts_without_duplicate_mutation(): void
    {
        [, , $token] = $this->credential([IntegrationScope::CorrespondenceReceive->value]);
        $key = 'receive-conflict-'.Str::uuid();

        $this->receive($token, $key, $this->payload('First payload'))->assertCreated();
        $this->receive($token, $key, $this->payload('Different payload'))
            ->assertStatus(409)
            ->assertJsonPath('error.code', IntegrationErrorCode::IdempotencyConflict->value);

        $this->assertDatabaseCount('correspondence_records', 1);
        $this->assertDatabaseCount('correspondence_events', 1);
        $this->assertDatabaseCount('outbox_messages', 1);
    }

    public function test_correspondence_history_is_append_only(): void
    {
        [, , $token] = $this->credential([IntegrationScope::CorrespondenceReceive->value]);
        $this->receiveRecord($token, 'append-only-'.Str::uuid(), $this->payload('Append only'));
        $event = CorrespondenceEvent::query()->sole();

        $this->expectException(LogicException::class);
        $event->update(['remarks' => 'attempted mutation']);
    }

    public function test_registration_requires_authenticated_human_actor(): void
    {
        [, , $token] = $this->credential([IntegrationScope::CorrespondenceReceive->value]);
        $record = $this->receiveRecord($token, 'human-register-'.Str::uuid(), $this->payload('Needs registrar'));

        $this->postJson('/correspondence/'.$record->public_id.'/register')
            ->assertUnauthorized();

        $this->assertSame(CorrespondenceLifecycleState::Received, $record->fresh()->lifecycle_state);
    }

    public function test_registration_is_state_guarded_and_reference_numbers_are_unique_and_counter_locked(): void
    {
        $department = $this->department('REG');
        $registrar = $this->human('department_staff', $department);
        [, , $token] = $this->credential([IntegrationScope::CorrespondenceReceive->value]);
        $first = $this->receiveRecord($token, 'register-a-'.Str::uuid(), $this->payload('Register A'));
        $second = $this->receiveRecord($token, 'register-b-'.Str::uuid(), $this->payload('Register B'));

        $firstResponse = $this->actingAs($registrar)
            ->postJson('/correspondence/'.$first->public_id.'/register')
            ->assertOk();
        $secondResponse = $this->actingAs($registrar)
            ->postJson('/correspondence/'.$second->public_id.'/register')
            ->assertOk();

        $firstReference = $firstResponse->json('correspondence.reference');
        $secondReference = $secondResponse->json('correspondence.reference');
        $this->assertNotSame($firstReference, $secondReference);
        $this->assertMatchesRegularExpression('/^TAL-COR-\d{4}-\d{6}$/', $firstReference);
        $this->assertDatabaseCount('correspondence_reference_counters', 1);
        $this->assertSame(2, (int) DB::table('correspondence_reference_counters')->value('last_value'));

        $this->actingAs($registrar)
            ->postJson('/correspondence/'.$first->public_id.'/register')
            ->assertUnprocessable();

        $this->assertDatabaseCount('correspondence_records', 2);
        $this->assertDatabaseCount('correspondence_events', 4);
        $this->assertDatabaseCount('outbox_messages', 4);
    }

    public function test_classification_before_registration_fails(): void
    {
        $department = $this->department('CLASS-EARLY');
        $classifier = $this->human('department_head', $department);
        [, , $token] = $this->credential([IntegrationScope::CorrespondenceReceive->value]);
        $record = $this->receiveRecord($token, 'classify-early-'.Str::uuid(), $this->payload('Classify early'));

        $this->actingAs($classifier)
            ->postJson('/correspondence/'.$record->public_id.'/classify', [
                'classification' => CorrespondenceClassification::Internal->value,
            ])
            ->assertUnprocessable();
    }

    public function test_unauthorized_human_cannot_classify_registered_correspondence(): void
    {
        $department = $this->department('CLASS-DENY');
        $staff = $this->human('department_staff', $department);
        [, , $token] = $this->credential([IntegrationScope::CorrespondenceReceive->value]);
        $record = $this->receiveRecord($token, 'classify-deny-'.Str::uuid(), $this->payload('Classification denied'));

        $this->actingAs($staff)
            ->postJson('/correspondence/'.$record->public_id.'/register')
            ->assertOk();
        $this->actingAs($staff)
            ->postJson('/correspondence/'.$record->public_id.'/classify', [
                'classification' => CorrespondenceClassification::Restricted->value,
            ])
            ->assertForbidden();

        $this->assertSame(CorrespondenceLifecycleState::Registered, $record->fresh()->lifecycle_state);
    }

    public function test_classification_changes_content_authorization_and_system_admin_is_not_global_authority(): void
    {
        $department = $this->department('ACCESS');
        $classifier = $this->human('department_head', $department);
        $staff = $this->human('department_staff', $department);
        $systemAdmin = $this->human('system_admin', $department);
        [, , $token] = $this->credential([IntegrationScope::CorrespondenceReceive->value]);
        $record = $this->receiveRecord($token, 'access-'.Str::uuid(), $this->payload('Sensitive content'));

        $this->actingAs($staff)
            ->postJson('/correspondence/'.$record->public_id.'/register')
            ->assertOk();
        $registered = $record->fresh();
        $access = app(CorrespondenceAccessDecider::class);
        $this->assertTrue($access->canView($staff, $registered));
        $this->assertFalse($access->canView($systemAdmin, $registered));

        $this->actingAs($classifier)
            ->postJson('/correspondence/'.$record->public_id.'/classify', [
                'classification' => CorrespondenceClassification::Restricted->value,
            ])
            ->assertOk();

        $restricted = $record->fresh();
        $this->assertFalse($access->canView($staff, $restricted));
        $this->assertFalse($access->canView($systemAdmin, $restricted));
        $this->assertTrue($access->canView($classifier, $restricted));
    }

    public function test_integration_status_is_safe_and_does_not_leak_internal_classification_or_remarks(): void
    {
        $department = $this->department('SAFE');
        $classifier = $this->human('department_head', $department);
        [, , $token] = $this->credential([
            IntegrationScope::CorrespondenceReceive->value,
            IntegrationScope::CorrespondenceStatusRead->value,
        ]);
        $record = $this->receiveRecord($token, 'safe-status-'.Str::uuid(), $this->payload('Safe status'));

        $this->actingAs($classifier)
            ->postJson('/correspondence/'.$record->public_id.'/register')
            ->assertOk();
        $this->actingAs($classifier)
            ->postJson('/correspondence/'.$record->public_id.'/classify', [
                'classification' => CorrespondenceClassification::Restricted->value,
                'remarks' => 'Internal classification rationale must not leak.',
            ])
            ->assertOk();

        $response = $this->withToken($token)
            ->getJson('/api/v1/correspondence/'.$record->public_id)
            ->assertOk()
            ->assertJsonPath('correspondence.classification', null)
            ->assertJsonMissingPath('correspondence.registered_by_user_id')
            ->assertJsonMissingPath('correspondence.classified_by_user_id');

        $serialized = $response->getContent();
        $this->assertStringNotContainsString('Internal classification rationale must not leak.', $serialized);
        $this->assertStringNotContainsString('audit_logs', $serialized);
    }

    public function test_machine_intake_never_creates_user_or_writes_machine_identity_into_human_actor_columns(): void
    {
        [$client, , $token] = $this->credential([IntegrationScope::CorrespondenceReceive->value]);
        $userCount = User::query()->count();

        $record = $this->receiveRecord($token, 'machine-actor-'.Str::uuid(), $this->payload('Machine actor'));

        $this->assertSame($userCount, User::query()->count());
        $this->assertSame($client->id, $record->receiving_integration_client_id);
        $this->assertNull($record->registered_by_user_id);
        $this->assertNull($record->classified_by_user_id);
        $event = CorrespondenceEvent::query()->sole();
        $this->assertSame($client->id, $event->integration_client_actor_id);
        $this->assertNull($event->actor_user_id);
    }

    public function test_failed_outer_receive_transaction_rolls_back_record_history_outbox_and_audit_mutation(): void
    {
        [, , $token] = $this->credential([IntegrationScope::CorrespondenceReceive->value]);
        $context = app(IntegrationCredentialService::class)->authenticate($token);
        $correlationId = (string) Str::uuid();

        try {
            DB::transaction(function () use ($context, $correlationId): void {
                request()->attributes->set(IntegrationRequestAttributes::CORRELATION_ID, $correlationId);
                app(CorrespondenceReceiveService::class)->receive(
                    $context,
                    $this->payload('Force rollback'),
                    $correlationId,
                );
                throw new RuntimeException('force correspondence receive rollback');
            });
        } catch (RuntimeException) {
        }

        $this->assertDatabaseCount('correspondence_records', 0);
        $this->assertDatabaseCount('correspondence_events', 0);
        $this->assertDatabaseCount('outbox_messages', 0);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'correspondence.received']);
    }

    public function test_receive_audit_and_history_share_request_correlation_identity(): void
    {
        [$client, , $token] = $this->credential([IntegrationScope::CorrespondenceReceive->value]);
        $response = $this->receive($token, 'correlation-'.Str::uuid(), $this->payload('Correlated'))
            ->assertCreated();
        $correlationId = (string) $response->json('correlation_id');

        $this->assertSame($correlationId, CorrespondenceEvent::query()->sole()->correlation_id);
        $this->assertTrue(AuditLog::query()
            ->where('integration_client_id', $client->id)
            ->where('correlation_id', $correlationId)
            ->where('action', 'correspondence.received')
            ->exists());
    }

    public function test_register_and_classify_append_history_and_outbox_while_workflow_link_remains_deferred(): void
    {
        $department = $this->department('BRIDGE');
        $head = $this->human('department_head', $department);
        [, , $token] = $this->credential([IntegrationScope::CorrespondenceReceive->value]);
        $record = $this->receiveRecord($token, 'bridge-'.Str::uuid(), $this->payload('Workflow bridge'));

        $this->actingAs($head)
            ->postJson('/correspondence/'.$record->public_id.'/register')
            ->assertOk();
        $this->actingAs($head)
            ->postJson('/correspondence/'.$record->public_id.'/classify', [
                'classification' => CorrespondenceClassification::Internal->value,
            ])
            ->assertOk();

        $record = $record->fresh();
        $this->assertSame(CorrespondenceLifecycleState::Classified, $record->lifecycle_state);
        $this->assertNull($record->workflow_transaction_id);
        $this->assertSame(
            ['received', 'registered', 'classified'],
            CorrespondenceEvent::query()->orderBy('id')->pluck('event')->all(),
        );
        $this->assertSame(
            ['correspondence.received', 'correspondence.registered', 'correspondence.classified'],
            OutboxMessage::query()->orderBy('id')->pluck('event_type')->all(),
        );
    }

    /**
     * @param  array<int, string>  $scopes
     * @return array{0: IntegrationClient, 1: IntegrationClientCredential, 2: string}
     */
    private function credential(array $scopes): array
    {
        $client = app(IntegrationClientService::class)->create(
            'Correspondence Core A Client '.Str::uuid(),
            500,
        );
        $issued = app(IntegrationCredentialService::class)->issue($client, $scopes);
        $credential = IntegrationClientCredential::query()
            ->where('public_id', $issued->publicId)
            ->firstOrFail();

        return [$client, $credential, $issued->plainTextToken];
    }

    /** @return array<string, mixed> */
    private function payload(string $subject): array
    {
        return [
            'source' => 'partner_system',
            'channel' => 'api',
            'sender_name' => 'External Sender',
            'sender_organization' => 'Partner Office',
            'sender_contact' => [
                'email' => 'sender@example.test',
                'phone' => '+639171234567',
            ],
            'subject' => $subject,
            'summary' => 'Correspondence Core A intake test.',
            'originating_external_reference' => 'EXT-'.Str::upper(Str::random(10)),
        ];
    }

    private function receive(string $token, string $key, array $payload)
    {
        return $this->withHeader('Idempotency-Key', $key)
            ->withToken($token)
            ->postJson('/api/v1/correspondence', $payload);
    }

    private function receiveRecord(string $token, string $key, array $payload): CorrespondenceRecord
    {
        $this->receive($token, $key, $payload)->assertCreated();

        return CorrespondenceRecord::query()->latest('id')->firstOrFail();
    }

    private function department(string $suffix): Department
    {
        return Department::query()->create([
            'code' => 'COR-'.Str::upper(Str::random(5)).'-'.$suffix,
            'name' => 'Correspondence '.$suffix,
            'is_active' => true,
        ]);
    }

    private function human(string $role, Department $department): User
    {
        $user = User::query()->create([
            'name' => 'Correspondence '.$role.' '.Str::random(5),
            'email' => Str::lower(Str::random(10)).'@example.test',
            'password' => 'password',
            'role' => $role,
            'is_active' => true,
        ]);

        Employee::query()->create([
            'employee_number' => 'COR-EMP-'.Str::upper(Str::random(10)),
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
