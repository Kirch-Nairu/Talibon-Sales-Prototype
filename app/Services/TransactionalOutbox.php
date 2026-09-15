<?php

namespace App\Services;

use App\Domain\Outbox\OutboxMessageStatus;
use App\Models\OutboxMessage;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;

class TransactionalOutbox
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function record(
        string $eventType,
        string $aggregateType,
        string $aggregateId,
        array $payload,
        ?CarbonInterface $occurredAt = null,
    ): OutboxMessage {
        if (DB::connection()->transactionLevel() < 1) {
            throw new LogicException('Outbox records must be created inside the authoritative database transaction.');
        }

        $occurred = ($occurredAt ?? now())->copy()->setTimezone(config('app.timezone'));

        return OutboxMessage::query()->create([
            'public_id' => (string) Str::uuid(),
            'event_type' => $eventType,
            'aggregate_type' => $aggregateType,
            'aggregate_id' => $aggregateId,
            'payload' => $payload,
            'occurred_at' => $occurred,
            'status' => OutboxMessageStatus::Pending,
            'available_at' => $occurred,
            'attempt_count' => 0,
        ]);
    }
}
