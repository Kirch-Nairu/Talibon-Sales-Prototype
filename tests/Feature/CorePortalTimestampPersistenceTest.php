<?php

namespace Tests\Feature;

use App\Domain\Correspondence\CorrespondenceClassification;
use App\Domain\Integration\IntegrationClientContext;
use App\Domain\Integration\IntegrationScope;
use App\Models\CorrespondenceEvent;
use App\Models\Department;
use App\Models\Employee;
use App\Models\IntegrationClientCredential;
use App\Models\IntegrationIdempotencyRecord;
use App\Models\OutboxMessage;
use App\Models\User;
use App\Services\CorrespondenceLifecycleService;
use App\Services\CorrespondenceReceiveService;
use App\Services\CorrespondenceRoutingService;
use App\Services\CorrespondenceTraceQuery;
use App\Services\IntegrationClientService;
use App\Services\IntegrationCredentialService;
use App\Services\IntegrationIdempotencyService;
use App\Services\OutboxDispatcher;
use App\Services\TransactionalOutbox;
use App\Services\TransactionWorkflowService;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class CorePortalTimestampPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_application_and_postgres_timezones_are_aligned(): void
    {
        $applicationTimezone = config('app.timezone');

        $this->assertSame($applicationTimezone, config('database.connections.pgsql.timezone'));
        $this->assertSame(
            $applicationTimezone,
            DB::selectOne("select current_setting('TIMEZONE') as timezone")->timezone,
        );
    }

    public function test_correspondence_lifecycle_persists_authoritative_instants_and_aligns_events_and_outbox(): void
    {
        try {
            $receivingOffice = $this->department('TIME-RECEIVE');
            $targetOffice = $this->department('TIME-TARGET');
            $receivingHead = $this->human('department_head', $receivingOffice);
            $targetHead = $this->human('department_head', $targetOffice);

            $receivedAt = $this->freeze('08:00:00');
            $context = $this->integrationContext(IntegrationScope::CorrespondenceReceive);
            $record = app(CorrespondenceReceiveService::class)->receive(
                $context,
                [
                    'source' => 'partner_system',
                    'channel' => 'api',
                    'sender_name' => 'Timestamp Acceptance Sender',
                    'subject' => 'Timestamp persistence acceptance',
                ],
                (string) Str::uuid(),
            );

            $registeredAt = $this->freeze('08:05:00');
            $record = app(CorrespondenceLifecycleService::class)->register(
                $receivingHead,
                $record,
                (string) Str::uuid(),
            );

            $classifiedAt = $this->freeze('08:07:00');
            $record = app(CorrespondenceLifecycleService::class)->classify(
                $receivingHead,
                $record,
                CorrespondenceClassification::Internal,
                (string) Str::uuid(),
                'Timestamp classification.',
            );

            $routedAt = $this->freeze('08:10:00');
            $record = app(CorrespondenceRoutingService::class)->route(
                $receivingHead,
                $record,
                [
                    'target_department_id' => $targetOffice->id,
                    'priority' => 'normal',
                    'remarks' => 'Timestamp route.',
                ],
                (string) Str::uuid(),
            );

            $this->freeze('08:15:00');
            $workflow = $record->workflowTransaction;
            $this->assertNotNull($workflow);
            app(TransactionWorkflowService::class)->transition(
                $targetHead,
                $workflow,
                'assign',
                assignedEmployeeId: $targetHead->employee->id,
                remarks: 'Timestamp assignment.',
            );

            $actedAt = $this->freeze('08:20:00');
            $record = app(CorrespondenceRoutingService::class)->markInAction(
                $targetHead,
                $record->fresh(),
                (string) Str::uuid(),
                'Timestamp action.',
            )->fresh();

            $this->assertDatabaseEpoch('correspondence_records', $record->id, 'received_at', $receivedAt);
            $this->assertDatabaseEpoch('correspondence_records', $record->id, 'registered_at', $registeredAt);
            $this->assertDatabaseEpoch('correspondence_records', $record->id, 'classified_at', $classifiedAt);
            $this->assertDatabaseEpoch('correspondence_records', $record->id, 'routed_at', $routedAt);
            $this->assertDatabaseEpoch('correspondence_records', $record->id, 'action_started_at', $actedAt);
            $referenceYear = (int) $registeredAt->format('Y');
            $this->assertDatabaseEpoch('correspondence_reference_counters', $referenceYear, 'created_at', $registeredAt, 'year');
            $this->assertDatabaseEpoch('correspondence_reference_counters', $referenceYear, 'updated_at', $registeredAt, 'year');

            $expected = [
                'received' => $receivedAt,
                'registered' => $registeredAt,
                'classified' => $classifiedAt,
                'routed' => $routedAt,
                'in_action' => $actedAt,
            ];
            $events = CorrespondenceEvent::query()
                ->where('correspondence_record_id', $record->id)
                ->get()
                ->keyBy('event');

            foreach ($expected as $eventName => $instant) {
                $event = $events->get($eventName);
                $this->assertNotNull($event, 'Missing '.$eventName.' correspondence event.');
                $this->assertSame($instant->getTimestamp(), $event->occurred_at->getTimestamp());
                $this->assertDatabaseEpoch('correspondence_events', $event->id, 'occurred_at', $instant);

                $outbox = OutboxMessage::query()
                    ->where('aggregate_type', 'correspondence_record')
                    ->where('aggregate_id', $record->public_id)
                    ->where('event_type', 'correspondence.'.$eventName)
                    ->sole();
                $this->assertDatabaseEpoch('outbox_messages', $outbox->id, 'occurred_at', $instant);
                $this->assertDatabaseEpoch('outbox_messages', $outbox->id, 'available_at', $instant);
            }

            $epochs = array_map(fn (CarbonInterface $instant): int => $instant->getTimestamp(), array_values($expected));
            for ($index = 1; $index < count($epochs); $index++) {
                $this->assertGreaterThan($epochs[$index - 1], $epochs[$index]);
            }
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_same_second_trace_order_preserves_route_then_workflow_then_in_action_causality(): void
    {
        try {
            $receivingOffice = $this->department('TIE-RECEIVE');
            $targetOffice = $this->department('TIE-TARGET');
            $receivingHead = $this->human('department_head', $receivingOffice);
            $targetHead = $this->human('department_head', $targetOffice);

            $this->freeze('09:00:00');
            $record = app(CorrespondenceReceiveService::class)->receive(
                $this->integrationContext(IntegrationScope::CorrespondenceReceive),
                [
                    'source' => 'partner_system',
                    'channel' => 'api',
                    'sender_name' => 'Same Second Sender',
                    'subject' => 'Same second trace ordering',
                ],
                (string) Str::uuid(),
            );

            $this->freeze('09:05:00');
            $record = app(CorrespondenceLifecycleService::class)->register($receivingHead, $record, (string) Str::uuid());
            $this->freeze('09:07:00');
            $record = app(CorrespondenceLifecycleService::class)->classify(
                $receivingHead,
                $record,
                CorrespondenceClassification::Internal,
                (string) Str::uuid(),
            );

            $sameSecond = $this->freeze('09:10:00');
            $record = app(CorrespondenceRoutingService::class)->route(
                $receivingHead,
                $record,
                ['target_department_id' => $targetOffice->id, 'priority' => 'normal'],
                (string) Str::uuid(),
            );
            $workflow = $record->workflowTransaction;
            $this->assertNotNull($workflow);
            app(TransactionWorkflowService::class)->transition(
                $targetHead,
                $workflow,
                'assign',
                assignedEmployeeId: $targetHead->employee->id,
            );
            $record = app(CorrespondenceRoutingService::class)->markInAction(
                $targetHead,
                $record->fresh(),
                (string) Str::uuid(),
            )->fresh('workflowTransaction');

            $timeline = app(CorrespondenceTraceQuery::class)->forRecord($record)['timeline'];
            $this->assertSame(
                ['received', 'registered', 'classified', 'routed', 'assign', 'in_action'],
                array_column($timeline, 'event'),
            );
            $this->assertSame($sameSecond->toISOString(), $timeline[3]['occurredAt']);
            $this->assertSame($sameSecond->toISOString(), $timeline[4]['occurredAt']);
            $this->assertSame($sameSecond->toISOString(), $timeline[5]['occurredAt']);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_active_foundation_persistence_preserves_credential_idempotency_and_outbox_instants(): void
    {
        try {
            $issuedAt = $this->freeze('10:00:00');
            $client = app(IntegrationClientService::class)->create('Timestamp foundation '.Str::uuid());
            $expiresAt = Carbon::parse('2026-08-25 12:00:00', config('app.timezone'));
            $issued = app(IntegrationCredentialService::class)->issue(
                $client,
                [IntegrationScope::SelfRead->value],
                $expiresAt,
            );
            $credential = IntegrationClientCredential::query()->where('public_id', $issued->publicId)->sole();
            $this->assertDatabaseEpoch('integration_client_credentials', $credential->id, 'issued_at', $issuedAt);
            $this->assertDatabaseEpoch('integration_client_credentials', $credential->id, 'expires_at', $expiresAt);

            $usedAt = $this->freeze('10:05:00');
            $context = app(IntegrationCredentialService::class)->authenticate($issued->plainTextToken);
            $credential = $credential->fresh();
            $this->assertDatabaseEpoch('integration_client_credentials', $credential->id, 'last_used_at', $usedAt);

            $startedAt = $this->freeze('10:10:00');
            $decision = app(IntegrationIdempotencyService::class)->begin(
                $context,
                'timestamp.persistence.test',
                (string) Str::uuid(),
                hash('sha256', 'timestamp-payload'),
            );
            $record = $decision->record;
            $this->assertInstanceOf(IntegrationIdempotencyRecord::class, $record);
            $this->assertNotNull($decision->processingToken);
            $this->assertDatabaseEpoch('integration_idempotency_records', $record->id, 'started_at', $startedAt);

            $completedAt = $this->freeze('10:12:00');
            DB::transaction(function () use ($record, $decision): void {
                app(IntegrationIdempotencyService::class)->complete(
                    $record,
                    $decision->processingToken,
                    200,
                    ['ok' => true],
                );
            });
            $this->assertDatabaseEpoch('integration_idempotency_records', $record->id, 'completed_at', $completedAt);

            $outboxAt = $this->freeze('10:20:00');
            $outbox = DB::transaction(fn (): OutboxMessage => app(TransactionalOutbox::class)->record(
                'timestamp.foundation.test',
                'timestamp_test',
                (string) Str::uuid(),
                ['ok' => true],
                $outboxAt,
            ));
            $this->assertDatabaseEpoch('outbox_messages', $outbox->id, 'occurred_at', $outboxAt);
            $this->assertDatabaseEpoch('outbox_messages', $outbox->id, 'available_at', $outboxAt);

            $claimedAt = $this->freeze('10:21:00');
            $claimed = app(OutboxDispatcher::class)->claimPending('timestamp-worker', 1);
            $this->assertCount(1, $claimed);
            $this->assertSame($outbox->id, $claimed->first()->id);
            $this->assertDatabaseEpoch('outbox_messages', $outbox->id, 'claimed_at', $claimedAt);

            $releasedAt = $this->freeze('10:22:00');
            app(OutboxDispatcher::class)->releaseClaim($outbox->fresh(), 'timestamp-worker', 'retry');
            $this->assertDatabaseEpoch('outbox_messages', $outbox->id, 'available_at', $releasedAt);

            $revokedAt = $this->freeze('10:30:00');
            app(IntegrationCredentialService::class)->revoke($credential);
            $this->assertDatabaseEpoch('integration_client_credentials', $credential->id, 'revoked_at', $revokedAt);
        } finally {
            Carbon::setTestNow();
        }
    }

    private function freeze(string $time): CarbonInterface
    {
        Carbon::setTestNow(Carbon::parse('2026-08-25 '.$time, config('app.timezone')));

        return now()->copy();
    }

    private function integrationContext(IntegrationScope $scope): IntegrationClientContext
    {
        $client = app(IntegrationClientService::class)->create('Timestamp correspondence '.Str::uuid());
        $issued = app(IntegrationCredentialService::class)->issue($client, [$scope->value]);

        return app(IntegrationCredentialService::class)->authenticate($issued->plainTextToken);
    }

    private function department(string $suffix): Department
    {
        return Department::query()->create([
            'code' => 'TIME-'.Str::upper(Str::random(5)).'-'.$suffix,
            'name' => 'Timestamp '.$suffix,
            'short_name' => 'TM-'.$suffix,
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
            'name' => 'Timestamp '.$role.' '.Str::random(5),
            'email' => Str::lower(Str::random(10)).'@example.test',
            'password' => 'password',
            'role' => $role,
            'is_active' => true,
        ]);

        Employee::query()->create([
            'employee_number' => 'TIME-EMP-'.Str::upper(Str::random(10)),
            'full_name' => $user->name,
            'work_email' => $user->email,
            'user_id' => $user->id,
            'department_id' => $department->id,
            'position_title' => 'Timestamp Test Officer',
            'employment_status' => 'active',
        ]);

        return $user->fresh('employee.department');
    }

    private function assertDatabaseEpoch(
        string $table,
        int $id,
        string $column,
        CarbonInterface $expected,
        string $keyColumn = 'id',
    ): void
    {
        $epoch = (int) DB::table($table)
            ->where($keyColumn, $id)
            ->selectRaw(sprintf('extract(epoch from %s)::bigint as epoch', $column))
            ->value('epoch');

        $this->assertSame($expected->getTimestamp(), $epoch, $table.'.'.$column.' did not preserve the intended instant.');
    }
}
