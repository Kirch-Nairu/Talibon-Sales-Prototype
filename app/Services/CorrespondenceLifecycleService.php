<?php

namespace App\Services;

use App\Domain\Correspondence\CorrespondenceClassification;
use App\Domain\Correspondence\CorrespondenceLifecycleState;
use App\Models\CorrespondenceRecord;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CorrespondenceLifecycleService
{
    public function __construct(
        private readonly CorrespondenceAccessDecider $access,
        private readonly CorrespondenceReferenceNumberService $references,
        private readonly CorrespondenceEventRecorder $events,
        private readonly TransactionalOutbox $outbox,
        private readonly AuditLogger $audit,
    ) {
    }

    public function register(
        User $actor,
        CorrespondenceRecord $correspondence,
        string $correlationId,
    ): CorrespondenceRecord {
        return DB::transaction(function () use ($actor, $correspondence, $correlationId): CorrespondenceRecord {
            $locked = CorrespondenceRecord::query()->lockForUpdate()->findOrFail($correspondence->id);

            if ($locked->lifecycle_state !== CorrespondenceLifecycleState::Received) {
                throw ValidationException::withMessages([
                    'correspondence' => 'Only received correspondence can be registered.',
                ]);
            }

            if (! $this->access->canRegister($actor, $locked)) {
                throw new AuthorizationException('You are not authorized to register this correspondence.');
            }

            $actor->loadMissing('employee');
            $departmentId = (int) $actor->employee->department_id;
            $registeredAt = now();
            $reference = $this->references->next((int) $registeredAt->format('Y'));

            $locked->forceFill([
                'receiving_department_id' => $departmentId,
                'municipal_reference_no' => $reference,
                'registered_by_user_id' => $actor->id,
                'registered_at' => $registeredAt,
                'lifecycle_state' => CorrespondenceLifecycleState::Registered,
            ])->save();

            $this->events->record(
                $locked,
                'registered',
                CorrespondenceLifecycleState::Received,
                CorrespondenceLifecycleState::Registered,
                actor: $actor,
                officeDepartmentId: $departmentId,
                metadata: ['municipal_reference_no' => $reference],
                correlationId: $correlationId,
                occurredAt: $registeredAt,
            );

            $this->outbox->record(
                'correspondence.registered',
                'correspondence_record',
                $locked->public_id,
                [
                    'correspondence_public_id' => $locked->public_id,
                    'municipal_reference_no' => $reference,
                    'lifecycle_state' => CorrespondenceLifecycleState::Registered->value,
                    'receiving_department_id' => $departmentId,
                ],
                $registeredAt,
            );

            $this->audit->record(
                $actor,
                'correspondence.registered',
                'Human municipal actor registered received correspondence.',
                entityType: 'correspondence_record',
                entityId: $locked->id,
            );

            return $locked->fresh();
        });
    }

    public function classify(
        User $actor,
        CorrespondenceRecord $correspondence,
        CorrespondenceClassification $classification,
        string $correlationId,
        ?string $remarks = null,
    ): CorrespondenceRecord {
        return DB::transaction(function () use ($actor, $correspondence, $classification, $correlationId, $remarks): CorrespondenceRecord {
            $locked = CorrespondenceRecord::query()->lockForUpdate()->findOrFail($correspondence->id);

            if ($locked->lifecycle_state !== CorrespondenceLifecycleState::Registered) {
                throw ValidationException::withMessages([
                    'correspondence' => 'Only registered correspondence can be classified.',
                ]);
            }

            if (! $this->access->canClassify($actor, $locked)) {
                throw new AuthorizationException('You are not authorized to classify this correspondence.');
            }

            $actor->loadMissing('employee');
            $departmentId = (int) $actor->employee->department_id;
            $classifiedAt = now();

            $locked->forceFill([
                'classification' => $classification,
                'classified_by_user_id' => $actor->id,
                'classified_at' => $classifiedAt,
                'lifecycle_state' => CorrespondenceLifecycleState::Classified,
            ])->save();

            $this->events->record(
                $locked,
                'classified',
                CorrespondenceLifecycleState::Registered,
                CorrespondenceLifecycleState::Classified,
                actor: $actor,
                officeDepartmentId: $departmentId,
                remarks: $remarks,
                metadata: ['classification' => $classification->value],
                correlationId: $correlationId,
                occurredAt: $classifiedAt,
            );

            $this->outbox->record(
                'correspondence.classified',
                'correspondence_record',
                $locked->public_id,
                [
                    'correspondence_public_id' => $locked->public_id,
                    'lifecycle_state' => CorrespondenceLifecycleState::Classified->value,
                    'classification' => $classification->value,
                    'receiving_department_id' => $departmentId,
                ],
                $classifiedAt,
            );

            $this->audit->record(
                $actor,
                'correspondence.classified',
                'Human municipal actor classified registered correspondence.',
                entityType: 'correspondence_record',
                entityId: $locked->id,
            );

            return $locked->fresh();
        });
    }
}
