<?php

namespace App\Services;

use App\Models\CorrespondenceEvent;
use App\Models\CorrespondenceRecord;
use App\Models\Department;
use App\Models\TransactionEvent;
use App\Models\WorkflowTransaction;
use Illuminate\Support\Collection;

final class CorrespondenceTraceQuery
{
    public function __construct(private readonly DocumentEvidenceQuery $evidence)
    {
    }

    /** @return array{timeline:array<int,array<string,mixed>>,evidence:array{record:array<int,array<string,mixed>>,events:array<string,array<int,array<string,mixed>>>}} */
    public function forRecord(CorrespondenceRecord $record): array
    {
        $correspondenceEvents = $record->events()
            ->with([
                'actorUser:id,name',
                'integrationClientActor:id,name',
                'officeDepartment:id,code,name,short_name',
            ])
            ->orderBy('occurred_at')
            ->orderBy('id')
            ->get();

        $correspondenceEvidence = $this->evidence->forCorrespondence($record);
        $workflow = $record->workflowTransaction;
        $workflowEvents = $this->workflowEvents($workflow);
        $workflowEvidence = $workflow instanceof WorkflowTransaction
            ? $this->evidence->forTransaction($workflow)
            : ['record' => [], 'events' => []];

        $routeTargets = $this->routeTargets($correspondenceEvents);
        $submitted = $workflowEvents->firstWhere('action', 'submitted');
        $hasCorrespondenceRoute = $correspondenceEvents->contains(
            fn (CorrespondenceEvent $event): bool => $event->event === 'routed',
        );

        $timeline = [];
        foreach ($correspondenceEvents as $event) {
            $targetId = $event->event === 'routed'
                ? (int) ($event->metadata['target_department_id'] ?? 0)
                : 0;
            $fallbackFrom = $event->event === 'routed' ? $submitted?->fromDepartment : null;
            $fallbackTo = $event->event === 'routed' ? $submitted?->toDepartment : null;
            $eventEvidence = $correspondenceEvidence['events'][(string) $event->id] ?? [];

            if ($event->event === 'routed' && $submitted instanceof TransactionEvent) {
                $eventEvidence = array_merge(
                    $eventEvidence,
                    $workflowEvidence['events'][(string) $submitted->id] ?? [],
                );
            }

            $timeline[] = [
                'id' => 'correspondence:'.$event->id,
                'source' => 'correspondence',
                'event' => $event->event,
                'previousState' => $event->previous_lifecycle_state?->value,
                'newState' => $event->new_lifecycle_state?->value,
                'actor' => $event->actorUser
                    ? ['type' => 'human', 'name' => $event->actorUser->name]
                    : ($event->integrationClientActor
                        ? ['type' => 'integration', 'name' => $event->integrationClientActor->name]
                        : null),
                'office' => $this->office($event->officeDepartment),
                'fromOffice' => $event->event === 'routed'
                    ? $this->office($event->officeDepartment ?? $fallbackFrom)
                    : null,
                'toOffice' => $event->event === 'routed'
                    ? $this->office($routeTargets->get($targetId) ?? $fallbackTo)
                    : null,
                'remarks' => $event->remarks,
                'occurredAt' => $event->occurred_at?->toISOString(),
                'evidence' => $eventEvidence,
                '_sortSequence' => (int) $event->id,
                '_sortRank' => $event->event === 'in_action' ? 2 : 0,
            ];
        }

        foreach ($workflowEvents as $event) {
            if ($hasCorrespondenceRoute && $event->action === 'submitted') {
                continue;
            }

            $timeline[] = [
                'id' => 'workflow:'.$event->id,
                'source' => 'workflow',
                'event' => $event->action,
                'previousState' => $event->previous_status,
                'newState' => $event->new_status,
                'actor' => $event->actor
                    ? ['type' => 'human', 'name' => $event->actor->name]
                    : null,
                'office' => $this->office($event->toDepartment),
                'fromOffice' => $this->office($event->fromDepartment),
                'toOffice' => $this->office($event->toDepartment),
                'remarks' => $event->remarks,
                'occurredAt' => $event->created_at?->toISOString(),
                'evidence' => $workflowEvidence['events'][(string) $event->id] ?? [],
                '_sortSequence' => (int) $event->id,
                '_sortRank' => 1,
            ];
        }

        usort($timeline, function (array $left, array $right): int {
            $time = strcmp((string) ($left['occurredAt'] ?? ''), (string) ($right['occurredAt'] ?? ''));
            if ($time !== 0) {
                return $time;
            }

            $rank = ((int) $left['_sortRank']) <=> ((int) $right['_sortRank']);
            if ($rank !== 0) {
                return $rank;
            }

            if ($left['source'] === $right['source']) {
                return ((int) $left['_sortSequence']) <=> ((int) $right['_sortSequence']);
            }

            return strcmp((string) $left['source'], (string) $right['source']);
        });

        $timeline = array_map(function (array $entry): array {
            unset($entry['_sortSequence'], $entry['_sortRank']);

            return $entry;
        }, $timeline);

        return [
            'timeline' => $timeline,
            'evidence' => $correspondenceEvidence,
        ];
    }

    /** @return Collection<int, TransactionEvent> */
    private function workflowEvents(?WorkflowTransaction $workflow): Collection
    {
        if (! $workflow instanceof WorkflowTransaction) {
            return collect();
        }

        $events = TransactionEvent::query()
            ->where('transaction_id', $workflow->id)
            ->with([
                'actor:id,name',
                'fromDepartment:id,code,name,short_name',
                'toDepartment:id,code,name,short_name',
            ])
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $workflow->setRelation('events', $events);

        return $events;
    }

    /** @param Collection<int, CorrespondenceEvent> $events @return Collection<int, Department> */
    private function routeTargets(Collection $events): Collection
    {
        $ids = $events
            ->filter(fn (CorrespondenceEvent $event): bool => $event->event === 'routed')
            ->map(fn (CorrespondenceEvent $event): int => (int) ($event->metadata['target_department_id'] ?? 0))
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return Department::query()
            ->whereKey($ids->all())
            ->get(['id', 'code', 'name', 'short_name'])
            ->keyBy('id');
    }

    /** @return array{code:string,name:string,shortName:?string}|null */
    private function office(?Department $department): ?array
    {
        if (! $department) {
            return null;
        }

        return [
            'code' => $department->code,
            'name' => $department->name,
            'shortName' => $department->short_name,
        ];
    }
}
