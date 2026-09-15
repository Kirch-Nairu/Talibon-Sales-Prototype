<?php

namespace App\Services;

use App\Domain\Correspondence\CorrespondenceClassification;
use App\Models\CorrespondenceEvent;
use App\Models\CorrespondenceRecord;
use App\Models\Document;
use App\Models\DocumentLink;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Throwable;

final class CorrespondenceEvidenceService
{
    public function __construct(
        private readonly CorrespondenceLifecycleService $lifecycle,
        private readonly CorrespondenceRoutingService $routing,
        private readonly DocumentAttachmentService $attachments,
    ) {
    }

    /** @param array<int, \Illuminate\Http\UploadedFile> $files */
    public function register(User $actor, CorrespondenceRecord $correspondence, string $correlationId, array $files): CorrespondenceRecord
    {
        if ($files === []) {
            return $this->lifecycle->register($actor, $correspondence, $correlationId);
        }

        $this->attachments->assertValidUploads($files);
        $attached = [];

        try {
            return DB::transaction(function () use ($actor, $correspondence, $correlationId, $files, &$attached): CorrespondenceRecord {
                $record = $this->lifecycle->register($actor, $correspondence, $correlationId);
                $event = $this->event($record, $correlationId, 'registered');
                $attached = $this->attachments->attach($actor, $files, [
                    ['model' => $record, 'relationship' => 'supporting_document'],
                    ['model' => $event, 'relationship' => 'action_evidence'],
                ]);

                return $record;
            });
        } catch (Throwable $exception) {
            $this->attachments->cleanupDocuments($attached);
            throw $exception;
        }
    }

    /** @param array<int, \Illuminate\Http\UploadedFile> $files */
    public function classify(
        User $actor,
        CorrespondenceRecord $correspondence,
        CorrespondenceClassification $classification,
        string $correlationId,
        ?string $remarks,
        array $files,
    ): CorrespondenceRecord {
        if ($files !== []) {
            $this->attachments->assertValidUploads($files);
        }

        $attached = [];

        try {
            return DB::transaction(function () use ($actor, $correspondence, $classification, $correlationId, $remarks, $files, &$attached): CorrespondenceRecord {
                $record = $this->lifecycle->classify($actor, $correspondence, $classification, $correlationId, $remarks);
                $this->reclassifyExistingEvidence($record, $classification->value);

                if ($files !== []) {
                    $event = $this->event($record, $correlationId, 'classified');
                    $attached = $this->attachments->attach($actor, $files, [[
                        'model' => $event,
                        'relationship' => 'action_evidence',
                    ]], $classification->value);
                }

                return $record;
            });
        } catch (Throwable $exception) {
            $this->attachments->cleanupDocuments($attached);
            throw $exception;
        }
    }

    /** @param array<string, mixed> $data @param array<int, \Illuminate\Http\UploadedFile> $files */
    public function route(User $actor, CorrespondenceRecord $correspondence, array $data, string $correlationId, array $files): CorrespondenceRecord
    {
        if ($files === []) {
            return $this->routing->route($actor, $correspondence, $data, $correlationId);
        }

        $this->attachments->assertValidUploads($files);
        $attached = [];

        try {
            return DB::transaction(function () use ($actor, $correspondence, $data, $correlationId, $files, &$attached): CorrespondenceRecord {
                $record = $this->routing->route($actor, $correspondence, $data, $correlationId);
                $event = $this->event($record, $correlationId, 'routed');
                $classification = $record->classification?->value ?? 'internal';
                $attached = $this->attachments->attach($actor, $files, [[
                    'model' => $event,
                    'relationship' => 'route_evidence',
                ]], $classification);

                return $record;
            });
        } catch (Throwable $exception) {
            $this->attachments->cleanupDocuments($attached);
            throw $exception;
        }
    }

    /** @param array<int, \Illuminate\Http\UploadedFile> $files */
    public function act(User $actor, CorrespondenceRecord $correspondence, string $correlationId, ?string $remarks, array $files): CorrespondenceRecord
    {
        if ($files === []) {
            return $this->routing->markInAction($actor, $correspondence, $correlationId, $remarks);
        }

        $this->attachments->assertValidUploads($files);
        $attached = [];

        try {
            return DB::transaction(function () use ($actor, $correspondence, $correlationId, $remarks, $files, &$attached): CorrespondenceRecord {
                $record = $this->routing->markInAction($actor, $correspondence, $correlationId, $remarks);
                $event = $this->event($record, $correlationId, 'in_action');
                $classification = $record->classification?->value ?? 'internal';
                $attached = $this->attachments->attach($actor, $files, [[
                    'model' => $event,
                    'relationship' => 'action_evidence',
                ]], $classification);

                return $record;
            });
        } catch (Throwable $exception) {
            $this->attachments->cleanupDocuments($attached);
            throw $exception;
        }
    }

    private function event(CorrespondenceRecord $record, string $correlationId, string $event): CorrespondenceEvent
    {
        return CorrespondenceEvent::query()
            ->where('correspondence_record_id', $record->id)
            ->where('correlation_id', $correlationId)
            ->where('event', $event)
            ->orderByDesc('id')
            ->firstOrFail();
    }

    private function reclassifyExistingEvidence(CorrespondenceRecord $record, string $classification): void
    {
        $documentIds = DocumentLink::query()
            ->where('linkable_type', $record->getMorphClass())
            ->where('linkable_id', $record->id)
            ->pluck('document_id');

        $eventIds = CorrespondenceEvent::query()
            ->where('correspondence_record_id', $record->id)
            ->pluck('id');

        if ($eventIds->isNotEmpty()) {
            $documentIds = $documentIds->merge(
                DocumentLink::query()
                    ->where('linkable_type', (new CorrespondenceEvent())->getMorphClass())
                    ->whereIn('linkable_id', $eventIds)
                    ->pluck('document_id'),
            );
        }

        $ids = $documentIds->unique()->values()->all();
        if ($ids !== []) {
            Document::query()->whereIn('id', $ids)->update(['classification' => $classification]);
        }
    }
}
