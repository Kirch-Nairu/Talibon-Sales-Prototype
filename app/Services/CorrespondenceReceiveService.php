<?php

namespace App\Services;

use App\Domain\Correspondence\CorrespondenceLifecycleState;
use App\Domain\Integration\IntegrationClientContext;
use App\Models\CorrespondenceRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CorrespondenceReceiveService
{
    public function __construct(
        private readonly CorrespondenceEventRecorder $events,
        private readonly TransactionalOutbox $outbox,
        private readonly AuditLogger $audit,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function receive(
        IntegrationClientContext $context,
        array $data,
        string $correlationId,
    ): CorrespondenceRecord {
        return DB::transaction(function () use ($context, $data, $correlationId): CorrespondenceRecord {
            $publicId = (string) Str::uuid();
            $receivedAt = now();
            $externalReference = sprintf(
                'TAL-EXT-%s-%s',
                $receivedAt->format('Y'),
                strtoupper(substr(str_replace('-', '', $publicId), 0, 12)),
            );

            $record = CorrespondenceRecord::query()->create([
                'public_id' => $publicId,
                'external_reference_no' => $externalReference,
                'source' => $data['source'],
                'channel' => $data['channel'] ?? null,
                'sender_name' => $data['sender_name'],
                'sender_organization' => $data['sender_organization'] ?? null,
                'sender_contact' => $data['sender_contact'] ?? null,
                'subject' => $data['subject'],
                'summary' => $data['summary'] ?? null,
                'received_at' => $receivedAt,
                'received_source_identity' => $context->credential->public_id,
                'receiving_integration_client_id' => $context->client->id,
                'receiving_integration_client_credential_id' => $context->credential->id,
                'originating_external_reference' => $data['originating_external_reference'] ?? null,
                'lifecycle_state' => CorrespondenceLifecycleState::Received,
            ]);

            $this->events->record(
                $record,
                'received',
                null,
                CorrespondenceLifecycleState::Received,
                integrationClient: $context->client,
                metadata: [
                    'source' => $record->source,
                    'channel' => $record->channel,
                    'credential_public_id' => $context->credential->public_id,
                ],
                correlationId: $correlationId,
                occurredAt: $receivedAt,
            );

            $this->outbox->record(
                'correspondence.received',
                'correspondence_record',
                $record->public_id,
                [
                    'correspondence_public_id' => $record->public_id,
                    'external_reference' => $record->external_reference_no,
                    'lifecycle_state' => CorrespondenceLifecycleState::Received->value,
                    'integration_client_public_id' => $context->client->public_id,
                ],
                $receivedAt,
            );

            $this->audit->recordIntegration(
                $context->client,
                'correspondence.received',
                'Integration client received correspondence into the authoritative correspondence register.',
                entityType: 'correspondence_record',
                entityId: $record->id,
            );

            return $record->fresh();
        });
    }
}
