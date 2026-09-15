<?php

namespace Tests\Feature;

use App\Domain\Integration\IntegrationErrorCode;
use App\Domain\Integration\IntegrationIdempotencyDecisionType;
use App\Domain\Integration\IntegrationIdempotencyStatus;
use App\Domain\Integration\IntegrationOperation;
use App\Domain\Integration\IntegrationScope;
use App\Domain\Outbox\OutboxMessageStatus;
use App\Models\AuditLog;
use App\Models\IntegrationClient;
use App\Models\IntegrationClientCredential;
use App\Models\IntegrationIdempotencyRecord;
use App\Models\IntegrationProofWrite;
use App\Models\OutboxMessage;
use App\Services\IntegrationClientService;
use App\Services\IntegrationCredentialService;
use App\Services\IntegrationIdempotencyService;
use App\Services\IntegrationProofWriteService;
use App\Services\IntegrationRequestFingerprint;
use App\Services\OutboxDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

class IntegrationFoundationBTest extends TestCase
{
    use RefreshDatabase;

    public function test_idempotency_key_is_required_only_for_explicitly_marked_write_route(): void
    {
        [, , $token] = $this->credential([
            IntegrationScope::SelfRead->value,
            IntegrationScope::ProofWrite->value,
        ]);

        $this->withToken($token)->getJson('/api/v1/integration/self')->assertOk();
        $this->withToken($token)
            ->postJson('/api/v1/integration/proof-writes', ['value' => 'proof'])
            ->assertStatus(422)
            ->assertJsonPath('error.code', IntegrationErrorCode::IdempotencyKeyRequired->value);
    }

    public function test_same_idempotency_key_and_payload_executes_only_once(): void
    {
        [, , $token] = $this->credential([IntegrationScope::ProofWrite->value]);
        $key = 'proof-'.Str::uuid();

        $this->write($token, $key, 'once')->assertCreated();
        $this->write($token, $key, 'once')->assertCreated();

        $this->assertDatabaseCount('integration_proof_writes', 1);
        $this->assertDatabaseCount('outbox_messages', 1);
        $this->assertDatabaseCount('integration_idempotency_records', 1);
    }

    public function test_replay_returns_same_logical_result_with_new_correlation_identity(): void
    {
        [, , $token] = $this->credential([IntegrationScope::ProofWrite->value]);
        $key = 'replay-'.Str::uuid();

        $original = $this->write($token, $key, 'replayable');
        $replay = $this->write($token, $key, 'replayable');

        $original->assertCreated();
        $replay->assertCreated()
            ->assertHeader('Idempotency-Replayed', 'true');
        $this->assertSame($original->json('proof'), $replay->json('proof'));
        $this->assertNotSame($original->json('correlation_id'), $replay->json('correlation_id'));
        $this->assertNotSame(
            $original->headers->get('X-Correlation-ID'),
            $replay->headers->get('X-Correlation-ID'),
        );
    }

    public function test_same_key_with_different_payload_returns_stable_conflict_without_second_execution(): void
    {
        [, , $token] = $this->credential([IntegrationScope::ProofWrite->value]);
        $key = 'conflict-'.Str::uuid();

        $this->write($token, $key, 'first')->assertCreated();
        $this->write($token, $key, 'different')
            ->assertStatus(409)
            ->assertJsonPath('error.code', IntegrationErrorCode::IdempotencyConflict->value);

        $this->assertDatabaseCount('integration_proof_writes', 1);
        $this->assertDatabaseCount('outbox_messages', 1);
    }

