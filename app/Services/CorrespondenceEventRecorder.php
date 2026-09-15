<?php

namespace App\Services;

use App\Domain\Correspondence\CorrespondenceLifecycleState;
use App\Models\CorrespondenceEvent;
use App\Models\CorrespondenceRecord;
use App\Models\IntegrationClient;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use LogicException;

class CorrespondenceEventRecorder
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function record(
        CorrespondenceRecord $correspondence,
        string $event,
        ?CorrespondenceLifecycleState $previousState,
        CorrespondenceLifecycleState $newState,
        ?User $actor = null,
        ?IntegrationClient $integrationClient = null,
        ?int $officeDepartmentId = null,
        ?string $remarks = null,
        ?array $metadata = null,
        ?string $correlationId = null,
        ?CarbonInterface $occurredAt = null,
    ): CorrespondenceEvent {
        if (DB::connection()->transactionLevel() < 1) {
            throw new LogicException('Correspondence history must be recorded inside the authoritative transaction.');
        }

        $occurred = ($occurredAt ?? now())->copy()->setTimezone(config('app.timezone'));

        return CorrespondenceEvent::query()->create([
            'correspondence_record_id' => $correspondence->id,
            'event' => $event,
            'previous_lifecycle_state' => $previousState,
            'new_lifecycle_state' => $newState,
            'actor_user_id' => $actor?->id,
            'integration_client_actor_id' => $integrationClient?->id,
            'office_department_id' => $officeDepartmentId,
            'remarks' => $remarks,
            'metadata' => $metadata,
            'correlation_id' => $correlationId,
            'occurred_at' => $occurred,
        ]);
    }
}