    public function test_persisted_processing_claim_rejects_concurrent_duplicate_without_double_execution(): void
    {
        [, , $token] = $this->credential([IntegrationScope::ProofWrite->value]);
        $context = app(IntegrationCredentialService::class)->authenticate($token);
        $key = 'concurrent-'.Str::uuid();
        $fingerprint = app(IntegrationRequestFingerprint::class)->hashPayload('POST', ['value' => 'hold']);

        $first = app(IntegrationIdempotencyService::class)->begin(
            $context,
            IntegrationOperation::ProofWrite->value,
            $key,
            $fingerprint,
        );
        $second = app(IntegrationIdempotencyService::class)->begin(
            $context,
            IntegrationOperation::ProofWrite->value,
            $key,
            $fingerprint,
        );

        $this->assertSame(IntegrationIdempotencyDecisionType::Execute, $first->type);
        $this->assertSame(IntegrationIdempotencyDecisionType::InProgress, $second->type);
        $this->assertDatabaseCount('integration_idempotency_records', 1);

        $this->write($token, $key, 'hold')
            ->assertStatus(409)
            ->assertJsonPath('error.code', IntegrationErrorCode::IdempotencyInProgress->value)
            ->assertHeader('Retry-After', '1');
        $this->assertDatabaseCount('integration_proof_writes', 0);
        $this->assertDatabaseCount('outbox_messages', 0);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'integration.idempotency.in_progress',
            'outcome' => 'denied',
        ]);
    }

    public function test_idempotency_scope_is_isolated_between_clients(): void
    {
        [, , $tokenA] = $this->credential([IntegrationScope::ProofWrite->value]);
        [, , $tokenB] = $this->credential([IntegrationScope::ProofWrite->value]);
        $key = 'client-scope-'.Str::uuid();

        $this->write($tokenA, $key, 'same')->assertCreated();
        $this->write($tokenB, $key, 'same')->assertCreated();

        $this->assertDatabaseCount('integration_proof_writes', 2);
        $this->assertDatabaseCount('integration_idempotency_records', 2);
    }

    public function test_idempotency_scope_is_isolated_between_credentials_of_same_client(): void
    {
        [$client, , $tokenA] = $this->credential([IntegrationScope::ProofWrite->value]);
        $issued = app(IntegrationCredentialService::class)->issue($client, [IntegrationScope::ProofWrite->value]);
        $tokenB = $issued->plainTextToken;
        $key = 'credential-scope-'.Str::uuid();

        $this->write($tokenA, $key, 'same')->assertCreated();
        $this->write($tokenB, $key, 'same')->assertCreated();

        $this->assertDatabaseCount('integration_proof_writes', 2);
        $this->assertDatabaseCount('integration_idempotency_records', 2);
    }

    public function test_idempotency_scope_is_isolated_between_operations(): void
    {
        [, , $token] = $this->credential([IntegrationScope::ProofWrite->value]);
        $context = app(IntegrationCredentialService::class)->authenticate($token);
        $key = 'operation-scope-'.Str::uuid();
        $fingerprint = app(IntegrationRequestFingerprint::class)->hashPayload('POST', ['value' => 'same']);
        $service = app(IntegrationIdempotencyService::class);

        $first = $service->begin($context, IntegrationOperation::ProofWrite->value, $key, $fingerprint);
        $second = $service->begin($context, 'integration.proof.write.alternate', $key, $fingerprint);

        $this->assertSame(IntegrationIdempotencyDecisionType::Execute, $first->type);
        $this->assertSame(IntegrationIdempotencyDecisionType::Execute, $second->type);
        $this->assertDatabaseCount('integration_idempotency_records', 2);
    }

    public function test_failed_execution_is_marked_failed_without_completed_response_snapshot(): void
    {
        [, , $token] = $this->credential([IntegrationScope::ProofWrite->value]);
        $key = 'failure-'.Str::uuid();
        $this->mock(IntegrationProofWriteService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('execute')->once()->andThrow(new RuntimeException('forced proof failure'));
        });

        $this->write($token, $key, 'will-fail')->assertStatus(500);

        $record = IntegrationIdempotencyRecord::query()->sole();
        $this->assertSame(IntegrationIdempotencyStatus::Failed, $record->status);
        $this->assertNull($record->response_status);
        $this->assertNull($record->response_body);
        $this->assertNotNull($record->failed_at);
        $this->assertDatabaseCount('integration_proof_writes', 0);
        $this->assertDatabaseCount('outbox_messages', 0);
    }

    public function test_idempotency_storage_contains_hashes_and_safe_response_but_no_bearer_secret(): void
    {
        [, , $token] = $this->credential([IntegrationScope::ProofWrite->value]);
        [, $secret] = explode('.', $token, 2);
        $key = 'storage-'.Str::uuid();

        $this->write($token, $key, 'safe')->assertCreated();
        $raw = DB::table('integration_idempotency_records')->first();
        $this->assertNotNull($raw);
        $serialized = json_encode($raw, JSON_THROW_ON_ERROR);

        $this->assertSame(hash('sha256', $key), $raw->idempotency_key_hash);
        $this->assertSame(64, strlen($raw->request_fingerprint));
        $this->assertStringNotContainsString($key, $serialized);
        $this->assertStringNotContainsString($token, $serialized);
        $this->assertStringNotContainsString($secret, $serialized);
        $this->assertStringNotContainsString('Bearer ', $serialized);
    }

    public function test_successful_proof_write_creates_outbox_message_in_authoritative_action(): void
    {
        [$client, , $token] = $this->credential([IntegrationScope::ProofWrite->value]);

        $response = $this->write($token, 'outbox-'.Str::uuid(), 'atomic');
        $response->assertCreated();
        $proof = IntegrationProofWrite::query()->sole();
        $outbox = OutboxMessage::query()->sole();

        $this->assertSame('integration.proof_write.created', $outbox->event_type);
        $this->assertSame('integration_proof_write', $outbox->aggregate_type);
        $this->assertSame($proof->public_id, $outbox->aggregate_id);
        $this->assertSame($proof->public_id, $outbox->payload['proof_public_id']);
        $this->assertSame($client->public_id, $outbox->payload['client_public_id']);
        $this->assertSame(OutboxMessageStatus::Pending, $outbox->status);
        $this->assertSame(0, $outbox->attempt_count);
    }

    public function test_rollback_removes_both_proof_mutation_and_outbox_record(): void
    {
        [, , $token] = $this->credential([IntegrationScope::ProofWrite->value]);
        $context = app(IntegrationCredentialService::class)->authenticate($token);

        try {
            DB::transaction(function () use ($context): void {
                app(IntegrationProofWriteService::class)->execute(
                    $context,
                    IntegrationOperation::ProofWrite->value,
                    'rollback',
                );
                throw new RuntimeException('force authoritative transaction rollback');
            });
        } catch (RuntimeException) {
        }

        $this->assertDatabaseCount('integration_proof_writes', 0);
        $this->assertDatabaseCount('outbox_messages', 0);
    }

    public function test_replay_does_not_create_duplicate_outbox_event(): void
    {
        [, , $token] = $this->credential([IntegrationScope::ProofWrite->value]);
        $key = 'outbox-replay-'.Str::uuid();

        $this->write($token, $key, 'single-event')->assertCreated();
        $this->write($token, $key, 'single-event')->assertCreated();

        $this->assertDatabaseCount('integration_proof_writes', 1);
        $this->assertDatabaseCount('outbox_messages', 1);
    }

    public function test_audit_records_distinguish_execution_replay_conflict_and_concurrent_rejection(): void
    {
        [$client, , $token] = $this->credential([IntegrationScope::ProofWrite->value]);
        $key = 'audit-'.Str::uuid();

        $original = $this->write($token, $key, 'audit');
        $replay = $this->write($token, $key, 'audit');
        $conflict = $this->write($token, $key, 'different');

        $this->assertTrue(AuditLog::query()->where('integration_client_id', $client->id)
            ->where('action', 'integration.idempotency.execution.started')->exists());
        $this->assertTrue(AuditLog::query()->where('integration_client_id', $client->id)
            ->where('action', 'integration.idempotency.execution.completed')->exists());
        $this->assertTrue(AuditLog::query()->where('integration_client_id', $client->id)
            ->where('action', 'integration.idempotency.replayed')->exists());
        $this->assertTrue(AuditLog::query()->where('integration_client_id', $client->id)
            ->where('action', 'integration.idempotency.conflict')->exists());

        $this->assertNotSame($original->json('correlation_id'), $replay->json('correlation_id'));
        $this->assertNotSame($replay->json('correlation_id'), $conflict->json('correlation_id'));
    }

    public function test_outbox_dispatcher_claims_with_row_locks_without_external_publication(): void
    {
        [, , $token] = $this->credential([IntegrationScope::ProofWrite->value]);
        $this->write($token, 'claim-'.Str::uuid(), 'claimable')->assertCreated();

        $claimed = app(OutboxDispatcher::class)->claimPending('foundation-b-test-worker', 10);

        $this->assertCount(1, $claimed);
        $message = $claimed->firstOrFail()->fresh();
        $this->assertSame(OutboxMessageStatus::Claimed, $message->status);
        $this->assertSame('foundation-b-test-worker', $message->claimed_by);
        $this->assertSame(1, $message->attempt_count);
        $this->assertNull($message->last_error);

        $released = app(OutboxDispatcher::class)
            ->releaseClaim($message, 'foundation-b-test-worker', 'transport intentionally unavailable');
        $this->assertSame(OutboxMessageStatus::Pending, $released->status);
        $this->assertNull($released->claimed_by);
        $this->assertSame('transport intentionally unavailable', $released->last_error);
    }

    /**
     * @param  array<int, string>  $scopes
     * @return array{0: IntegrationClient, 1: IntegrationClientCredential, 2: string}
     */
    private function credential(array $scopes): array
    {
        $client = app(IntegrationClientService::class)->create(
            'Foundation B Client '.Str::uuid(),
            500,
        );
        $issued = app(IntegrationCredentialService::class)->issue($client, $scopes);
        $credential = IntegrationClientCredential::query()
            ->where('public_id', $issued->publicId)
            ->firstOrFail();

        return [$client, $credential, $issued->plainTextToken];
    }

    private function write(string $token, string $idempotencyKey, string $value)
    {
        return $this->withHeader('Idempotency-Key', $idempotencyKey)
            ->withToken($token)
            ->postJson('/api/v1/integration/proof-writes', ['value' => $value]);
    }
}
